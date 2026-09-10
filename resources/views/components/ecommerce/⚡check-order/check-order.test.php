<?php

use App\Models\Order\Order;
use Livewire\Livewire;

it('registers the ecommerce.check-order component', function () {
    expect(Livewire::exists('ecommerce.check-order'))->toBeTrue();
});

it('redirects a guest order lookup using only its reference', function () {
    $order = Order::factory()->guest()->create([
        'access_token' => str_repeat('a', 64),
    ]);
    $detailUrl = route('orders.detail', [
        'reference' => $order->reference,
        'token' => $order->access_token,
    ]);

    Livewire::test('ecommerce.check-order')
        ->set('reference', $order->reference)
        ->call('check')
        ->assertRedirect($detailUrl);

    $this->get($detailUrl)->assertOk();
});
