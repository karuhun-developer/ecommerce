<?php

namespace App\Actions\Api\V1\Callback;

use App\Actions\Ecommerce\Payment\ReconcilePaymentStatusAction;
use App\Actions\Ecommerce\Payment\SendPaymentNotificationAction;
use App\Data\Callbacks\PaywuzCallbackData;
use App\Enums\PaymentGatewayDriver;
use App\Models\Payment\Payment;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

final class HandlePaywuzCallbackAction
{
    private const string DELIVERY_LOG_NAME = 'paywuz';

    private const string DELIVERY_LOG_EVENT = 'callback_processed';

    private const string DELIVERY_LOG_DESCRIPTION = 'Paywuz callback delivery processed';

    public function __construct(
        private readonly ReconcilePaymentStatusAction $reconcilePaymentStatus,
        private readonly PaymentGatewayManager $paymentGatewayManager,
        private readonly SendPaymentNotificationAction $sendPaymentNotification,
    ) {}

    public function handle(PaywuzCallbackData $data): Payment
    {
        $signature = $this->requiredHeader($data->signature, 'X-Paywuz-Signature');
        $event = $this->requiredHeader($data->event, 'X-Paywuz-Event');
        $deliveryId = $this->requiredHeader($data->deliveryId, 'X-Paywuz-Delivery');

        $apiKey = config('payment.drivers.paywuz.api_key');

        if (! is_string($apiKey) || blank($apiKey)) {
            throw new RuntimeException('Paywuz API key is not configured.', 500);
        }

        $rawBody = $data->rawBody;
        $expectedSignature = 'sha256='.hash_hmac('sha256', $rawBody, $apiKey);

        if (! hash_equals($expectedSignature, $signature)) {
            throw new RuntimeException('Invalid Paywuz signature.', 403);
        }

        $this->validateDeliveryId($deliveryId);

        $payload = $this->decodePayload($rawBody);
        $orderId = $this->requiredPayloadString($payload, 'orderId');
        $providerStatus = $this->requiredPayloadString($payload, 'status');
        $this->validateEventStatus($event, $providerStatus);

        $payment = Payment::query()
            ->where('driver', PaymentGatewayDriver::Paywuz->value)
            ->where('order_id', $orderId)
            ->first();

        if ($payment === null) {
            throw new RuntimeException('Transaction not found', 404);
        }

        $deliveryHash = hash('sha256', $deliveryId);

        return Cache::store('file')
            ->lock('paywuz-payment:'.$payment->getKey(), 60)
            ->block(30, function () use ($payment, $deliveryHash): Payment {
                return DB::transaction(function () use ($payment, $deliveryHash): Payment {
                    $lockedPayment = Payment::query()
                        ->lockForUpdate()
                        ->findOrFail($payment->getKey());

                    if ($this->deliveryWasProcessed($lockedPayment, $deliveryHash)) {
                        $this->sendPaymentNotification->handle($lockedPayment);

                        return $lockedPayment;
                    }

                    $transaction = $this->paymentGatewayManager
                        ->driver(PaymentGatewayDriver::Paywuz)
                        ->paymentStatus((string) $lockedPayment->order_id);

                    $reconciledPayment = $this->reconcilePaymentStatus->handle($lockedPayment, $transaction);

                    $this->recordProcessedDelivery($lockedPayment, $deliveryHash);

                    return $reconciledPayment;
                });
            });
    }

    private function requiredHeader(mixed $value, string $name): string
    {
        if (! is_string($value) || blank($value)) {
            throw new RuntimeException("Missing Paywuz callback header: {$name}", 401);
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function decodePayload(string $rawBody): array
    {
        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Malformed Paywuz callback payload.', 400, $exception);
        }

        if (! is_array($payload)) {
            throw new InvalidArgumentException('Malformed Paywuz callback payload.', 400);
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private function requiredPayloadString(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;

        if (! is_string($value) && ! is_numeric($value)) {
            throw new InvalidArgumentException("Missing Paywuz callback field: {$field}", 400);
        }

        $value = trim((string) $value);

        if ($value === '') {
            throw new InvalidArgumentException("Missing Paywuz callback field: {$field}", 400);
        }

        return $value;
    }

    private function validateEventStatus(string $event, string $providerStatus): void
    {
        $pair = strtolower(trim($event)).':'.strtolower(trim($providerStatus));

        if (! in_array($pair, [
            'transaction.settlement:settlement',
            'transaction.paid:success',
            'transaction.failed:failed',
            'transaction.cancelled:cancelled',
        ], true)) {
            throw new InvalidArgumentException('Invalid Paywuz callback event or status.', 400);
        }
    }

    private function validateDeliveryId(string $deliveryId): void
    {
        if (strlen($deliveryId) > 255 || preg_match('/\A[\x21-\x7E]+\z/D', $deliveryId) !== 1) {
            throw new InvalidArgumentException('Invalid Paywuz delivery ID.', 400);
        }
    }

    private function deliveryWasProcessed(Payment $payment, string $deliveryHash): bool
    {
        return Activity::query()
            ->where('log_name', self::DELIVERY_LOG_NAME)
            ->where('event', self::DELIVERY_LOG_EVENT)
            ->where('description', self::DELIVERY_LOG_DESCRIPTION)
            ->where('subject_type', $payment->getMorphClass())
            ->where('subject_id', $payment->getKey())
            ->get(['properties'])
            ->contains(function (Activity $activity) use ($deliveryHash): bool {
                $properties = $this->decodeActivityProperties($activity->getRawOriginal('properties'));
                $storedDeliveryHash = $properties['delivery_id_sha256'] ?? null;

                return is_string($storedDeliveryHash)
                    && strlen($storedDeliveryHash) === 64
                    && hash_equals($storedDeliveryHash, $deliveryHash);
            });
    }

    private function recordProcessedDelivery(Payment $payment, string $deliveryHash): void
    {
        Activity::query()->create([
            'log_name' => self::DELIVERY_LOG_NAME,
            'description' => self::DELIVERY_LOG_DESCRIPTION,
            'subject_type' => $payment->getMorphClass(),
            'subject_id' => $payment->getKey(),
            'event' => self::DELIVERY_LOG_EVENT,
            'properties' => [
                'provider' => PaymentGatewayDriver::Paywuz->value,
                'delivery_id_sha256' => $deliveryHash,
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function decodeActivityProperties(mixed $properties): array
    {
        if (! is_string($properties)) {
            return [];
        }

        try {
            $decodedProperties = json_decode($properties, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decodedProperties) ? $decodedProperties : [];
    }
}
