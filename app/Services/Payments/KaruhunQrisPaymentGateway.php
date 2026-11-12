<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Data\Payments\CreatePaymentData;
use App\Data\Payments\PaymentMethodData;
use App\Data\Payments\PaymentTransactionData;
use App\Enums\PaymentGatewayDriver;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment\Payment;
use Bayurifkialghifari\DynamicQris\DTOs\CheckPaymentRequest;
use Bayurifkialghifari\DynamicQris\DTOs\CreateQrisRequest;
use Bayurifkialghifari\DynamicQris\DTOs\RequestOptions;
use Bayurifkialghifari\DynamicQris\DynamicQrisClient;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use UnexpectedValueException;

final class KaruhunQrisPaymentGateway implements PaymentGateway
{
    public function __construct(private readonly DynamicQrisClient $client) {}

    public function paymentMethods(): array
    {
        return [
            new PaymentMethodData(PaymentMethod::Qris, 'gopay', 'QRIS GoPay', 'qris', minimumAmount: 1, feeByMerchant: true),
            new PaymentMethodData(PaymentMethod::Qris, 'shopee', 'QRIS ShopeePay', 'qris', minimumAmount: 1, feeByMerchant: true),
        ];
    }

    public function createPayment(CreatePaymentData $data): PaymentTransactionData
    {
        $provider = strtolower($data->providerCode ?? 'gopay');

        if ($data->paymentMethod !== PaymentMethod::Qris || ! in_array($provider, ['gopay', 'shopee'], true)) {
            throw new InvalidArgumentException('Unsupported KaruhunQRIS payment method/provider combination.');
        }

        $payment = Payment::query()->where('order_id', $data->orderId)->first();
        $createdAt = $payment !== null ? CarbonImmutable::instance($payment->created_at) : CarbonImmutable::now();
        $deadline = $createdAt->addMinutes(15);
        $qris = $this->client->qris()->create(
            new CreateQrisRequest(amount: $data->amount, provider: $provider),
            new RequestOptions(idempotencyKey: $data->orderId),
        );

        if (
            blank($qris->id)
            || blank($qris->token)
            || ! is_int($qris->raw['requested_amount'] ?? null)
            || ! is_int($qris->raw['amount'] ?? null)
            || $qris->requestedAmount !== $data->amount
            || $qris->amount < $data->amount
            || $qris->provider !== $provider
        ) {
            throw new UnexpectedValueException('KaruhunQRIS returned an invalid payment response.');
        }

        if (filled($qris->expiresAt)) {
            $deadline = $deadline->min(CarbonImmutable::parse($qris->expiresAt));
        } elseif ($qris->expiresInSeconds > 0) {
            $deadline = $deadline->min(CarbonImmutable::now()->addSeconds($qris->expiresInSeconds));
        }

        $destination = filled($qris->qrisString) ? $qris->qrisString : $this->client->qris()->url($qris->id);

        return new PaymentTransactionData(
            id: $qris->id,
            orderId: $data->orderId,
            amount: $data->amount,
            totalPayment: $qris->amount,
            paymentMethod: PaymentMethod::Qris,
            paymentNumber: $destination,
            paymentUrl: null,
            status: PaymentStatus::Pending,
            expiresAt: $deadline,
            createdAt: $createdAt,
            providerCode: $provider,
            providerToken: $qris->token,
        );
    }

    public function paymentStatus(string $orderId): PaymentTransactionData
    {
        if (blank($orderId)) {
            throw new InvalidArgumentException('Payment order ID is required.');
        }

        $payment = Payment::query()
            ->where('driver', PaymentGatewayDriver::KaruhunQris->value)
            ->where('order_id', $orderId)
            ->first();

        if ($payment === null || blank($payment->provider_token) || blank($payment->transaction_id)) {
            throw new PaymentTransactionNotFoundException;
        }

        $result = $this->client->payment()->check(new CheckPaymentRequest(
            token: $payment->provider_token,
            wait: false,
        ));
        $providerTransaction = $result->transaction ?? $result->data->transaction;
        $total = (int) round((float) $payment->total);
        $provider = $result->provider ?: $result->data->provider;

        if (
            ! $result->success
            || ! is_bool($result->raw['paid'] ?? null)
            || ! is_int(data_get($result->raw, 'data.amount'))
            || ! in_array($result->status, ['pending', 'success', 'expired'], true)
            || $result->paid !== ($result->status === 'success')
            || ! hash_equals($payment->provider_token, $result->data->token)
            || $result->data->amount !== $total
            || ! hash_equals((string) $payment->channel, $provider)
            || ($providerTransaction !== null && $providerTransaction->amount !== $total)
            || ($result->paid && ($providerTransaction === null || blank($providerTransaction->transactionId)))
        ) {
            throw new UnexpectedValueException('KaruhunQRIS returned a mismatched payment status response.');
        }

        $deadline = $payment->expired_at !== null
            ? CarbonImmutable::instance($payment->expired_at)
            : CarbonImmutable::instance($payment->created_at)->addMinutes(15);

        if (filled($result->data->expiresAt)) {
            $deadline = $deadline->min(CarbonImmutable::parse($result->data->expiresAt));
        }

        $status = match ($result->status) {
            'success' => PaymentStatus::Paid,
            'expired' => PaymentStatus::Expired,
            default => $deadline->lessThanOrEqualTo(CarbonImmutable::now()) ? PaymentStatus::Expired : PaymentStatus::Pending,
        };
        $paidAt = $result->paid && $providerTransaction?->timestamp !== null
            ? CarbonImmutable::createFromTimestamp($providerTransaction->timestamp, 'UTC')
            : null;

        if ($paidAt !== null && $paidAt->greaterThan($deadline)) {
            throw new UnexpectedValueException('KaruhunQRIS reported a payment after its deadline; reconciliation is required.');
        }

        return new PaymentTransactionData(
            id: (string) $payment->transaction_id,
            orderId: $orderId,
            amount: (int) round((float) $payment->amount),
            totalPayment: $total,
            paymentMethod: PaymentMethod::Qris,
            paymentNumber: $payment->account_number,
            paymentUrl: null,
            status: $status,
            expiresAt: $deadline,
            providerCode: (string) $payment->channel,
            paidAt: $paidAt,
            providerPaymentId: $result->paid ? $providerTransaction?->transactionId : null,
        );
    }
}
