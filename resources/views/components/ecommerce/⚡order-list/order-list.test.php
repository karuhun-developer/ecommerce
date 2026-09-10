<?php

use App\Models\Order\Order;
use App\Models\User;
use Livewire\Livewire;

it('registers the ecommerce order list component', function () {
    expect(Livewire::exists('ecommerce.order-list'))->toBeTrue();
});

it('requires authentication on the order list page and component', function () {
    $this->get(route('orders.index'))->assertRedirect(route('login'));

    Livewire::test('ecommerce.order-list')->assertForbidden();
});

it('only lists orders owned by the authenticated user', function () {
    $user = User::factory()->create();
    $ownedOrder = Order::factory()->for($user)->create(['reference' => 'OWNED-ORDER-LIST']);
    $foreignOrder = Order::factory()->create(['reference' => 'FOREIGN-ORDER-LIST']);
    $guestOrder = Order::factory()->guest()->create(['reference' => 'GUEST-ORDER-LIST']);

    Livewire::actingAs($user)
        ->test('ecommerce.order-list')
        ->assertSee($ownedOrder->reference)
        ->assertDontSee($foreignOrder->reference)
        ->assertDontSee($guestOrder->reference);
});
