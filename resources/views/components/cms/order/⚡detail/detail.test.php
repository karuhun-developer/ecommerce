<?php

use App\Models\Location\Location;
use App\Models\Order\Order;
use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopItem;
use App\Models\Product\Product;
use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Models\User;
use App\Services\BiteshipService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

use function Pest\Laravel\mock;

function createCmsOrderDetailOrder(User $owner, string $reference, int $quantity = 1): array
{
    $buyer = User::factory()->create();
    $destination = Location::factory()->for($buyer)->create();
    $shop = Shop::factory()->for($owner)->create();
    Location::factory()->for($owner)->create([
        'shop_id' => $shop->id,
        'type' => 'origin',
    ]);
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
    ]);
    $order = Order::factory()->for($buyer)->create([
        'location_id' => $destination->id,
        'reference' => $reference,
        'status' => true,
    ]);
    $orderShop = OrderShop::factory()->for($order)->for($shop)->create();
    $item = OrderShopItem::factory()->create([
        'order_id' => $order->id,
        'order_shop_id' => $orderShop->id,
        'product_flat_id' => $productFlat->id,
        'product_data' => [
            'name' => $product->name,
            'description' => $product->description,
            'weight' => $product->weight,
        ],
        'quantity' => $quantity,
    ]);

    return compact('order', 'orderShop', 'item');
}

it('registers the cms.order.detail component', function () {
    expect(Livewire::exists('cms.order.detail'))->toBeTrue();
});

it('returns not found for another tenants order component', function () {
    $owner = User::factory()->create();
    $foreignOwner = User::factory()->create();
    $foreign = createCmsOrderDetailOrder($foreignOwner, 'FOREIGN-COMPONENT-ORDER');
    $this->actingAs($owner);

    expect(fn () => Livewire::test('cms.order.detail', ['orderShopId' => $foreign['orderShop']->id]))
        ->toThrow(ModelNotFoundException::class);
});

it('returns not found for another tenants Folio order page', function () {
    $owner = User::factory()->create();
    $foreignOwner = User::factory()->create();
    $foreign = createCmsOrderDetailOrder($foreignOwner, 'FOREIGN-FOLIO-ORDER');
    $this->actingAs($owner);

    $this->get(route('cms.order.show', ['id' => $foreign['orderShop']->id]))
        ->assertNotFound();
});

it('prevents the order shop id from being changed during hydration', function () {
    $owner = User::factory()->create();
    $owned = createCmsOrderDetailOrder($owner, 'LOCKED-DETAIL-ORDER');
    $foreign = createCmsOrderDetailOrder(User::factory()->create(), 'LOCKED-FOREIGN-ORDER');
    $this->actingAs($owner);

    $component = Livewire::test('cms.order.detail', ['orderShopId' => $owned['orderShop']->id]);

    expect(fn () => $component->set('orderShopId', $foreign['orderShop']->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('rejects a mismatched shipping event id before calling the provider', function () {
    $owner = User::factory()->create();
    $owned = createCmsOrderDetailOrder($owner, 'MATCHED-DETAIL-ORDER');
    $foreign = createCmsOrderDetailOrder(User::factory()->create(), 'MISMATCHED-DETAIL-ORDER');
    $this->actingAs($owner);

    mock(BiteshipService::class)->shouldNotReceive('createOrder');

    Livewire::test('cms.order.detail', ['orderShopId' => $owned['orderShop']->id])
        ->call('kirimPesanan', $foreign['orderShop']->id)
        ->assertDispatched(
            'toast',
            type: 'error',
            message: 'Gagal mengirim pesanan. Silakan coba lagi.',
        );
});

it('renders the persisted item quantity', function () {
    $owner = User::factory()->create();
    $owned = createCmsOrderDetailOrder($owner, 'QUANTITY-DETAIL-ORDER', 37);
    $this->actingAs($owner);

    Livewire::test('cms.order.detail', ['orderShopId' => $owned['orderShop']->id])
        ->assertSeeInOrder(['Kuantitas', '37']);
});
