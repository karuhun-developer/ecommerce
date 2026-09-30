<?php

namespace App\Actions\Ecommerce\Payment;

use App\Enums\PaymentStatus;
use App\Mail\OrderPaid;
use App\Mail\OrderPaymentFailed;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Activitylog\Models\Activity;

final class SendPaymentNotificationAction
{
    private const string PROVIDER_STATUS_PREFIX = 'provider-status:';

    private const string NOTIFICATION_LOG_NAME = 'payment';

    private const string NOTIFICATION_LOG_EVENT = 'notification_sent';

    private const string PAID_NOTIFICATION_DESCRIPTION = 'Order paid email sent';

    private const string FAILED_NOTIFICATION_DESCRIPTION = 'Order payment failed email sent';

    public function handle(Payment $payment, ?Order $order = null): void
    {
        $status = $payment->paid_at !== null
            ? PaymentStatus::Paid
            : $this->providerTerminalStatus($payment);

        if ($status === null) {
            return;
        }

        $order ??= $this->lockedOrder($payment);

        if ($order === null) {
            return;
        }

        $this->sendAfterCommit($payment, $order, $status);
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

    private function sendAfterCommit(Payment $payment, Order $order, PaymentStatus $status): void
    {
        $paymentId = $payment->getKey();
        $orderId = $order->getKey();

        DB::afterCommit(function () use ($paymentId, $orderId, $status): void {
            $notificationType = $this->notificationType($status);

            Cache::store('database')
                ->lock('payment-notification:'.$paymentId.':'.$notificationType, 60)
                ->block(30, function () use ($paymentId, $orderId, $status, $notificationType): void {
                    $storedPayment = Payment::query()->find($paymentId);

                    if ($storedPayment === null || $this->notificationWasSent($storedPayment, $notificationType)) {
                        return;
                    }

                    $storedOrder = Order::query()->with('user')->find($orderId);

                    if ($storedOrder === null) {
                        return;
                    }

                    $email = $storedOrder->user?->email ?? data_get($storedOrder->guest_data, 'contact_email');

                    if (blank($email)) {
                        return;
                    }

                    $mail = $status === PaymentStatus::Paid
                        ? new OrderPaid($storedOrder)
                        : new OrderPaymentFailed($storedOrder);

                    Mail::to($email)->send($mail);

                    Activity::query()->create([
                        'log_name' => self::NOTIFICATION_LOG_NAME,
                        'description' => $this->notificationDescription($notificationType),
                        'subject_type' => $storedPayment->getMorphClass(),
                        'subject_id' => $storedPayment->getKey(),
                        'event' => self::NOTIFICATION_LOG_EVENT,
                        'properties' => [
                            'notification' => $notificationType,
                        ],
                    ]);
                });
        });
    }

    private function notificationType(PaymentStatus $status): string
    {
        return $status === PaymentStatus::Paid ? 'paid' : 'failed';
    }

    private function notificationDescription(string $notificationType): string
    {
        return $notificationType === 'paid'
            ? self::PAID_NOTIFICATION_DESCRIPTION
            : self::FAILED_NOTIFICATION_DESCRIPTION;
    }

    private function notificationWasSent(Payment $payment, string $notificationType): bool
    {
        return Activity::query()
            ->where('log_name', self::NOTIFICATION_LOG_NAME)
            ->where('event', self::NOTIFICATION_LOG_EVENT)
            ->where('description', $this->notificationDescription($notificationType))
            ->where('subject_type', $payment->getMorphClass())
            ->where('subject_id', $payment->getKey())
            ->exists();
    }
}
