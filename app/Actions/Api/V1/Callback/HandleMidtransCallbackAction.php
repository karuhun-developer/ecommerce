<?php

namespace App\Actions\Api\V1\Callback;

use App\Actions\Ecommerce\Payment\ReconcilePaymentStatusAction;
use App\Data\Callbacks\MidtransCallbackData;
use App\Enums\PaymentGatewayDriver;
use App\Models\Payment\Payment;
use App\Services\MidtransService;
use App\Services\Payments\PaymentGatewayManager;
use InvalidArgumentException;

class HandleMidtransCallbackAction
{
    public function __construct(
        private readonly MidtransService $midtransService,
        private readonly ReconcilePaymentStatusAction $reconcilePaymentStatus,
        private readonly PaymentGatewayManager $paymentGatewayManager,
    ) {}

    public function handle(MidtransCallbackData $data): Payment
    {
        $orderId = $data->orderId;
        $statusCode = $data->statusCode;
        $grossAmount = $data->grossAmount;
        $signatureKey = $data->signatureKey;
        $transactionStatus = $data->transactionStatus;

        if (! $this->midtransService->validateSignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            throw new \Exception('Invalid signature key', 403);
        }

        $this->validateTransactionStatus($transactionStatus);

        $payment = Payment::query()
            ->where('driver', PaymentGatewayDriver::Midtrans->value)
            ->where('order_id', $orderId)
            ->first();

        if ($payment === null) {
            throw new \Exception('Transaction not found', 404);
        }

        $transaction = $this->paymentGatewayManager
            ->driver(PaymentGatewayDriver::Midtrans)
            ->paymentStatus($orderId);

        return $this->reconcilePaymentStatus->handle($payment, $transaction);
    }

    private function validateTransactionStatus(string $transactionStatus): void
    {
        if (! in_array(strtolower($transactionStatus), [
            'pending',
            'capture',
            'settlement',
            'deny',
            'failure',
            'cancel',
            'expire',
        ], true)) {
            throw new InvalidArgumentException('Unsupported Midtrans transaction status.', 400);
        }
    }
}
