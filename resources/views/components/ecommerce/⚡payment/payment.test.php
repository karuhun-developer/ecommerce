<?php

use App\Actions\Ecommerce\Payment\CreatePaymentAction;
use App\Actions\Ecommerce\Payment\RefreshPaymentStatusAction;
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
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentTransactionNotFoundException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function bindPaymentPageMethods(array $methods): void
{
    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('paymentMethods')->once()->andReturn($methods);

    $gatewayManager = Mockery::mock(PaymentGatewayManager::class);
    $gatewayManager->shouldReceive('driver')->once()->withNoArgs()->andReturn($gateway);

    app()->instance(PaymentGatewayManager::class, $gatewayManager);
}

it('registers the ecommerce payment component', function () {
    expect(Livewire::exists('ecommerce.payment'))->toBeTrue();
});

it('only renders payment for the registered order owner', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $order = Order::factory()->for($owner)->create();

    Livewire::actingAs($otherUser)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertNotFound();

    Livewire::actingAs($owner)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertOk();
});

it('only renders guest payment with the correct access token without exposing it', function () {
    $order = Order::factory()->guest()->create([
        'access_token' => 'valid-guest-token',
    ]);

    Livewire::test('ecommerce.payment', ['order' => $order])
        ->assertNotFound();

    Livewire::withQueryParams(['token' => 'invalid-guest-token'])
        ->test('ecommerce.payment', ['order' => $order])
        ->assertNotFound();

    Livewire::withQueryParams(['token' => $order->access_token])
        ->test('ecommerce.payment', ['order' => $order])
        ->assertOk()
        ->assertDontSee($order->access_token, true, false);
});

it('authorizes guest payment creation without storing the access token in component output', function () {
    $order = Order::factory()->guest()->create([
        'access_token' => 'guest-submit-token',
    ]);
    bindPaymentPageMethods([
        new PaymentMethodData(PaymentMethod::Qris, 'QRIS', 'QRIS', 'qris'),
    ]);

    $action = Mockery::mock(CreatePaymentAction::class);
    $action->shouldReceive('handle')
        ->once()
        ->withArgs(fn (Order $submittedOrder, string $method, ?User $actor, ?string $guestToken, ?string $providerCode): bool => $submittedOrder->is($order)
            && $method === PaymentMethod::Qris->value
            && $actor === null
            && $guestToken === $order->access_token
            && $providerCode === 'QRIS')
        ->andReturnUsing(fn (): Payment => Payment::factory()->for($order, 'payable')->create([
            'payment_type' => 'qris',
            'channel' => PaymentMethod::Qris->value,
            'account_number' => 'https://payments.example.test/qr/guest.png',
        ]));
    $this->app->instance(CreatePaymentAction::class, $action);

    Livewire::withQueryParams(['token' => $order->access_token])
        ->test('ecommerce.payment', ['order' => $order])
        ->set('paymentMethod', 'QRIS')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDontSee($order->access_token, true, false);
});

it('renders configured provider methods with estimates local icons and a generic fallback', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => 100_001]);

    bindPaymentPageMethods([
        new PaymentMethodData(PaymentMethod::Qris, 'QRIS', 'QRIS', 'qris', flatFee: 290, percentageFeeBasisPoints: 70, totalFee: 290),
        new PaymentMethodData(PaymentMethod::Va, 'CIMBVA', 'CIMB Niaga Virtual Account', 'virtual_account', flatFee: 3_500, totalFee: 1),
        new PaymentMethodData(PaymentMethod::Va, 'BSIVA', 'BSI Virtual Account', 'virtual_account', flatFee: 2_500),
        new PaymentMethodData(PaymentMethod::Va, 'MYSTERY', 'Provider Lain', 'hosted', flatFee: 1_250),
    ]);

    $component = Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertSet('paymentMethods.0.estimatedFee', 991)
        ->assertSet('paymentMethods.0.estimatedTotal', 100_992)
        ->assertSet('paymentMethods.1.estimatedFee', 3_500)
        ->assertSet('paymentMethods.2.estimatedFee', 2_500)
        ->assertSet('paymentMethods.3.estimatedFee', 1_250)
        ->assertSee('CIMB Niaga Virtual Account')
        ->assertSee('BSI Virtual Account')
        ->assertSee('Provider Lain')
        ->assertSee('payment-icon/CIMB.svg', false)
        ->assertSee('payment-icon/BSI.svg', false)
        ->assertSeeHtml('data-payment-icon-fallback');

    expect(substr_count($component->html(), 'name="payment_method"'))->toBe(4)
        ->and(substr_count($component->html(), 'wire:model.live="paymentMethod"'))->toBe(4);
});

it('calculates customer fees from normalized provider metadata', function (
    int $amount,
    bool $feeByMerchant,
    array $feeTiers,
    int $expectedFee,
) {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => $amount]);
    bindPaymentPageMethods([
        new PaymentMethodData(
            PaymentMethod::Qris,
            'QRIS',
            'QRIS',
            'qris',
            flatFee: 290,
            percentageFeeBasisPoints: 70,
            feeByMerchant: $feeByMerchant,
            feeTiers: $feeTiers,
        ),
    ]);

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertSet('paymentMethods.0.estimatedFee', $expectedFee)
        ->assertSet('paymentMethods.0.estimatedTotal', $amount + $expectedFee);
})->with([
    'merchant pays the provider fee' => [100_001, true, [], 0],
    'lower QRIS tier' => [50_000, false, [
        ['minimumAmount' => null, 'maximumAmount' => 149_999, 'flatFee' => 290, 'percentageFeeBasisPoints' => 70],
        ['minimumAmount' => 150_000, 'maximumAmount' => null, 'flatFee' => 0, 'percentageFeeBasisPoints' => 95],
    ], 640],
    'highest matching QRIS tier at an overlapping boundary' => [150_000, false, [
        ['minimumAmount' => null, 'maximumAmount' => 150_000, 'flatFee' => 290, 'percentageFeeBasisPoints' => 70],
        ['minimumAmount' => 150_000, 'maximumAmount' => null, 'flatFee' => 0, 'percentageFeeBasisPoints' => 95],
    ], 1_425],
]);

it('maps all supported local provider icons without remote bank assets', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    bindPaymentPageMethods([
        new PaymentMethodData(PaymentMethod::Qris, 'QRIS', 'QRIS', 'qris'),
        new PaymentMethodData(PaymentMethod::Bca, 'BCAVA', 'BCA', 'virtual_account'),
        new PaymentMethodData(PaymentMethod::Bni, 'BNIVA', 'BNI', 'virtual_account'),
        new PaymentMethodData(PaymentMethod::Bri, 'BRIVA', 'BRI', 'virtual_account'),
        new PaymentMethodData(PaymentMethod::Va, 'MANDIRIVA', 'Mandiri', 'virtual_account'),
        new PaymentMethodData(PaymentMethod::Va, 'PERMATAVA', 'Permata', 'virtual_account'),
    ]);

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertSee('payment-icon/QRIS.svg', false)
        ->assertSee('payment-icon/BCA.svg', false)
        ->assertSee('payment-icon/BNI.svg', false)
        ->assertSee('payment-icon/BRI.svg', false)
        ->assertSee('payment-icon/MANDIRI.svg', false)
        ->assertSee('payment-icon/PERMATA.svg', false);
});

it('validates a selected provider code against the offered methods', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    bindPaymentPageMethods([
        new PaymentMethodData(PaymentMethod::Qris, 'QRIS', 'QRIS', 'qris'),
    ]);

    $action = Mockery::mock(CreatePaymentAction::class);
    $action->shouldNotReceive('handle');
    $this->app->instance(CreatePaymentAction::class, $action);

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->set('paymentMethod', 'FORGED')
        ->call('submit')
        ->assertHasErrors(['paymentMethod']);
});

it('rejects an offered method outside the provider amount limits', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => 100_000]);
    bindPaymentPageMethods([
        new PaymentMethodData(
            PaymentMethod::Va,
            'LIMITEDVA',
            'Limited Virtual Account',
            'virtual_account',
            minimumAmount: 200_000,
        ),
    ]);

    $action = Mockery::mock(CreatePaymentAction::class);
    $action->shouldNotReceive('handle');
    $this->app->instance(CreatePaymentAction::class, $action);

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertSee('Tidak tersedia untuk nominal pesanan ini')
        ->set('paymentMethod', 'LIMITEDVA')
        ->call('submit')
        ->assertHasErrors(['paymentMethod']);
});

it('submits the normalized method with the exact provider code and then displays persisted amounts', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => 100_000]);
    bindPaymentPageMethods([
        new PaymentMethodData(PaymentMethod::Va, 'CIMBVA', 'CIMB Niaga Virtual Account', 'virtual_account', totalFee: 3_500),
    ]);

    $action = Mockery::mock(CreatePaymentAction::class);
    $action->shouldReceive('handle')
        ->once()
        ->withArgs(fn (Order $submittedOrder, string $method, ?User $actor, ?string $guestToken, ?string $providerCode): bool => $submittedOrder->is($order)
            && $method === PaymentMethod::Va->value
            && $actor?->is($user)
            && $guestToken === null
            && $providerCode === 'CIMBVA')
        ->andReturnUsing(fn (): Payment => Payment::factory()->for($order, 'payable')->create([
            'driver' => PaymentGatewayDriver::Paywuz->value,
            'payment_type' => 'bank_transfer',
            'channel' => PaymentMethod::Va->value,
            'account_number' => '880812345678',
            'amount' => 100_000,
            'fee' => 3_500,
            'total' => 103_500,
        ]));
    $this->app->instance(CreatePaymentAction::class, $action);

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->set('paymentMethod', 'CIMBVA')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Rp 100.000')
        ->assertSee('Rp 3.500')
        ->assertSee('Rp 103.500')
        ->assertDontSeeHtml('data-payment-methods')
        ->assertDontSeeHtml('data-payment-mobile-cta');
});

it('keeps selection and action adjacent with responsive sticky summaries and mobile clearance', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertSeeHtml('data-payment-layout')
        ->assertSeeHtml('data-payment-methods')
        ->assertSeeHtml('data-payment-primary-cta')
        ->assertSeeHtml('data-payment-summary')
        ->assertSeeHtml('data-payment-mobile-cta')
        ->assertSee('lg:grid-cols-[minmax(0,1fr)_22rem]', false)
        ->assertSee('lg:sticky', false)
        ->assertSee('fixed', false)
        ->assertSee('pb-28', false);
});

it('shows QR instructions with image download and open actions first', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    Payment::factory()->for($order, 'payable')->create([
        'payment_type' => 'qris',
        'channel' => 'qris',
        'account_number' => '00020101021226670016COM.NOBUBANK.WWW01189360050300000879140214567890123456780303UMI51440014ID.CO.QRIS.WWW0215ID20253748405600303UMI52045411530336054061000005802ID5913TOKO CONTOH6007JAKARTA6304ABCD',
        'expired_at' => now()->addHour(),
    ]);

    $component = Livewire::actingAs($user)->test('ecommerce.payment', ['order' => $order])
        ->assertSeeHtml('data-payment-instructions')
        ->assertSee('Instruksi pembayaran #'.$order->reference)
        ->assertSee('Ikuti instruksi pembayaran dan selesaikan transaksi sebelum batas waktu.')
        ->assertDontSee('Pilih metode yang tersedia dan selesaikan pembayaran dengan aman.')
        ->assertSeeHtml('data-payment-qr')
        ->assertSeeHtml('data-payment-qr-download')
        ->assertSeeHtml('data-payment-qr-open')
        ->assertSeeHtml('data:image/svg+xml;base64,')
        ->assertDontSeeHtml('src="https://payments.example.test')
        ->assertSeeHtml('data-payment-countdown')
        ->assertSee('Menunggu pembayaran')
        ->assertSee('Batas pembayaran')
        ->assertSeeHtml('data-payment-order-details');

    expect(strpos($component->html(), 'data-payment-instructions'))
        ->toBeLessThan(strpos($component->html(), 'data-payment-order-details'));
});

it('never renders a hosted QRIS payment URL as an image source', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    $hostedUrl = 'https://pay.example.test/qris/hosted-transaction';

    Payment::factory()->for($order, 'payable')->create([
        'payment_type' => 'qris',
        'channel' => 'QRIS',
        'account_number' => $hostedUrl,
    ]);

    $component = Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertSeeHtml('data-payment-qr-hosted-open')
        ->assertSee($hostedUrl, false);

    expect($component->html())->not->toContain('src="'.$hostedUrl.'"');
});

it('shows virtual account provider code and payment total copy actions without leaking access data', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'access_token' => 'never-render-this-owner-token',
    ]);
    Payment::factory()->for($order, 'payable')->create([
        'payment_type' => 'bank_transfer',
        'channel' => 'bca',
        'account_number' => '880812345678',
        'account_code' => 'BANK-CODE-123',
        'amount' => 100_000,
        'fee' => 3_500,
        'total' => 103_500,
    ]);

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertSee('880812345678')
        ->assertSee('BANK-CODE-123')
        ->assertSeeHtml('data-payment-va-copy')
        ->assertSeeHtml('data-payment-code-copy')
        ->assertSeeHtml('data-payment-total-copy')
        ->assertSeeHtml('aria-label="Salin total pembayaran"')
        ->assertSeeHtml('aria-label="Salin nomor virtual account"')
        ->assertSeeHtml('aria-label="Salin kode pembayaran"')
        ->assertSeeHtml('data-copy-value="103500"')
        ->assertSee('async copy(value, target)', false)
        ->assertSee('await navigator.clipboard.writeText(value)', false)
        ->assertSee('catch (error)', false)
        ->assertSee('Gagal menyalin ${target}. Silakan salin secara manual.', false)
        ->assertSeeHtml('data-payment-copy-status')
        ->assertSeeHtml('role="status"')
        ->assertSeeHtml('aria-live="polite"')
        ->assertSeeHtml('aria-atomic="true"')
        ->assertSee('x-bind:aria-label', false)
        ->assertDontSee($order->access_token, true, false);
});

it('shows the hosted payment action when the destination is a URL', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    Payment::factory()->for($order, 'payable')->create([
        'payment_type' => 'bank_transfer',
        'channel' => 'va',
        'account_number' => 'https://pay.example.test/transaction/123',
    ]);

    $component = Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertSeeHtml('data-payment-hosted-open')
        ->assertSee('Buka halaman pembayaran');

    expect($component->instance()->paymentDestinationUrl())->toBe('https://pay.example.test/transaction/123')
        ->and($component->html())->toContain('href="https://pay.example.test/transaction/123"');
});

it('hides payment actions instructions destinations and deadlines for terminal states', function (string $status) {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    $destination = 'https://pay.example.test/terminal-payment';
    $payment = Payment::factory()->for($order, 'payable')->create([
        'payment_type' => 'bank_transfer',
        'channel' => 'va',
        'account_number' => $destination,
        'account_code' => 'TERMINAL-CODE',
        'paid_at' => $status === 'paid' ? now() : null,
        'expired_at' => $status === 'expired' ? now()->subMinute() : now()->addHour(),
    ]);

    if ($status === 'expired') {
        $refreshPaymentStatus = Mockery::mock(app(RefreshPaymentStatusAction::class));
        $refreshPaymentStatus->shouldReceive('handle')
            ->once()
            ->withArgs(fn (Payment $candidate): bool => $candidate->is($payment))
            ->andReturn($payment);
        $this->app->instance(RefreshPaymentStatusAction::class, $refreshPaymentStatus);
    }

    $component = Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertSeeHtml('data-payment-terminal-state')
        ->assertSeeHtml('data-payment-order-details')
        ->assertSeeHtml('data-payment-summary')
        ->assertSee('lg:grid-cols-[minmax(0,1fr)_22rem]', false)
        ->assertSee('lg:sticky', false)
        ->assertDontSeeHtml('data-payment-instructions')
        ->assertDontSeeHtml('data-payment-total-copy')
        ->assertDontSeeHtml('data-payment-qr')
        ->assertDontSeeHtml('data-payment-qr-download')
        ->assertDontSeeHtml('data-payment-qr-open')
        ->assertDontSeeHtml('data-payment-qr-hosted-open')
        ->assertDontSeeHtml('data-payment-qr-copy')
        ->assertDontSeeHtml('data-payment-hosted-open')
        ->assertDontSeeHtml('data-payment-va-copy')
        ->assertDontSeeHtml('data-payment-code-copy')
        ->assertDontSeeHtml('data-payment-copy-status')
        ->assertDontSeeHtml('data-payment-countdown')
        ->assertDontSee('Batas pembayaran')
        ->assertDontSee('Ikuti petunjuk utama di bawah ini.')
        ->assertDontSee('Total yang perlu dibayar')
        ->assertDontSee('async copy(value, target)', false);

    expect($component->html(true))
        ->not->toContain('href="'.$destination.'"')
        ->not->toContain('TERMINAL-CODE');
})->with([
    'paid payment' => ['paid'],
    'expired payment' => ['expired'],
]);

it('rejects insecure hosted payment destinations from primary actions', function (string $paymentType, string $hostedAction): void {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    $destination = 'http://pay.example.test/transaction/insecure';
    Payment::factory()->for($order, 'payable')->create([
        'payment_type' => $paymentType,
        'channel' => $paymentType === 'qris' ? 'QRIS' : 'va',
        'account_number' => $destination,
    ]);

    $component = Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertDontSeeHtml($hostedAction);

    expect($component->instance()->paymentDestinationUrl())->toBeNull()
        ->and($component->html())->not->toContain('href="'.$destination.'"');
})->with([
    'hosted transfer' => ['bank_transfer', 'data-payment-hosted-open'],
    'hosted QRIS' => ['qris', 'data-payment-qr-hosted-open'],
]);

it('polls pending payments and delegates refresh through the stored driver', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    $payment = Payment::factory()->for($order, 'payable')->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'order_id' => 'pending-provider-order',
        'transaction_id' => 'pending-provider-transaction',
        'paid_at' => null,
        'expired_at' => now()->addHour(),
    ]);
    Mail::fake();

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('paymentStatus')
        ->once()
        ->with('pending-provider-order')
        ->andReturn(new PaymentTransactionData(
            id: 'pending-provider-transaction',
            orderId: 'pending-provider-order',
            amount: (int) $payment->amount,
            totalPayment: (int) $payment->total,
            paymentMethod: PaymentMethod::Bca,
            paymentNumber: '880812345678',
            paymentUrl: null,
            status: PaymentStatus::Paid,
        ));
    $gatewayManager = Mockery::mock(PaymentGatewayManager::class);
    $gatewayManager->shouldReceive('driver')
        ->once()
        ->with(PaymentGatewayDriver::Paywuz)
        ->andReturn($gateway);
    $this->app->instance(PaymentGatewayManager::class, $gatewayManager);

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertSee('wire:poll.15s="refreshPaymentStatus"', false)
        ->call('refreshPaymentStatus')
        ->assertSet('isPaid', true)
        ->assertDontSee('wire:poll.15s="refreshPaymentStatus"', false);
});

it('does not poll a paid payment', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    Payment::factory()->for($order, 'payable')->create([
        'paid_at' => now(),
        'expired_at' => now()->addHour(),
    ]);

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertSee('Dibayar')
        ->assertDontSee('wire:poll.15s="refreshPaymentStatus"', false);
});

it('performs one final refresh for a locally expired payment without enabling polling', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    $payment = Payment::factory()->for($order, 'payable')->create([
        'paid_at' => null,
        'expired_at' => now()->subMinute(),
    ]);
    $refreshPaymentStatus = Mockery::mock(app(RefreshPaymentStatusAction::class));
    $refreshPaymentStatus->shouldReceive('handle')
        ->once()
        ->withArgs(fn (Payment $candidate): bool => $candidate->is($payment))
        ->andReturn($payment);
    $this->app->instance(RefreshPaymentStatusAction::class, $refreshPaymentStatus);

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertSet('hasPerformedFinalExpiryRefresh', true)
        ->assertSee('Gagal atau kedaluwarsa')
        ->assertDontSee('wire:poll.15s="refreshPaymentStatus"', false)
        ->call('refreshPaymentStatus')
        ->assertSet('hasPerformedFinalExpiryRefresh', true);
});

it('lets an authoritative paid refresh override stale local expiry', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    $payment = Payment::factory()->for($order, 'payable')->create([
        'paid_at' => null,
        'expired_at' => now()->subMinute(),
    ]);
    $refreshPaymentStatus = Mockery::mock(app(RefreshPaymentStatusAction::class));
    $refreshPaymentStatus->shouldReceive('handle')
        ->once()
        ->withArgs(fn (Payment $candidate): bool => $candidate->is($payment))
        ->andReturnUsing(function (Payment $candidate): Payment {
            $candidate->forceFill(['paid_at' => now()])->save();

            return $candidate->refresh();
        });
    $this->app->instance(RefreshPaymentStatusAction::class, $refreshPaymentStatus);

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertSet('hasPerformedFinalExpiryRefresh', true)
        ->assertSet('isPaid', true)
        ->assertSee('Pembayaran selesai #'.$order->reference)
        ->assertDontSee('Pilih metode yang tersedia dan selesaikan pembayaran dengan aman.')
        ->assertSeeHtml('data-payment-terminal-state')
        ->assertDontSeeHtml('data-payment-instructions')
        ->assertDontSeeHtml('data-payment-total-copy')
        ->assertDontSeeHtml('data-payment-countdown')
        ->assertDontSee('Batas pembayaran')
        ->assertDontSee('wire:poll.15s="refreshPaymentStatus"', false)
        ->call('refreshPaymentStatus')
        ->assertSet('isPaid', true);
});

it('handles a nullable expiry without starting a countdown interval', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    Payment::factory()->for($order, 'payable')->create([
        'paid_at' => null,
        'expired_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->assertOk()
        ->assertSee('wire:poll.15s="refreshPaymentStatus"', false)
        ->assertDontSeeHtml('data-payment-countdown');
});

it('does not expose provider failures to customers', function () {
    Exceptions::fake();
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    $action = Mockery::mock(CreatePaymentAction::class);
    $action->shouldReceive('handle')
        ->once()
        ->andThrow(new RuntimeException('provider-secret-error'));
    $this->app->instance(CreatePaymentAction::class, $action);

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->set('paymentMethod', 'qris')
        ->call('submit')
        ->assertDispatched(
            'toast',
            type: 'error',
            message: 'Pembayaran belum dapat diproses. Silakan coba lagi.',
        )
        ->assertDontSee('provider-secret-error');

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'provider-secret-error');
});

it('retries a persisted transaction-less payment after a failed submit and remount', function () {
    Exceptions::fake();

    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => 100_000]);
    $paymentMethods = [
        new PaymentMethodData(PaymentMethod::Va, 'CIMBVA', 'CIMB Niaga Virtual Account', 'virtual_account'),
        new PaymentMethodData(PaymentMethod::Va, 'BSIVA', 'BSI Virtual Account', 'virtual_account'),
    ];
    $createAttempts = 0;

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('paymentMethods')->times(3)->andReturn($paymentMethods);
    $gateway->shouldReceive('paymentStatus')
        ->twice()
        ->andThrow(new PaymentTransactionNotFoundException);
    $gateway->shouldReceive('createPayment')
        ->twice()
        ->withArgs(function (CreatePaymentData $data) use (&$createAttempts): bool {
            $createAttempts++;

            expect($data->paymentMethod)->toBe(PaymentMethod::Va)
                ->and($data->providerCode)->toBe('CIMBVA')
                ->and($data->amount)->toBe(100_000);

            return true;
        })
        ->andReturnUsing(function (CreatePaymentData $data) use (&$createAttempts): PaymentTransactionData {
            if ($createAttempts === 1) {
                throw new RuntimeException('temporary-provider-failure');
            }

            return new PaymentTransactionData(
                id: 'retry-transaction-id',
                orderId: $data->orderId,
                amount: 100_000,
                totalPayment: 103_400,
                paymentMethod: PaymentMethod::Va,
                paymentNumber: '880812345678',
                paymentUrl: null,
                status: PaymentStatus::Pending,
                providerCode: 'CIMBVA',
            );
        });

    $gatewayManager = Mockery::mock(PaymentGatewayManager::class);
    $gatewayManager->shouldReceive('defaultDriver')->once()->andReturn(PaymentGatewayDriver::Paywuz);
    $gatewayManager->shouldReceive('driver')->andReturnUsing(
        function (?PaymentGatewayDriver $driver = null) use ($gateway): PaymentGateway {
            if ($driver !== null) {
                expect($driver)->toBe(PaymentGatewayDriver::Paywuz);
            }

            return $gateway;
        },
    );
    app()->instance(PaymentGatewayManager::class, $gatewayManager);

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order])
        ->set('paymentMethod', 'CIMBVA')
        ->call('submit')
        ->assertDispatched('toast', type: 'error', message: 'Pembayaran belum dapat diproses. Silakan coba lagi.');

    $persistedPayment = Payment::query()->sole();

    expect($persistedPayment->transaction_id)->toBeNull()
        ->and($persistedPayment->driver)->toBe(PaymentGatewayDriver::Paywuz->value)
        ->and($persistedPayment->channel)->toBe('CIMBVA');

    Livewire::actingAs($user)
        ->test('ecommerce.payment', ['order' => $order->refresh()])
        ->assertSet('paymentMethod', 'CIMBVA')
        ->assertSet('paymentMethods.0.providerCode', 'CIMBVA')
        ->assertCount('paymentMethods', 1)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('880812345678');

    expect(Payment::query()->count())->toBe(1)
        ->and($persistedPayment->refresh()->transaction_id)->toBe('retry-transaction-id');

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'temporary-provider-failure');
});
