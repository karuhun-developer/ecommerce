<?php

namespace App\Actions\Ecommerce\Payment;

use App\Enums\PaymentGatewayDriver;
use App\Models\Payment\Payment;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentGatewaySettingsService;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

final class RefreshPaymentStatusAction
{
    public function __construct(
        private readonly PaymentGatewayManager $paymentGatewayManager,
        private readonly ReconcilePaymentStatusAction $reconcilePaymentStatus,
        private readonly PaymentGatewaySettingsService $settings,
    ) {}

    public function handle(Payment $payment): Payment
    {
        $storedPayment = Payment::query()->findOrFail($payment->getKey());

        if ($storedPayment->driver === PaymentGatewayDriver::KaruhunQris->value) {
            $lockSeconds = max(30, $this->settings->karuhunQrisConfiguration()['timeout'] + 15);
            $result = Cache::lock('qris-payment-check:'.$storedPayment->getKey(), $lockSeconds)
                ->get(function () use ($storedPayment): Payment {
                    $storedPayment->refresh();

                    if (
                        $storedPayment->paid_at !== null
                        || filled($storedPayment->account_code)
                        || ($storedPayment->last_checked_at?->greaterThan(now()->subSeconds(15)) ?? false)
                    ) {
                        return $storedPayment;
                    }

                    $result = $this->refreshFromGateway($storedPayment);
                    $result->update(['last_checked_at' => now()]);

                    return $result;
                });

            return $result instanceof Payment ? $result : $storedPayment->refresh();
        }

        return $this->refreshFromGateway($storedPayment);
    }

    private function refreshFromGateway(Payment $storedPayment): Payment
    {
        $driver = PaymentGatewayDriver::tryFrom((string) $storedPayment->driver);
        $orderId = (string) $storedPayment->order_id;

        if ($driver === null) {
            throw new InvalidArgumentException("Unsupported payment gateway [{$storedPayment->driver}].");
        }

        if (blank($orderId)) {
            throw new InvalidArgumentException('Payment order ID is required.');
        }

        $transaction = $this->paymentGatewayManager
            ->driver($driver)
            ->paymentStatus($orderId);

        return $this->reconcilePaymentStatus->handle($storedPayment, $transaction);
    }
}
