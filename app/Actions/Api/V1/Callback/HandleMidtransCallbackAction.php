<?php

namespace App\Actions\Api\V1\Callback;

use App\Mail\OrderPaid;
use App\Mail\OrderPaymentFailed;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Services\MidtransService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class HandleMidtransCallbackAction
{
    public function __construct(
        public readonly MidtransService $midtransService,
    ) {}

    /** @param array<string, mixed> $payload */
    public function handle(array $payload): Payment
    {
        $this->validatePayload($payload);

        $orderId = (string) $payload['order_id'];
        $statusCode = (string) $payload['status_code'];
        $grossAmount = (string) $payload['gross_amount'];
        $signatureKey = (string) $payload['signature_key'];
        $transactionStatus = (string) $payload['transaction_status'];

        Log::info('Midtrans callback received', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'transaction_status' => $transactionStatus,
        ]);

        if (! $this->midtransService->validateSignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            throw new \Exception('Invalid signature key', 403);
        }

        return DB::transaction(function () use ($orderId, $transactionStatus): Payment {
            $payment = Payment::query()
                ->where('order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw new \Exception('Transaction not found', 404);
            }

            $this->handlePaymentStatus($transactionStatus, $payment);

            return $payment->refresh();
        });
    }

    private function handlePaymentStatus(string $transactionStatus, Payment $payment): void
    {
        if (in_array($transactionStatus, ['capture', 'settlement'], true)) {
            $this->markPaid($payment);

            return;
        }

        if (in_array($transactionStatus, ['deny', 'expire', 'cancel'], true)) {
            $this->markFailed($payment);

            return;
        }

        if ($transactionStatus !== 'pending') {
            Log::warning('Midtrans callback has unsupported status', [
                'order_id' => $payment->order_id,
                'transaction_status' => $transactionStatus,
            ]);
        }
    }

    private function markPaid(Payment $payment): void
    {
        if ($payment->paid_at !== null || $this->hasFailed($payment)) {
            return;
        }

        $payment->update([
            'paid_at' => now(),
        ]);

        $order = $this->lockedOrder($payment);

        if (! $order) {
            return;
        }

        $order->update([
            'status' => true,
        ]);

        $email = $order->user?->email ?? data_get($order->guest_data, 'contact_email');

        if ($email) {
            DB::afterCommit(fn () => Mail::to($email)->send(new OrderPaid($order)));
        }
    }

    private function markFailed(Payment $payment): void
    {
        if ($payment->paid_at !== null || $this->hasFailed($payment)) {
            return;
        }

        $payment->update([
            'expired_at' => now(),
        ]);

        $order = $this->lockedOrder($payment);

        if (! $order) {
            return;
        }

        $email = $order->user?->email ?? data_get($order->guest_data, 'contact_email');

        if ($email) {
            DB::afterCommit(fn () => Mail::to($email)->send(new OrderPaymentFailed($order)));
        }
    }

    private function hasFailed(Payment $payment): bool
    {
        return $payment->expired_at?->isPast() ?? false;
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

    /** @param array<string, mixed> $payload */
    private function validatePayload(array $payload): void
    {
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key', 'transaction_status'] as $field) {
            if (! isset($payload[$field]) || ! is_scalar($payload[$field])) {
                throw new InvalidArgumentException("Missing Midtrans callback field: {$field}", 400);
            }
        }
    }
}
