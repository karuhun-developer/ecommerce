<?php

use App\Actions\Ecommerce\Payment\CreatePaymentAction;
use App\Models\Order\Order;
use App\Models\User;
use Livewire\Livewire;

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

it('only renders guest payment with the correct access token', function () {
    $order = Order::factory()->guest()->create([
        'access_token' => str_repeat('a', 64),
    ]);

    Livewire::test('ecommerce.payment', ['order' => $order])
        ->assertNotFound();

    Livewire::withQueryParams(['token' => str_repeat('b', 64)])
        ->test('ecommerce.payment', ['order' => $order])
        ->assertNotFound();

    Livewire::withQueryParams(['token' => $order->access_token])
        ->test('ecommerce.payment', ['order' => $order])
        ->assertOk();
});

it('does not expose provider failures to customers', function () {
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
});
