<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Data\Payments\CreatePaymentData;
use App\Data\Payments\PaymentMethodData;
use App\Data\Payments\PaymentTransactionData;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;
use UnexpectedValueException;

final class PaywuzPaymentGateway implements PaymentGateway
{
    private array $configuration;

    public function __construct(PaymentGatewaySettingsService $settings)
    {
        $this->configuration = $settings->paywuzConfiguration();
    }

    public function paymentMethods(): array
    {
        $payload = $this->request()->get('payment-methods')->throw()->json('data');

        if (! is_array($payload) || ! array_is_list($payload)) {
            throw new UnexpectedValueException('Paywuz returned an invalid payment methods response.');
        }

        $methods = [];

        foreach ($payload as $method) {
            try {
                $methods[] = $this->normalizePaymentMethod($method);
            } catch (Throwable) {
                continue;
            }
        }

        return $methods;
    }

    public function createPayment(CreatePaymentData $data): PaymentTransactionData
    {
        $payload = [
            'orderId' => $data->orderId,
            'amount' => $data->amount,
            'paymentMethod' => $data->providerCode ?? $this->providerCode($data->paymentMethod),
        ];

        $this->addOptional($payload, 'expiryMinutes', $data->expiryMinutes ?? $this->configuration['expiry_minutes']);
        $this->addOptional($payload, 'redirectUrl', $data->redirectUrl ?? $this->configuration['redirect_url']);
        $this->addOptional($payload, 'feeByMerchant', $data->feeByMerchant ?? $this->configuration['fee_by_merchant']);
        $this->addOptional($payload, 'metadata', $data->metadata);
        $this->addOptional($payload, 'paymentReminderEnabled', $data->paymentReminderEnabled ?? $this->configuration['payment_reminder_enabled']);

        $response = $this->request()->post('transactions', $payload)->throw()->json('data');

        return $this->normalizeTransaction($response, $data->paymentMethod);
    }

    public function paymentStatus(string $orderId): PaymentTransactionData
    {
        if (blank($orderId)) {
            throw new InvalidArgumentException('Payment order ID is required.');
        }

        $response = $this->request()->get('transactions/'.rawurlencode($orderId));

        if ($response->status() === 404) {
            throw new PaymentTransactionNotFoundException;
        }

        $payload = $response->throw()->json('data');

        return $this->normalizeTransaction($payload);
    }

    private function request(): PendingRequest
    {
        $apiKey = $this->configuration['api_key'];
        $baseUrl = $this->configuration['base_url'];

        if (! is_string($apiKey) || blank($apiKey)) {
            throw new InvalidArgumentException('Paywuz API key is not configured.');
        }

        if (! is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Paywuz base URL is not configured correctly.');
        }

        $scheme = strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME));

        if ($scheme !== 'https' && ! ($scheme === 'http' && app()->environment(['local', 'testing']))) {
            throw new InvalidArgumentException('Paywuz base URL is not configured correctly.');
        }

        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->withoutRedirecting()
            ->connectTimeout((float) $this->configuration['connect_timeout'])
            ->timeout((float) $this->configuration['timeout']);
    }

    private function normalizePaymentMethod(mixed $payload): PaymentMethodData
    {
        if (! is_array($payload)) {
            throw new UnexpectedValueException;
        }

        $providerCode = $this->string($payload['code'] ?? null);

        return new PaymentMethodData(
            paymentMethod: $this->paymentMethod($providerCode),
            providerCode: $providerCode,
            name: $this->string($payload['name'] ?? null),
            type: $this->string($payload['type'] ?? null),
            flatFee: $this->nonNegativeInteger(data_get($payload, 'fee.flatIdr')),
            percentageFeeBasisPoints: $this->nonNegativeInteger(data_get($payload, 'fee.percentBps')),
            totalFee: $this->optionalNonNegativeInteger(data_get($payload, 'fee.totalIdr')),
            minimumAmount: $this->positiveInteger(data_get($payload, 'limits.minIdr')),
            maximumAmount: $this->positiveInteger(data_get($payload, 'limits.maxIdr')),
            feeByMerchant: $this->feeByMerchant($payload),
            feeTiers: $this->feeTiers($payload, $providerCode),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{minimumAmount: ?int, maximumAmount: ?int, flatFee: int, percentageFeeBasisPoints: int}>
     */
    private function feeTiers(array $payload, string $providerCode): array
    {
        $tiers = data_get($payload, 'fee.tiers', $payload['feeTiers'] ?? null);

        if ($tiers === null || $tiers === []) {
            return strtoupper($providerCode) === 'QRIS'
                ? [
                    [
                        'minimumAmount' => null,
                        'maximumAmount' => 149_999,
                        'flatFee' => 290,
                        'percentageFeeBasisPoints' => 70,
                    ],
                    [
                        'minimumAmount' => 150_000,
                        'maximumAmount' => null,
                        'flatFee' => 0,
                        'percentageFeeBasisPoints' => 95,
                    ],
                ]
                : [];
        }

        if (! is_array($tiers) || ! array_is_list($tiers)) {
            throw new UnexpectedValueException;
        }

        return array_map(function (mixed $tier): array {
            if (! is_array($tier)) {
                throw new UnexpectedValueException;
            }

            $minimumAmount = $this->optionalPositiveInteger(
                $tier['minimumAmount'] ?? $tier['minIdr'] ?? null,
            );
            $maximumAmount = $this->optionalPositiveInteger(
                $tier['maximumAmount'] ?? $tier['maxIdr'] ?? null,
            );

            if ($minimumAmount !== null && $maximumAmount !== null && $minimumAmount > $maximumAmount) {
                throw new UnexpectedValueException;
            }

            return [
                'minimumAmount' => $minimumAmount,
                'maximumAmount' => $maximumAmount,
                'flatFee' => $this->nonNegativeInteger($tier['flatFee'] ?? $tier['flatIdr'] ?? null),
                'percentageFeeBasisPoints' => $this->nonNegativeInteger(
                    $tier['percentageFeeBasisPoints'] ?? $tier['percentBps'] ?? null,
                ),
            ];
        }, $tiers);
    }

    /** @param array<string, mixed> $payload */
    private function feeByMerchant(array $payload): bool
    {
        $value = array_key_exists('feeByMerchant', $payload)
            ? $payload['feeByMerchant']
            : $this->configuration['fee_by_merchant'];

        if ($value === null || $value === '') {
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (in_array($value, [0, 1, '0', '1'], true)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                'true', 'yes', 'on' => true,
                'false', 'no', 'off' => false,
                default => throw new UnexpectedValueException,
            };
        }

        throw new UnexpectedValueException;
    }

    private function normalizeTransaction(mixed $payload, ?PaymentMethod $requestedMethod = null): PaymentTransactionData
    {
        try {
            if (! is_array($payload)) {
                throw new UnexpectedValueException;
            }

            return new PaymentTransactionData(
                id: $this->string($payload['id'] ?? null),
                orderId: $this->string($payload['orderId'] ?? null),
                amount: $this->positiveInteger($payload['amount'] ?? null),
                totalPayment: $this->positiveInteger($payload['totalPayment'] ?? null),
                paymentMethod: $this->paymentMethod($payload['paymentMethod'] ?? null, $requestedMethod),
                paymentNumber: $this->nullableString($payload['paymentNumber'] ?? null),
                paymentUrl: $this->nullableString($payload['paymentUrl'] ?? null),
                status: $this->status($payload['status'] ?? null),
                expiresAt: $this->date($payload['expiresAt'] ?? null),
                createdAt: $this->date($payload['createdAt'] ?? null),
                providerCode: $this->string($payload['paymentMethod'] ?? null),
            );
        } catch (Throwable $exception) {
            throw new UnexpectedValueException('Paywuz returned an invalid payment response.', 0, $exception);
        }
    }

    private function providerCode(PaymentMethod $paymentMethod): string
    {
        return match ($paymentMethod) {
            PaymentMethod::Qris => 'QRIS',
            PaymentMethod::Va => 'VA',
            PaymentMethod::Bca => 'BCAVA',
            PaymentMethod::Bni => 'BNIVA',
            PaymentMethod::Bri => 'BRIVA',
        };
    }

    private function paymentMethod(mixed $providerCode, ?PaymentMethod $requestedMethod = null): PaymentMethod
    {
        if (! is_string($providerCode)) {
            throw new UnexpectedValueException;
        }

        $providerCode = strtoupper(trim($providerCode));

        if (
            $requestedMethod === PaymentMethod::Va
            && in_array($providerCode, ['VA', '014', '009', '002', 'BCAVA', 'BNIVA', 'BRIVA', 'CIMBVA', 'BSIVA', 'MANDIRIVA', 'PERMATAVA'], true)
        ) {
            return PaymentMethod::Va;
        }

        return match ($providerCode) {
            'QRIS' => PaymentMethod::Qris,
            'VA', 'CIMBVA', 'BSIVA', 'MANDIRIVA', 'PERMATAVA' => PaymentMethod::Va,
            '014', 'BCAVA' => PaymentMethod::Bca,
            '009', 'BNIVA' => PaymentMethod::Bni,
            '002', 'BRIVA' => PaymentMethod::Bri,
            default => throw new UnexpectedValueException,
        };
    }

    private function status(mixed $status): PaymentStatus
    {
        if (! is_string($status)) {
            throw new UnexpectedValueException;
        }

        return match (strtolower($status)) {
            'pending', 'settlement' => PaymentStatus::Pending,
            'success' => PaymentStatus::Paid,
            'failed' => PaymentStatus::Failed,
            'cancelled' => PaymentStatus::Cancelled,
            default => throw new UnexpectedValueException,
        };
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new UnexpectedValueException;
        }

        return CarbonImmutable::parse($value);
    }

    private function string(mixed $value): string
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            throw new UnexpectedValueException;
        }

        return $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value) && ! is_numeric($value)) {
            throw new UnexpectedValueException;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function positiveInteger(mixed $value): int
    {
        $value = $this->integer($value);

        if ($value <= 0) {
            throw new UnexpectedValueException;
        }

        return $value;
    }

    private function nonNegativeInteger(mixed $value): int
    {
        $value = $this->integer($value);

        if ($value < 0) {
            throw new UnexpectedValueException;
        }

        return $value;
    }

    private function optionalNonNegativeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->nonNegativeInteger($value);
    }

    private function optionalPositiveInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->positiveInteger($value);
    }

    private function integer(mixed $value): int
    {
        if (! is_int($value) && ! is_float($value) && ! (is_string($value) && is_numeric($value))) {
            throw new UnexpectedValueException;
        }

        $integer = (int) $value;

        if ((float) $value !== (float) $integer) {
            throw new UnexpectedValueException;
        }

        return $integer;
    }

    private function addOptional(array &$payload, string $key, mixed $value): void
    {
        if ($value !== null && $value !== '') {
            $payload[$key] = $value;
        }
    }
}
