<?php

use App\Contracts\Payments\PaymentGateway;
use App\Enums\PaymentGatewayDriver;
use App\Services\Payments\KaruhunQrisPaymentGateway;
use App\Services\Payments\MidtransPaymentGateway;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaywuzPaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('selects the configured gateway without retaining stale configuration', function () {
    $manager = app(PaymentGatewayManager::class);

    config()->set('payment.default', 'midtrans');

    expect($manager->defaultDriver())->toBe(PaymentGatewayDriver::Midtrans)
        ->and($manager->driver())->toBeInstanceOf(MidtransPaymentGateway::class);

    config()->set('payment.default', 'paywuz');

    expect($manager->defaultDriver())->toBe(PaymentGatewayDriver::Paywuz)
        ->and($manager->driver())->toBeInstanceOf(PaywuzPaymentGateway::class)
        ->and(app(PaymentGateway::class))->toBeInstanceOf(PaywuzPaymentGateway::class);

    config()->set('payment.default', 'karuhunqris');

    expect($manager->defaultDriver())->toBe(PaymentGatewayDriver::KaruhunQris)
        ->and($manager->driver())->toBeInstanceOf(KaruhunQrisPaymentGateway::class)
        ->and(app(PaymentGateway::class))->toBeInstanceOf(KaruhunQrisPaymentGateway::class);
});

it('rejects unsupported configured gateways', function () {
    config()->set('payment.default', 'unsupported');

    expect(fn () => app(PaymentGatewayManager::class)->driver())
        ->toThrow(InvalidArgumentException::class, 'Unsupported payment gateway [unsupported].');
});
