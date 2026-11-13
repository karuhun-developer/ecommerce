<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Data\Payments\CreatePaymentData;
use App\Data\Payments\PaymentMethodData;
use App\Data\Payments\PaymentTransactionData;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Services\MidtransService;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

final class MidtransPaymentGateway implements PaymentGateway
{
    public function __construct(
        private readonly MidtransService $midtransService,
    ) {}

    public function paymentMethods(): array
    {
        return [
            new PaymentMethodData(PaymentMethod::Qris, 'qris', 'QRIS', 'qris', percentageFeeBasisPoints: 70),
            new PaymentMethodData(PaymentMethod::Bca, 'bca', 'BCA Virtual Account', 'virtual_account', flatFee: 4_500),
            new PaymentMethodData(PaymentMethod::Bni, 'bni', 'BNI Virtual Account', 'virtual_account', flatFee: 4_500),
            new PaymentMethodData(PaymentMethod::Bri, 'bri', 'BRI Virtual Account', 'virtual_account', flatFee: 4_500),
        ];
    }

    public function createPayment(CreatePaymentData $data): PaymentTransactionData
    {
        $providerAmount = $data->totalAmount ?? $data->amount;

        $response = $data->paymentMethod === PaymentMethod::Qris
            ? $this->midtransService->createQris($data->orderId, $providerAmount)
            : $this->midtransService->createBankTransfer($data->orderId, $providerAmount, $data->paymentMethod->value);

        if (! ($response['successful'] ?? false)) {
            throw new RuntimeException('Failed to create payment transaction. Please try again.');
        }

        return $this->normalizeTransaction($response['data'] ?? null, $data->paymentMethod);
    }

    public function paymentStatus(string $orderId): PaymentTransactionData
    {
        if (blank($orderId)) {
            throw new InvalidArgumentException('Payment order ID is required.');
        }

        $response = $this->midtransService->paymentStatus($orderId);

        if (($response['http_status'] ?? null) === 404) {
            throw new PaymentTransactionNotFoundException;
        }

        if (! ($response['successful'] ?? false)) {
            throw new RuntimeException('Failed to fetch payment status. Please try again.');
        }

        return $this->normalizeTransaction($response['data'] ?? null);
    }

    private function normalizeTransaction(mixed $payload, ?PaymentMethod $requestedMethod = null): PaymentTransactionData
    {
        try {
            if (! is_array($payload)) {
                throw new UnexpectedValueException;
            }

            $amount = $this->integer($payload['gross_amount'] ?? null);

            return new PaymentTransactionData(
                id: $this->string($payload['transaction_id'] ?? null),
                orderId: $this->string($payload['order_id'] ?? null),
                amount: $amount,
                totalPayment: $amount,
                paymentMethod: $this->paymentMethod($payload, $requestedMethod),
                paymentNumber: $this->nullableString(data_get($payload, 'va_numbers.0.va_number') ?? ($payload['permata_va_number'] ?? null)),
                paymentUrl: $this->paymentUrl($payload),
                status: $this->paymentStatusFromProvider($payload['transaction_status'] ?? null),
                expiresAt: $this->date($payload['expiry_time'] ?? null),
                createdAt: $this->date($payload['transaction_time'] ?? null),
                providerCode: $this->providerCode($payload, $requestedMethod),
            );
        } catch (Throwable $exception) {
            throw new UnexpectedValueException('Midtrans returned an invalid payment response.', 0, $exception);
        }
    }

    private function paymentMethod(array $payload, ?PaymentMethod $requestedMethod): PaymentMethod
    {
        $paymentType = $this->nullableString($payload['payment_type'] ?? null);

        if ($paymentType === 'qris') {
            return PaymentMethod::Qris;
        }

        if ($paymentType === 'bank_transfer') {
            $bank = $this->nullableString(data_get($payload, 'va_numbers.0.bank'));

            if ($bank !== null && PaymentMethod::tryFrom(strtolower($bank)) !== null) {
                return PaymentMethod::from(strtolower($bank));
            }

            if ($requestedMethod !== null && $requestedMethod !== PaymentMethod::Qris) {
                return $requestedMethod;
            }
        }

        if ($paymentType === null && $requestedMethod !== null) {
            return $requestedMethod;
        }

        throw new UnexpectedValueException;
    }

    private function paymentStatusFromProvider(mixed $status): PaymentStatus
    {
        if (! is_string($status)) {
            throw new UnexpectedValueException;
        }

        return match (strtolower($status)) {
            'pending' => PaymentStatus::Pending,
            'capture', 'settlement' => PaymentStatus::Paid,
            'deny', 'failure' => PaymentStatus::Failed,
            'cancel' => PaymentStatus::Cancelled,
            'expire' => PaymentStatus::Expired,
            default => throw new UnexpectedValueException,
        };
    }

    private function providerCode(array $payload, ?PaymentMethod $requestedMethod): string
    {
        $paymentType = $this->nullableString($payload['payment_type'] ?? null);

        if ($paymentType === 'qris') {
            return 'qris';
        }

        $bank = $this->nullableString(data_get($payload, 'va_numbers.0.bank'));

        if ($bank !== null) {
            return strtolower($bank);
        }

        if ($requestedMethod !== null) {
            return $requestedMethod->value;
        }

        throw new UnexpectedValueException;
    }

    private function paymentUrl(array $payload): ?string
    {
        $actions = $payload['actions'] ?? [];

        if (is_array($actions)) {
            foreach ($actions as $action) {
                if (is_array($action) && ($action['name'] ?? null) === 'generate-qr-code') {
                    return $this->nullableString($action['url'] ?? null);
                }
            }
        }

        return $this->nullableString($payload['redirect_url'] ?? null);
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new UnexpectedValueException;
        }

        $date = CarbonImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $value,
            $this->midtransService->timezone(),
        );

        if ($date->format('Y-m-d H:i:s') !== $value) {
            throw new UnexpectedValueException;
        }

        return $date;
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

    private function integer(mixed $value): int
    {
        if (! is_numeric($value)) {
            throw new UnexpectedValueException;
        }

        $integer = (int) round((float) $value);

        if ($integer <= 0) {
            throw new UnexpectedValueException;
        }

        return $integer;
    }
}
