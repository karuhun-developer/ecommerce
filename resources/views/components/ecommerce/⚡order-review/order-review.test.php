<?php

use App\Actions\Ecommerce\Review\SubmitOrderReviewAction;
use App\Models\Order\Order;
use App\Models\Order\OrderReview;
use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopItem;
use App\Models\Product\Product;
use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

use function Pest\Laravel\mock;

function createEcommerceOrderReviewFixture(?User $buyer = null): array
{
    $buyer ??= User::factory()->create();
    $owner = User::factory()->create();
    $shop = Shop::factory()->for($owner)->create();
    $product = Product::factory()->for($shop)->create();
    $forgedProduct = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
    ]);
    $order = Order::factory()->for($buyer)->create(['status' => true]);
    $orderShop = OrderShop::factory()->for($order)->for($shop)->create([
        'shipping_status' => true,
        'waybill_number' => 'delivered-waybill',
    ]);
    $item = OrderShopItem::factory()->create([
        'order_id' => $order->id,
        'order_shop_id' => $orderShop->id,
        'product_flat_id' => $productFlat->id,
        'product_data' => [
            'product_id' => $forgedProduct->id,
            'name' => 'Snapshot product',
            'weight' => 100,
        ],
    ]);

    return compact('buyer', 'shop', 'product', 'forgedProduct', 'orderShop', 'item');
}

it('registers the ecommerce.order-review component', function () {
    expect(Livewire::exists('ecommerce.order-review'))->toBeTrue();
});

it('prevents a buyer from mounting another buyers order shop', function () {
    $fixture = createEcommerceOrderReviewFixture();
    $this->actingAs(User::factory()->create());

    expect(fn () => Livewire::test('ecommerce.order-review', ['orderShop' => $fixture['orderShop']]))
        ->toThrow(ModelNotFoundException::class);
});

it('prevents the order shop model from being replaced during hydration', function () {
    $fixture = createEcommerceOrderReviewFixture();
    $foreign = createEcommerceOrderReviewFixture();
    $this->actingAs($fixture['buyer']);

    $component = Livewire::test('ecommerce.order-review', ['orderShop' => $fixture['orderShop']]);

    expect(fn () => $component->set('orderShop', $foreign['orderShop']))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('rejects invalid review ratings', function (float $rating) {
    $fixture = createEcommerceOrderReviewFixture();
    $this->actingAs($fixture['buyer']);
    $itemKey = 'shopitem__'.$fixture['item']->id;

    Livewire::test('ecommerce.order-review', ['orderShop' => $fixture['orderShop']])
        ->set("reviewData.{$itemKey}.rating", $rating)
        ->call('submit')
        ->assertHasErrors("reviewData.{$itemKey}.rating");

    expect(OrderReview::query()->count())->toBe(0);
})->with([
    'below minimum' => 0,
    'above maximum' => 5.5,
    'not a half step' => 4.3,
]);

it('stores half-step ratings against canonical product and shop targets', function () {
    $fixture = createEcommerceOrderReviewFixture();
    $this->actingAs($fixture['buyer']);
    $itemKey = 'shopitem__'.$fixture['item']->id;

    Livewire::test('ecommerce.order-review', ['orderShop' => $fixture['orderShop']])
        ->set("reviewData.{$itemKey}.rating", 4.5)
        ->call('submit')
        ->assertDispatched(
            'toast',
            type: 'success',
            message: 'Ulasan berhasil dikirim! Menunggu persetujuan admin.',
        );

    $this->assertDatabaseHas('order_reviews', [
        'user_id' => $fixture['buyer']->id,
        'order_shop_id' => $fixture['orderShop']->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $fixture['product']->id,
        'rating' => 4.5,
    ]);
    $this->assertDatabaseHas('order_reviews', [
        'user_id' => $fixture['buyer']->id,
        'order_shop_id' => $fixture['orderShop']->id,
        'reviewable_type' => Shop::class,
        'reviewable_id' => $fixture['shop']->id,
    ]);
    $this->assertDatabaseMissing('order_reviews', [
        'reviewable_type' => Product::class,
        'reviewable_id' => $fixture['forgedProduct']->id,
    ]);
});

it('rejects duplicate reviews for the same order target', function () {
    $fixture = createEcommerceOrderReviewFixture();
    $itemKey = 'shopitem__'.$fixture['item']->id;
    $shopKey = 'shop__'.$fixture['shop']->id;
    $data = [
        $itemKey => ['rating' => 4.5, 'comment' => 'Good product'],
        $shopKey => ['rating' => 5, 'comment' => 'Good shop'],
    ];
    $images = [$itemKey => [], $shopKey => []];
    $action = app(SubmitOrderReviewAction::class);

    $action->handle($fixture['orderShop'], $data, $images, $fixture['buyer']);

    expect(fn () => $action->handle($fixture['orderShop'], $data, $images, $fixture['buyer']))
        ->toThrow(ValidationException::class)
        ->and(OrderReview::query()->count())->toBe(2);
});

it('rejects direct review submission for another buyers order', function () {
    $fixture = createEcommerceOrderReviewFixture();
    $itemKey = 'shopitem__'.$fixture['item']->id;

    expect(fn () => app(SubmitOrderReviewAction::class)->handle(
        $fixture['orderShop'],
        [$itemKey => ['rating' => 5, 'comment' => null]],
        [$itemKey => []],
        User::factory()->create(),
    ))->toThrow(ModelNotFoundException::class);
});

it('does not expose internal review submission exceptions', function () {
    $fixture = createEcommerceOrderReviewFixture();
    $this->actingAs($fixture['buyer']);

    mock(SubmitOrderReviewAction::class)
        ->shouldReceive('handle')
        ->once()
        ->andThrow(new RuntimeException('review-secret-detail'));

    Livewire::test('ecommerce.order-review', ['orderShop' => $fixture['orderShop']])
        ->call('submit')
        ->assertDispatched(
            'toast',
            type: 'error',
            message: 'Ulasan gagal dikirim. Silakan coba lagi.',
        )
        ->assertDontSee('review-secret-detail');
});
