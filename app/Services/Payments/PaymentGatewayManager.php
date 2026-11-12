<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Enums\PaymentGatewayDriver;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function defaultDriver(): PaymentGatewayDriver
    {
        $driver = config('payment.default');

        if (! is_string($driver) || PaymentGatewayDriver::tryFrom($driver) === null) {
            $name = is_scalar($driver) ? (string) $driver : get_debug_type($driver);

            throw new InvalidArgumentException("Unsupported payment gateway [{$name}].");
        }

        return PaymentGatewayDriver::from($driver);
    }

    public function driver(PaymentGatewayDriver|string|null $driver = null): PaymentGateway
    {
        if ($driver === null) {
            $driver = $this->defaultDriver();
        } elseif (is_string($driver)) {
            $resolvedDriver = PaymentGatewayDriver::tryFrom($driver);

            if ($resolvedDriver === null) {
                throw new InvalidArgumentException("Unsupported payment gateway [{$driver}].");
            }

            $driver = $resolvedDriver;
        }

        return match ($driver) {
            PaymentGatewayDriver::Midtrans => $this->container->make(MidtransPaymentGateway::class),
            PaymentGatewayDriver::Paywuz => $this->container->make(PaywuzPaymentGateway::class),
            PaymentGatewayDriver::KaruhunQris => $this->container->make(KaruhunQrisPaymentGateway::class),
        };
    }
}
