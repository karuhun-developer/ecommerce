<?php

namespace App\Actions\Ecommerce\Payment;

use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class CreatePaymentAction
{
    public function __construct(
        public readonly MidtransService $midtransService,
    ) {}

    public function handle(
        Order $order,
        string $paymentMethod,
        ?User $actor = null,
        ?string $guestToken = null,
    ): Payment {
        $this->authorize($order, $actor, $guestToken);

        if (! in_array($paymentMethod, ['qris', 'bca', 'bni', 'bri'], true)) {
            throw new InvalidArgumentException('Unsupported payment method.');
        }

        return Cache::lock("payment:create:order:{$order->getKey()}", 90)->block(10, function () use ($order, $paymentMethod, $actor, $guestToken): Payment {
            $payment = DB::transaction(function () use ($order, $paymentMethod, $actor, $guestToken): Payment {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

                $this->authorize($lockedOrder, $actor, $guestToken);

                $existingPayment = Payment::query()
                    ->where('payable_type', $lockedOrder->getMorphClass())
                    ->where('payable_id', $lockedOrder->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($existingPayment) {
                    return $existingPayment;
                }

                $amount = (int) round((float) $lockedOrder->total);
                $fee = $paymentMethod === 'qris'
                    ? (int) round($amount * 0.007)
                    : 4500;

                $lockedOrder->update([
                    'payment_fee' => $fee,
                ]);

                return Payment::query()->create([
                    'driver' => 'midtrans',
                    'payable_type' => $lockedOrder->getMorphClass(),
                    'payable_id' => $lockedOrder->getKey(),
                    'order_id' => (string) Str::uuid(),
                    'transaction_id' => null,
                    'payment_type' => $paymentMethod === 'qris' ? 'qris' : 'bank_transfer',
                    'account_number' => '',
                    'channel' => $paymentMethod,
                    'expired_at' => now()->addDay(),
                    'amount' => $amount,
                    'fee' => $fee,
                    'total' => $amount + $fee,
                ]);
            });

            if (filled($payment->transaction_id)) {
                return $payment;
            }

            $midtrans = $payment->payment_type === 'qris'
                ? $this->midtransService->createQris(
                    orderId: $payment->order_id,
                    amount: (int) $payment->total,
                )
                : $this->midtransService->createBankTransfer(
                    orderId: $payment->order_id,
                    amount: (int) $payment->total,
                    bank: $payment->channel,
                );

            if (! ($midtrans['successful'] ?? false) || blank($midtrans['transaction_id'] ?? null)) {
                throw new RuntimeException('Failed to create payment transaction. Please try again.');
            }

            return DB::transaction(function () use ($payment, $midtrans): Payment {
                $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

                if (filled($lockedPayment->transaction_id)) {
                    return $lockedPayment;
                }

                $lockedPayment->update([
                    'transaction_id' => $midtrans['transaction_id'],
                    'account_number' => $midtrans['account'] ?? '',
                    'account_code' => $midtrans['code'] ?? null,
                ]);

                return $lockedPayment;
            });
        });
    }

    private function authorize(Order $order, ?User $actor, ?string $guestToken): void
    {
        if ($order->user_id !== null) {
            if ($actor?->getKey() !== $order->user_id) {
                throw new AuthorizationException;
            }

            return;
        }

        if (blank($order->access_token) || blank($guestToken) || ! hash_equals($order->access_token, $guestToken)) {
            throw new AuthorizationException;
        }
    }
}
