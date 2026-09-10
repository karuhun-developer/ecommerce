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
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\mock;

function createCmsOrderTableOrder(User $owner, string $reference, int $quantity = 3): array
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

it('registers the cms.order.table component', function () {
    expect(Livewire::exists('cms.order.table'))->toBeTrue();
});

it('shows only order shops accessible to the current tenant', function () {
    $owner = User::factory()->create();
    $foreignOwner = User::factory()->create();
    $owned = createCmsOrderTableOrder($owner, 'OWNED-ORDER-REFERENCE');
    $foreign = createCmsOrderTableOrder($foreignOwner, 'FOREIGN-ORDER-REFERENCE');

    $this->actingAs($owner);

    Livewire::test('cms.order.table')
        ->assertSee($owned['order']->reference)
        ->assertDontSee($foreign['order']->reference);
});

it('does not ship an order shop from another tenant', function () {
    $owner = User::factory()->create();
    $foreignOwner = User::factory()->create();
    $foreign = createCmsOrderTableOrder($foreignOwner, 'FOREIGN-SHIP-ORDER');
    $this->actingAs($owner);

    mock(BiteshipService::class)->shouldNotReceive('createOrder');

    Livewire::test('cms.order.table')
        ->call('kirimPesanan', $foreign['orderShop']->id)
        ->assertDispatched(
            'toast',
            type: 'error',
            message: 'Gagal mengirim pesanan. Silakan coba lagi.',
        );

    expect($foreign['orderShop']->fresh()->waybill_number)->toBeNull();
});

it('does not expose provider exception details', function () {
    $owner = User::factory()->create();
    $owned = createCmsOrderTableOrder($owner, 'PROVIDER-ERROR-ORDER');
    $this->actingAs($owner);

    mock(BiteshipService::class)
        ->shouldReceive('createOrder')
        ->once()
        ->andThrow(new RuntimeException('provider-secret-detail'));

    Livewire::test('cms.order.table')
        ->call('kirimPesanan', $owned['orderShop']->id)
        ->assertDispatched(
            'toast',
            type: 'error',
            message: 'Gagal mengirim pesanan. Silakan coba lagi.',
        )
        ->assertDontSee('provider-secret-detail');
});

it('does not call the provider when required shipping data is incomplete', function () {
    $owner = User::factory()->create();
    $missingOrigin = createCmsOrderTableOrder($owner, 'MISSING-ORIGIN');
    $missingDestination = createCmsOrderTableOrder($owner, 'MISSING-DESTINATION');
    $missingCourier = createCmsOrderTableOrder($owner, 'MISSING-COURIER');
    $this->actingAs($owner);

    Location::query()->where('shop_id', $missingOrigin['orderShop']->shop_id)->delete();
    $missingDestination['order']->location()->update(['address' => null]);
    $missingCourier['orderShop']->update(['shipping_data' => ['price' => 10_000]]);

    mock(BiteshipService::class)->shouldNotReceive('createOrder');

    foreach ([$missingOrigin, $missingDestination, $missingCourier] as $fixture) {
        Livewire::test('cms.order.table')
            ->call('kirimPesanan', $fixture['orderShop']->id)
            ->assertDispatched(
                'toast',
                type: 'error',
                message: 'Gagal mengirim pesanan. Silakan coba lagi.',
            );
    }

    $this->assertDatabaseCount('order_shop_shipments', 0);
});

it('ships once with canonical quantity and calls the provider outside its persistence transaction', function () {
    $owner = User::factory()->create();
    $owned = createCmsOrderTableOrder($owner, 'IDEMPOTENT-SHIP-ORDER', 7);
    $this->actingAs($owner);

    $baselineTransactionLevel = DB::transactionLevel();
    $providerTransactionLevel = null;
    $providerPayload = null;

    mock(BiteshipService::class)
        ->shouldReceive('createOrder')
        ->once()
        ->andReturnUsing(function (array $payload) use (&$providerPayload, &$providerTransactionLevel): array {
            $providerPayload = $payload;
            $providerTransactionLevel = DB::transactionLevel();

            return [
                'id' => 'provider-order-1',
                'status' => 'allocated',
                'courier' => [
                    'tracking_id' => 'tracking-1',
                    'waybill_id' => 'waybill-1',
                    'name' => 'JNE',
                    'company' => 'jne',
                    'type' => 'reg',
                ],
            ];
        });

    Livewire::test('cms.order.table')
        ->call('kirimPesanan', $owned['orderShop']->id)
        ->call('kirimPesanan', $owned['orderShop']->id);

    expect($providerTransactionLevel)->toBe($baselineTransactionLevel)
        ->and($providerPayload['items'][0]['quantity'])->toBe(7)
        ->and($owned['orderShop']->fresh()->waybill_number)->toBe('waybill-1');

    $this->assertDatabaseCount('order_shop_shipments', 1);
    $this->assertDatabaseHas('order_shop_shipments', [
        'order_shop_id' => $owned['orderShop']->id,
        'event' => 'create_order',
        'courier_waybill_id' => 'waybill-1',
    ]);
});
