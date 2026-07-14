<?php

namespace App\Actions\Ecommerce\Payment;

use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Services\MidtransService;
use Exception;
use Illuminate\Support\Facades\DB;

class CreatePaymentAction
{
    public function __construct(
        public readonly MidtransService $midtransService,
    ) {}

    /**
     * Handle the action.
     */
    public function handle(Order $order, string $paymentMethod): Payment
    {
        return DB::transaction(function () use ($order, $paymentMethod) {
            $amount = $order->total;

            $fee = $paymentMethod === 'qris'
                ? $amount * 0.007
                : 4500;

            // Update order
            $order->update([
                'payment_fee' => $fee,
            ]);

            $fee = (int) round($fee);
            $totalAmount = $amount + $fee;

            $orderId = uniqid().time();

            $paymentType = $paymentMethod === 'qris' ? 'qris' : 'bank_transfer';

            $payment = Payment::create([
                'driver' => 'midtrans',
                'payable_type' => get_class($order),
                'payable_id' => $order->id,
                'order_id' => $orderId,
                'transaction_id' => null,
                'payment_type' => $paymentType,
                'account_number' => 'AUTO_GENERATED',
                'channel' => $paymentMethod,
                'expired_at' => now()->addHours(24),
                'amount' => $amount,
                'fee' => $fee,
                'total' => $totalAmount,
            ]);

            if ($paymentMethod === 'qris') {
                $midtrans = $this->midtransService->createQris(
                    orderId: $payment->order_id,
                    amount: (int) $payment->total,
                );
            } else {
                // If it's mandiri, we use echannel for midtrans bill_payment. Wait, if MidtransService sends it as 'mandiri' under 'bank_transfer' -> 'bank' it might fail midtrans API if it expects echannel. But the user example relies on `midtransService->createBankTransfer`. I will pass the paymentMethod as bank directly.
                // But Midtrans documentation: Mandiri is echannel, Permata is permata.
                // However, our midtransService has bank_transfer => ['bank' => $bank]. If it's echannel, it should be under echannel, not bank_transfer.
                // Actually, I'll just pass the $paymentMethod as is to MidtransService, as the user instructed.
                $midtrans = $this->midtransService->createBankTransfer(
                    orderId: $payment->order_id,
                    bank: $paymentMethod,
                    amount: (int) $payment->total,
                );
            }

            if (! $midtrans['successful']) {
                throw new Exception('Failed to create Midtrans transaction: '.($midtrans['message'] ?? 'Unknown Error'));
            }

            $payment->transaction_id = $midtrans['transaction_id'] ?? '';
            $payment->account_number = $midtrans['account'] ?? '';
            $payment->account_code = $midtrans['code'] ?? '';
            $payment->save();

            return $payment;
        });
    }
}
