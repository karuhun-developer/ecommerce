<?php

namespace App\Actions\Ecommerce\Payment;

use App\Data\Payments\PaymentTransactionData;
use App\Enums\PaymentGatewayDriver;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

final class ReconcilePaymentStatusAction
{
    private const string PROVIDER_STATUS_PREFIX = 'provider-status:';

    public function __construct(private readonly SendPaymentNotificationAction $sendPaymentNotification = new SendPaymentNotificationAction) {}

    public function handle(
        Payment $payment,
        PaymentTransactionData $transaction,
        string $mismatchMessage = 'Payment gateway returned a mismatched payment status response.',
    ): Payment {
        return DB::transaction(function () use ($payment, $transaction, $mismatchMessage): Payment {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->getKey());
            $lockedOrder = $this->lockedOrder($lockedPayment);

            $this->validateTransaction($lockedPayment, $lockedOrder, $transaction, $mismatchMessage);

            if ($lockedPayment->paid_at !== null) {
                $this->sendPaymentNotification->handle($lockedPayment, $lockedOrder);

                return $lockedPayment;
            }

            $providerTerminalStatus = $this->providerTerminalStatus($lockedPayment);

            if ($providerTerminalStatus !== null && $transaction->status !== PaymentStatus::Paid) {
                $this->sendPaymentNotification->handle($lockedPayment, $lockedOrder);

                return $lockedPayment;
            }

            $attributes = $this->transactionAttributes($lockedPayment, $transaction);

            if ($transaction->status === PaymentStatus::Paid) {
                $attributes['paid_at'] = now();

                if ($providerTerminalStatus !== null) {
                    $attributes['account_code'] = null;
                }
            } elseif ($this->isProviderTerminalStatus($transaction->status)) {
                $attributes['account_code'] = self::PROVIDER_STATUS_PREFIX.$transaction->status->value;
                $attributes['expired_at'] = now();
            }

            $lockedPayment->update($attributes);

            if ($lockedOrder === null) {
                return $lockedPayment->refresh();
            }

            $fee = $transaction->totalPayment - (int) round((float) $lockedPayment->amount);
            $lockedOrder->update(['payment_fee' => $fee]);

            if ($transaction->status === PaymentStatus::Paid) {
                $lockedOrder->update(['status' => true]);
                $this->sendPaymentNotification->handle($lockedPayment, $lockedOrder);
            } elseif ($this->isProviderTerminalStatus($transaction->status)) {
                $this->sendPaymentNotification->handle($lockedPayment, $lockedOrder);
            }

            return $lockedPayment->refresh();
        }, attempts: 3);
    }

    private function providerTerminalStatus(Payment $payment): ?PaymentStatus
    {
        $accountCode = $payment->account_code;

        if (! is_string($accountCode) || ! str_starts_with($accountCode, self::PROVIDER_STATUS_PREFIX)) {
            return null;
        }

        $status = PaymentStatus::tryFrom(substr($accountCode, strlen(self::PROVIDER_STATUS_PREFIX)));

        return $status !== null && $this->isProviderTerminalStatus($status) ? $status : null;
    }

    private function isProviderTerminalStatus(PaymentStatus $status): bool
    {
        return in_array($status, [
            PaymentStatus::Failed,
            PaymentStatus::Cancelled,
            PaymentStatus::Expired,
        ], true);
    }

    private function validateTransaction(
        Payment $payment,
        ?Order $order,
        PaymentTransactionData $transaction,
        string $message,
    ): void {
        $principal = (int) round((float) $payment->amount);
        $storedTotal = (int) round((float) $payment->total);
        $storedMethod = $this->storedPaymentMethod($payment);
        $expectedProviderAmount = $payment->driver === PaymentGatewayDriver::Midtrans->value
            ? $storedTotal
            : $principal;

        $hasTransactionConflict = filled($payment->transaction_id)
            && ! hash_equals((string) $payment->transaction_id, $transaction->id);
        $hasTotalConflict = $payment->driver === PaymentGatewayDriver::Midtrans->value
            ? $transaction->totalPayment !== $storedTotal
            : filled($payment->transaction_id)
                && $payment->driver === PaymentGatewayDriver::Paywuz->value
                && $transaction->totalPayment !== $storedTotal;
        $transactionBelongsToAnotherPayment = Payment::query()
            ->where('transaction_id', $transaction->id)
            ->whereKeyNot($payment->getKey())
            ->lockForUpdate()
            ->exists();
        $orderPrincipalMismatch = $order !== null
            && (int) round((float) $order->total) !== $principal;

        if (
            ! hash_equals((string) $payment->order_id, $transaction->orderId)
            || $hasTransactionConflict
            || $hasTotalConflict
            || $transactionBelongsToAnotherPayment
            || $orderPrincipalMismatch
            || $transaction->amount !== $expectedProviderAmount
            || $transaction->totalPayment < $principal
            || ! $this->paymentMethodIsCompatible($payment, $storedMethod, $transaction)
        ) {
            throw new UnexpectedValueException($message);
        }
    }

    private function storedPaymentMethod(Payment $payment): ?PaymentMethod
    {
        $channel = trim((string) $payment->channel);
        $paymentMethod = PaymentMethod::tryFrom(strtolower($channel))
            ?? PaymentMethod::fromProviderCode($channel);

        if ($paymentMethod !== null) {
            return $paymentMethod;
        }

        if (
            $payment->driver === PaymentGatewayDriver::Paywuz->value
            && in_array(strtoupper($channel), ['MANDIRIVA', 'PERMATAVA'], true)
        ) {
            return PaymentMethod::Va;
        }

        return null;
    }

    private function paymentMethodIsCompatible(
        Payment $payment,
        ?PaymentMethod $storedMethod,
        PaymentTransactionData $transaction,
    ): bool {
        if ($storedMethod === null) {
            return false;
        }

        $storedProviderCode = strtoupper(trim((string) $payment->channel));
        $isMetaVa = $storedProviderCode === 'VA';
        $methodMatches = $storedMethod === $transaction->paymentMethod
            || ($isMetaVa && $transaction->paymentMethod !== PaymentMethod::Qris);

        if (! $methodMatches) {
            return false;
        }

        $legacyChannels = array_map(
            static fn (PaymentMethod $method): string => $method->value,
            PaymentMethod::cases(),
        );

        if (in_array((string) $payment->channel, $legacyChannels, true) || $isMetaVa) {
            return true;
        }

        return $transaction->providerCode !== null
            && hash_equals($storedProviderCode, strtoupper($transaction->providerCode));
    }

    private function transactionAttributes(Payment $payment, PaymentTransactionData $transaction): array
    {
        $principal = (int) round((float) $payment->amount);
        $destination = $transaction->paymentNumber ?? $transaction->paymentUrl;

        return [
            'transaction_id' => $transaction->id,
            'account_number' => $destination ?? $payment->account_number,
            'expired_at' => $transaction->expiresAt ?? $payment->expired_at,
            'fee' => $transaction->totalPayment - $principal,
            'total' => $transaction->totalPayment,
        ];
    }

    private function lockedOrder(Payment $payment): ?Order
    {
        if ($payment->payable_type !== Order::class) {
            return null;
        }

        return Order::query()
            ->with('user')
            ->lockForUpdate()
            ->find($payment->payable_id);
    }
}
