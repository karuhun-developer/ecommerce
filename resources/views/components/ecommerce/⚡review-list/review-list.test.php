<?php

use App\Models\Order\Order;
use App\Models\Order\OrderReview;
use App\Models\Order\OrderShop;
use App\Models\Product\Product;
use App\Models\Shop\Shop;
use App\Models\User;
use Livewire\Livewire;

it('registers the ecommerce review list component', function () {
    expect(Livewire::exists('ecommerce.review-list'))->toBeTrue();
});

it('requires authentication on the review list page and component', function () {
    $this->get(route('account.reviews'))->assertRedirect(route('login'));

    Livewire::test('ecommerce.review-list')->assertForbidden();
});

it('only lists reviews owned by the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $ownedOrderShop = OrderShop::factory()->for(Order::factory()->for($user))->for($shop)->create();
    $foreignOrderShop = OrderShop::factory()->for(Order::factory()->for($otherUser))->for($shop)->create();

    OrderReview::query()->create([
        'user_id' => $user->id,
        'order_shop_id' => $ownedOrderShop->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $product->id,
        'rating' => 5,
        'comment' => 'visible-owned-review',
        'status' => 'approved',
    ]);
    OrderReview::query()->create([
        'user_id' => $otherUser->id,
        'order_shop_id' => $foreignOrderShop->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $product->id,
        'rating' => 4,
        'comment' => 'hidden-foreign-review',
        'status' => 'approved',
    ]);

    Livewire::actingAs($user)
        ->test('ecommerce.review-list')
        ->assertSee('visible-owned-review')
        ->assertDontSee('hidden-foreign-review');
});
