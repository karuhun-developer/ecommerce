<?php

use App\Actions\Ecommerce\Payment\CreatePaymentAction;
use App\Actions\Ecommerce\Payment\RefreshPaymentStatusAction;
use App\Enums\PaymentGatewayDriver;
use App\Mail\OrderPaid;
use App\Mail\OrderPaymentFailed;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Models\User;
use Bayurifkialghifari\DynamicQris\Exceptions\DynamicQrisException;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-12T05:00:00Z'));
    Http::preventStrayRequests();
    Mail::fake();
    config()->set([
        'payment.default' => 'karuhunqris',
        'dynamic-qris.api_key' => 'merchant-api-key',
        'dynamic-qris.base_url' => 'https://qris.karuhundeveloper.com',
        'dynamic-qris.timeout' => 10,
        'dynamic-qris.connect_timeout' => 3,
    ]);
    $this->qrisResponse = [
        'success' => true,
        'data' => [
            'id' => 'qris-reference',
            'token' => 'private-payment-token',
            'requested_amount' => 100_000,
            'amount' => 100_123,
            'unique_code' => 123,
            'creation_fee' => 100,
            'qris_string' => '000201010212'.str_repeat('1234567890', 30),
            'provider' => 'gopay',
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
        ],
    ];
    $this->statusResponse = [
        'success' => true,
        'paid' => false,
        'status' => 'pending',
        'provider' => 'gopay',
        'data' => [
            'token' => 'private-payment-token',
            'amount' => 100_123,
            'provider' => 'gopay',
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
        ],
    ];
});

function pendingKaruhunQrisPayment(array $attributes = []): Payment
{
    $order = Order::factory()->create(['total' => 100_000, 'status' => false, 'payment_fee' => 123]);

    return Payment::factory()->create(array_merge([
        'driver' => PaymentGatewayDriver::KaruhunQris->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'transaction_id' => 'qris-reference',
        'provider_token' => 'private-payment-token',
        'payment_type' => 'qris',
        'channel' => 'gopay',
        'account_number' => '000201010212',
        'amount' => 100_000,
        'fee' => 123,
        'total' => 100_123,
        'paid_at' => null,
        'expired_at' => now()->addMinutes(15),
    ], $attributes));
}

it('creates QRIS through the SDK with encrypted tokens and the final unique-code amount', function (string $provider) {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->getKey(), 'total' => 100_000]);
    $response = $this->qrisResponse;
    $response['data']['provider'] = $provider;
    Http::fake(['https://qris.karuhundeveloper.com/v1/qris/create' => Http::response($response)]);

    $payment = app(CreatePaymentAction::class)->handle($order, 'qris', $user, providerCode: $provider);

    expect($payment->driver)->toBe('karuhunqris')
        ->and($payment->channel)->toBe($provider)
        ->and($payment->provider_token)->toBe('private-payment-token')
        ->and($payment->getRawOriginal('provider_token'))->not->toContain('private-payment-token')
        ->and($payment->toArray())->not->toHaveKey('provider_token')
        ->and($payment->account_number)->toBe($response['data']['qris_string'])
        ->and((int) $payment->total)->toBe(100_123)
        ->and((int) $payment->fee)->toBe(123)
        ->and((int) $order->refresh()->payment_fee)->toBe(123)
        ->and($payment->expired_at->equalTo(now()->addMinutes(15)))->toBeTrue()
        ->and(Activity::query()->get()->pluck('properties')->toJson())->not->toContain('private-payment-token');

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-api-key', 'merchant-api-key')
        && $request->hasHeader('Idempotency-Key', $payment->order_id)
        && $request['amount'] === 100_000
        && $request['provider'] === $provider);
    app(CreatePaymentAction::class)->handle($order, 'qris', $user, providerCode: $provider);
    Http::assertSentCount(1);
})->with(['gopay', 'shopee']);

it('retries an uncertain create request with its original idempotency key and deadline', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->getKey(), 'total' => 100_000]);
    $response = $this->qrisResponse;
    $response['data']['expires_at'] = now()->addMinutes(20)->toIso8601String();
    Http::fakeSequence()->pushFailedConnection()->push($response);

    expect(fn () => app(CreatePaymentAction::class)->handle($order, 'qris', $user, providerCode: 'gopay'))
        ->toThrow(DynamicQrisException::class);
    $originalPayment = Payment::query()->sole();
    $this->travel(5)->minutes();

    $payment = app(CreatePaymentAction::class)->handle($order, 'qris', $user, providerCode: 'gopay');

    expect($payment->is($originalPayment))->toBeTrue()
        ->and(Payment::query()->count())->toBe(1)
        ->and($payment->expired_at->equalTo($originalPayment->created_at->addMinutes(15)))->toBeTrue();
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Idempotency-Key', $originalPayment->order_id));
});

it('honors shorter provider expiry and seconds-only expiry', function (array $expiry, int $seconds) {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->getKey(), 'total' => 100_000]);
    $response = $this->qrisResponse;
    unset($response['data']['expires_at']);
    $response['data'] = array_merge($response['data'], $expiry);
    Http::fake(['*' => Http::response($response)]);

    $payment = app(CreatePaymentAction::class)->handle($order, 'qris', $user, providerCode: 'gopay');

    expect($payment->expired_at->equalTo(now()->addSeconds($seconds)))->toBeTrue();
})->with([
    'provider deadline' => [['expires_at' => '2026-11-12T05:02:00Z'], 120],
    'provider seconds' => [['expires_in_seconds' => 60], 60],
    'local fallback' => [[], 900],
]);

it('checks pending payments without treating request success as payment success', function () {
    $payment = pendingKaruhunQrisPayment();
    $response = $this->statusResponse;
    Http::fake(function () use ($response) {
        expect(DB::transactionLevel())->toBe(0);

        return Http::response($response);
    });

    app(RefreshPaymentStatusAction::class)->handle($payment);
    app(RefreshPaymentStatusAction::class)->handle($payment);

    expect($payment->refresh()->paid_at)->toBeNull()
        ->and($payment->last_checked_at)->not->toBeNull()
        ->and($payment->payable->status)->toBeFalse();
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://qris.karuhundeveloper.com/v1/payment/check'
        && $request['token'] === 'private-payment-token'
        && $request['wait'] === false);
    Http::assertSentCount(1);
    Mail::assertNothingSent();
    $this->travel(15)->seconds();
    app(RefreshPaymentStatusAction::class)->handle($payment);
    Http::assertSentCount(2);
});

it('confirms an on-time payment after the deadline and notifies the order once', function () {
    $payment = pendingKaruhunQrisPayment(['expired_at' => now()->subMinute()]);
    $paidAt = now()->subMinutes(2);
    $response = $this->statusResponse;
    $response['paid'] = true;
    $response['status'] = 'success';
    $response['transaction'] = ['transactionId' => 'gopay-payment-reference', 'amount' => 100_123, 'timestamp' => $paidAt->timestamp];
    Http::fake(['*' => Http::response($response)]);

    app(RefreshPaymentStatusAction::class)->handle($payment);
    app(RefreshPaymentStatusAction::class)->handle($payment);

    expect($payment->refresh()->paid_at->equalTo($paidAt))->toBeTrue()
        ->and($payment->transaction_id)->toBe('qris-reference')
        ->and($payment->provider_payment_id)->toBe('gopay-payment-reference')
        ->and($payment->payable->status)->toBeTrue();
    Http::assertSentCount(1);
    Mail::assertSent(OrderPaid::class, 1);
});

it('rejects mismatched or inconsistent status responses without fulfilling an order', function (string $field, mixed $value) {
    $payment = pendingKaruhunQrisPayment();
    $response = $this->statusResponse;
    data_set($response, $field, $value);
    Http::fake(['*' => Http::response($response)]);

    expect(fn () => app(RefreshPaymentStatusAction::class)->handle($payment))->toThrow(UnexpectedValueException::class);
    expect($payment->refresh()->paid_at)->toBeNull()
        ->and($payment->last_checked_at)->toBeNull()
        ->and($payment->payable->status)->toBeFalse();
    Mail::assertNothingSent();
})->with([
    'wrong token' => ['data.token', 'another-token'],
    'wrong amount' => ['data.amount', 1],
    'wrong provider' => ['provider', 'shopee'],
    'paid flag only' => ['paid', true],
    'invalid paid flag' => ['paid', 'false'],
    'status only' => ['status', 'success'],
    'unknown status' => ['status', 'failed'],
]);

it('keeps expiry reconciliation pending when the API fails and retries on the next scheduler run', function () {
    $payment = pendingKaruhunQrisPayment(['expired_at' => now()->subMinute()]);
    Http::fakeSequence()->push(['error' => 'temporarily unavailable'], 503)->push($this->statusResponse);

    $this->artisan('payments:reconcile-qris')->assertFailed();
    expect($payment->refresh()->account_code)->toBeNull()
        ->and($payment->last_checked_at)->toBeNull();
    Mail::assertNothingSent();

    $this->artisan('payments:reconcile-qris')->assertSuccessful();
    expect($payment->refresh()->account_code)->toBe('provider-status:expired')
        ->and($payment->payable->status)->toBeFalse();
    Mail::assertSent(OrderPaymentFailed::class, 1);
    $this->artisan('payments:reconcile-qris')->assertSuccessful();
    Http::assertSentCount(2);
});

it('stops checking provider-confirmed expired payments before the local deadline', function () {
    $payment = pendingKaruhunQrisPayment();
    $response = $this->statusResponse;
    $response['status'] = 'expired';
    Http::fake(['*' => Http::response($response)]);

    app(RefreshPaymentStatusAction::class)->handle($payment);
    $this->travel(15)->seconds();
    app(RefreshPaymentStatusAction::class)->handle($payment);

    expect($payment->refresh()->account_code)->toBe('provider-status:expired');
    Http::assertSentCount(1);
    Mail::assertSent(OrderPaymentFailed::class, 1);
});

it('retains a confirmed payment after its deadline for reconciliation without fulfilling the order', function () {
    $payment = pendingKaruhunQrisPayment(['expired_at' => now()->subMinute()]);
    $response = $this->statusResponse;
    $response['status'] = 'success';
    $response['paid'] = true;
    $response['transaction'] = ['transactionId' => 'late-payment', 'amount' => 100_123, 'timestamp' => now()->timestamp];
    Http::fake(['*' => Http::response($response)]);

    expect(fn () => app(RefreshPaymentStatusAction::class)->handle($payment))->toThrow(UnexpectedValueException::class);
    expect($payment->refresh()->paid_at)->toBeNull()
        ->and($payment->account_code)->toBeNull()
        ->and($payment->payable->status)->toBeFalse();
    Mail::assertNothingSent();
});

it('does not overlap a payment check already held by polling or the scheduler', function () {
    $payment = pendingKaruhunQrisPayment();
    $lock = Cache::lock('qris-payment-check:'.$payment->getKey(), 30);
    $lock->get();

    try {
        app(RefreshPaymentStatusAction::class)->handle($payment);
        Http::assertNothingSent();
    } finally {
        $lock->release();
    }
});

it('renders QRIS for an authorized customer with 15-second polling and no private token', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->getKey(), 'total' => 100_000]);
    Http::fake(['*' => Http::response($this->qrisResponse)]);
    Exceptions::fake();

    $component = Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertSee('QRIS GoPay')
        ->assertSee('QRIS ShopeePay')
        ->set('paymentMethod', 'gopay')
        ->call('submit')
        ->assertHasNoErrors();

    Exceptions::assertNothingReported();
    expect($component->instance()->payment->payment_type)->toBe('qris')
        ->and($component->instance()->payment->account_number)->toBe($this->qrisResponse['data']['qris_string'])
        ->and($component->instance()->qrisDataUri())->not->toBeNull();
    $component
        ->assertSee('100.123')
        ->assertSee('data:image/svg+xml;base64,', false)
        ->assertSee('wire:poll.15s="refreshPaymentStatus"', false)
        ->assertDontSee('private-payment-token');
});
