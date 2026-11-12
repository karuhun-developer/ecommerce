<?php

namespace App\Actions\Ecommerce\Payment;

use App\Contracts\Payments\PaymentGateway;
use App\Data\Payments\CreatePaymentData;
use App\Data\Payments\PaymentMethodData;
use App\Data\Payments\PaymentTransactionData;
use App\Enums\PaymentGatewayDriver;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Models\User;
use App\Services\Payments\PaymentFeeCalculator;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use UnexpectedValueException;

class CreatePaymentAction
{
    public function __construct(
        private readonly PaymentGatewayManager $paymentGatewayManager,
        private readonly ?ReconcilePaymentStatusAction $reconcilePaymentStatus = null,
        private readonly ?PaymentFeeCalculator $paymentFeeCalculator = null,
    ) {}

    public function handle(
        Order $order,
        string $paymentMethod,
        ?User $actor = null,
        ?string $guestToken = null,
        ?string $providerCode = null,
    ): Payment {
        $this->authorize($order, $actor, $guestToken);

        $selectedPaymentMethod = PaymentMethod::tryFrom($paymentMethod);

        if ($selectedPaymentMethod === null) {
            throw new InvalidArgumentException('Unsupported payment method.');
        }

        $payment = $this->existingPayment($order, $actor, $guestToken);
        $preparedDriver = null;
        $gateway = null;

        if ($payment === null) {
            $preparedDriver = $this->paymentGatewayManager->defaultDriver();
            $gateway = $this->paymentGatewayManager->driver($preparedDriver);
            $advertisedMethod = $this->advertisedPaymentMethod(
                $gateway,
                $selectedPaymentMethod,
                $providerCode,
            );
            $payment = $this->createPaymentRecord(
                $order,
                $preparedDriver,
                $advertisedMethod,
                $actor,
                $guestToken,
            );
        }

        if (filled($payment->transaction_id)) {
            return $payment;
        }

        $driver = PaymentGatewayDriver::tryFrom((string) $payment->driver);
        $storedPaymentMethod = $this->storedPaymentMethod($payment);

        if ($driver === null) {
            throw new InvalidArgumentException("Unsupported payment gateway [{$payment->driver}].");
        }

        if ($storedPaymentMethod === null) {
            throw new InvalidArgumentException('Unsupported payment method.');
        }

        if ($gateway === null || $preparedDriver !== $driver) {
            $gateway = $this->paymentGatewayManager->driver($driver);
        }

        $transaction = $gateway->createPayment(new CreatePaymentData(
            orderId: (string) $payment->order_id,
            amount: (int) round((float) $payment->amount),
            paymentMethod: $storedPaymentMethod,
            providerCode: (string) $payment->channel,
            totalAmount: (int) round((float) $payment->total),
        ));

        if ($transaction->status === PaymentStatus::Pending) {
            $this->ensureUsableDestination($transaction);
        }

        return ($this->reconcilePaymentStatus ?? new ReconcilePaymentStatusAction)->handle(
            $payment,
            $transaction,
            'Payment gateway returned a mismatched payment response.',
        );
    }

    private function existingPayment(Order $order, ?User $actor, ?string $guestToken): ?Payment
    {
        return DB::transaction(function () use ($order, $actor, $guestToken): ?Payment {
            $payment = Payment::query()
                ->where('payable_type', $order->getMorphClass())
                ->where('payable_id', $order->getKey())
                ->lockForUpdate()
                ->first();
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            $this->authorize($lockedOrder, $actor, $guestToken);

            return $payment;
        }, attempts: 3);
    }

    private function advertisedPaymentMethod(
        PaymentGateway $gateway,
        PaymentMethod $requestedPaymentMethod,
        ?string $requestedProviderCode,
    ): PaymentMethodData {
        $requestedProviderCode = trim($requestedProviderCode ?? $requestedPaymentMethod->value);

        foreach ($gateway->paymentMethods() as $method) {
            if (
                $method instanceof PaymentMethodData
                && $method->paymentMethod === $requestedPaymentMethod
                && strcasecmp(trim($method->providerCode), $requestedProviderCode) === 0
            ) {
                return $method;
            }
        }

        throw new InvalidArgumentException('Unsupported payment method/provider combination.');
    }

    private function createPaymentRecord(
        Order $order,
        PaymentGatewayDriver $driver,
        PaymentMethodData $method,
        ?User $actor,
        ?string $guestToken,
    ): Payment {
        return DB::transaction(function () use ($order, $driver, $method, $actor, $guestToken): Payment {
            $existingPayment = Payment::query()
                ->where('payable_type', $order->getMorphClass())
                ->where('payable_id', $order->getKey())
                ->lockForUpdate()
                ->first();
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            $this->authorize($lockedOrder, $actor, $guestToken);

            if ($existingPayment !== null) {
                return $existingPayment;
            }

            $amount = (int) round((float) $lockedOrder->total);
            $fee = ($this->paymentFeeCalculator ?? new PaymentFeeCalculator)
                ->calculateCustomerFee($method, $amount);

            $lockedOrder->update(['payment_fee' => $fee]);

            return Payment::query()->create([
                'driver' => $driver->value,
                'payable_type' => $lockedOrder->getMorphClass(),
                'payable_id' => $lockedOrder->getKey(),
                'order_id' => (string) Str::uuid(),
                'transaction_id' => null,
                'payment_type' => $method->type,
                'account_number' => '',
                'channel' => $method->providerCode,
                'expired_at' => $driver === PaymentGatewayDriver::KaruhunQris
                    ? now()->addMinutes(15)
                    : now()->addDay(),
                'amount' => $amount,
                'fee' => $fee,
                'total' => $amount + $fee,
            ]);
        }, attempts: 3);
    }

    private function storedPaymentMethod(Payment $payment): ?PaymentMethod
    {
        if ($payment->driver === PaymentGatewayDriver::KaruhunQris->value) {
            return PaymentMethod::Qris;
        }

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

    private function ensureUsableDestination(PaymentTransactionData $transaction): void
    {
        if (blank($transaction->paymentNumber) && blank($transaction->paymentUrl)) {
            throw new UnexpectedValueException('Payment gateway returned no usable payment destination.');
        }
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
