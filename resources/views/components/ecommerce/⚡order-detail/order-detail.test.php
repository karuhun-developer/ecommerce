<?php

use App\Models\Order\Order;
use App\Models\User;
use Livewire\Livewire;

it('registers the ecommerce order detail component', function () {
    expect(Livewire::exists('ecommerce.order-detail'))->toBeTrue();
});

it('only renders a registered order for its owner', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $order = Order::factory()->for($owner)->create();

    Livewire::actingAs($otherUser)
        ->test('ecommerce.order-detail', ['order' => $order])
        ->assertNotFound();

    Livewire::actingAs($owner)
        ->test('ecommerce.order-detail', ['order' => $order])
        ->assertOk();
});

it('only renders a guest order with the correct access token', function () {
    $order = Order::factory()->guest()->create([
        'access_token' => str_repeat('a', 64),
    ]);

    Livewire::test('ecommerce.order-detail', ['order' => $order])
        ->assertNotFound();

    Livewire::withQueryParams(['token' => str_repeat('b', 64)])
        ->test('ecommerce.order-detail', ['order' => $order])
        ->assertNotFound();

    Livewire::withQueryParams(['token' => $order->access_token])
        ->test('ecommerce.order-detail', ['order' => $order])
        ->assertOk();
});
