<?php

namespace App\Console\Commands;

use App\Actions\Ecommerce\Payment\RefreshPaymentStatusAction;
use App\Enums\PaymentGatewayDriver;
use App\Models\Payment\Payment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

#[Signature('payments:reconcile-qris')]
#[Description('Reconcile pending KaruhunQRIS payments, including their final expiry check')]
final class ReconcileQrisPayments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(RefreshPaymentStatusAction $refreshPaymentStatus): int
    {
        $failed = 0;

        Payment::query()
            ->where('driver', PaymentGatewayDriver::KaruhunQris->value)
            ->whereNull('paid_at')
            ->whereNull('account_code')
            ->whereNotNull('transaction_id')
            ->whereNotNull('provider_token')
            ->chunkById(50, function (Collection $payments) use ($refreshPaymentStatus, &$failed): void {
                foreach ($payments as $payment) {
                    try {
                        $refreshPaymentStatus->handle($payment);
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                        $this->warn('Payment '.$payment->getKey().' could not be checked; it will be retried.');
                    }
                }
            });

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
