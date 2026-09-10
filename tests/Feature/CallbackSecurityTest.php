<?php

use App\Actions\Api\V1\Callback\HandleBiteshipCallbackAction;
use App\Mail\OrderDelivered;
use App\Mail\OrderPaid;
use App\Mail\OrderPaymentFailed;
use App\Models\Order\Order;
use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopShipment;
use App\Models\Payment\Payment;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;

uses(DatabaseMigrations::class);

it('handles duplicate Midtrans settlement callbacks once', function () {
    Mail::fake();
    config(['midtrans.server_key' => 'midtrans-test-secret']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'midtrans-order-123',
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);

    $payload = [
        'order_id' => $payment->order_id,
        'status_code' => '200',
        'gross_amount' => '118000.00',
        'transaction_status' => 'settlement',
    ];
    $payload['signature_key'] = hash(
        'sha512',
        $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'midtrans-test-secret',
    );

    $this->postJson(route('api.v1.midtrans.callback'), $payload)->assertOk();
    $this->postJson(route('api.v1.midtrans.callback'), $payload)->assertOk();

    expect($payment->refresh()->paid_at)->not->toBeNull()
        ->and($order->refresh()->status)->toBeTrue();

    Mail::assertSent(OrderPaid::class, 1);
    Mail::assertNotSent(OrderPaymentFailed::class);
});

it('deduplicates Biteship delivery callbacks and sends one email after commit', function () {
    Mail::fake();
    config([
        'services.biteship.webhook.header_key' => 'X-Biteship-Webhook-Secret',
        'services.biteship.webhook.header_secret' => 'biteship-test-secret',
    ]);

    $orderShop = OrderShop::factory()->create([
        'shipping_status' => false,
    ]);
    OrderShopShipment::factory()->create([
        'order_shop_id' => $orderShop->getKey(),
        'provider_event_key' => null,
        'courier_waybill_id' => 'WAYBILL-123',
        'courier_tracking_id' => 'TRACKING-123',
        'status' => 'allocated',
    ]);

    $payload = [
        'event' => 'order.status',
        'status' => 'delivered',
        'courier_waybill_id' => 'WAYBILL-123',
        'courier_tracking_id' => 'TRACKING-123',
        'courier_company' => 'jne',
        'courier_type' => 'reg',
    ];

    $this->withHeader('X-Biteship-Webhook-Secret', 'biteship-test-secret')
        ->postJson(route('api.v1.biteship.callback'), $payload)
        ->assertOk();

    $this->withHeader('X-Biteship-Webhook-Secret', 'biteship-test-secret')
        ->postJson(route('api.v1.biteship.callback'), array_reverse($payload, true))
        ->assertOk();

    expect($orderShop->refresh()->shipping_status)->toBeTrue()
        ->and(OrderShopShipment::query()->where('order_shop_id', $orderShop->getKey())->count())->toBe(2)
        ->and(OrderShopShipment::query()->whereNotNull('provider_event_key')->count())->toBe(1);

    Mail::assertSent(OrderDelivered::class, 1);
});

it('rejects Biteship callbacks when webhook authentication is incomplete', function (?string $headerKey, ?string $headerSecret, bool $sendHeader) {
    config([
        'services.biteship.webhook.header_key' => $headerKey,
        'services.biteship.webhook.header_secret' => $headerSecret,
    ]);

    $action = Mockery::mock(HandleBiteshipCallbackAction::class);
    $action->shouldNotReceive('handle');
    $this->app->instance(HandleBiteshipCallbackAction::class, $action);

    $request = $sendHeader && $headerKey
        ? $this->withHeader($headerKey, 'biteship-test-secret')
        : $this;

    $request->postJson(route('api.v1.biteship.callback'), ['event' => 'order.status'])
        ->assertUnauthorized();
})->with([
    'missing configured header key' => [null, 'biteship-test-secret', false],
    'missing configured secret' => ['X-Biteship-Webhook-Secret', null, true],
    'missing request header' => ['X-Biteship-Webhook-Secret', 'biteship-test-secret', false],
]);
