<?php

use App\Actions\Ecommerce\Checkout\ResolveShopGroupsAction;
use App\Actions\Ecommerce\Checkout\StoreCheckoutAction;
use App\Actions\Ecommerce\Shipping\GetShippingRatesAction;
use App\Data\Checkout\CartData;
use App\Data\Checkout\CheckoutData;
use App\Data\Checkout\ShippingRatesData;
use App\Models\Location\Location;
use App\Models\Order\Order;
use App\Models\Product\Product;
use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Models\User;
use App\Services\BiteshipService;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Sqids\Sqids;

it('registers the ecommerce.checkout.checkout component', function () {
    expect(Livewire::exists('ecommerce.checkout.checkout'))->toBeTrue();
});

it('binds checkout product image alt text to the actual product name', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create(['shop_id' => $shop->id]);

    Livewire::test('ecommerce.checkout.checkout', [
        'selectedIds' => (new Sqids)->encode([$productFlat->id]),
    ])
        ->call('resolveShopGroups', [['id' => $productFlat->id, 'qty' => 1]])
        ->assertSee('x-bind:alt="item.name"', false)
        ->assertDontSee('alt="Product"', false);
});

it('resolves only selected cart items across shops', function () {
    $firstShop = Shop::factory()->create();
    $secondShop = Shop::factory()->create();
    $firstProduct = Product::factory()->for($firstShop)->create();
    $secondProduct = Product::factory()->for($secondShop)->create();
    $selectedFlat = ProductFlat::factory()->for($firstProduct)->create(['shop_id' => $firstShop->id]);
    $unselectedFlat = ProductFlat::factory()->for($firstProduct)->create(['shop_id' => $firstShop->id]);
    $otherSelectedFlat = ProductFlat::factory()->for($secondProduct)->create(['shop_id' => $secondShop->id]);

    $groups = app(ResolveShopGroupsAction::class)->handle(CartData::fromArray([
        ['id' => $selectedFlat->id, 'qty' => 2],
        ['id' => $unselectedFlat->id, 'qty' => 3],
        ['id' => $otherSelectedFlat->id, 'qty' => 4],
    ], [$selectedFlat->id, $otherSelectedFlat->id]));

    expect($groups)->toHaveCount(2)
        ->and($groups[0]['items'])->toBe([$selectedFlat->id => 2])
        ->and($groups[1]['items'])->toBe([$otherSelectedFlat->id => 4]);
});

it('resolves no checkout items when selected ids are missing or malformed', function (mixed $selectedIds) {
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $flat = ProductFlat::factory()->for($product)->create(['shop_id' => $shop->id]);

    Livewire::test('ecommerce.checkout.checkout', ['selectedIds' => $selectedIds])
        ->call('resolveShopGroups', [['id' => $flat->id, 'qty' => 2]])
        ->assertSet('shopGroups', []);
})->with([
    'missing token' => [''],
    'malformed token' => ['invalid!'],
    'non-string token' => [['invalid']],
]);

it('resolves no checkout items for a stale selected id', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $flat = ProductFlat::factory()->for($product)->create(['shop_id' => $shop->id]);
    $staleToken = (new Sqids)->encode([$flat->id + 100_000]);

    Livewire::test('ecommerce.checkout.checkout', ['selectedIds' => $staleToken])
        ->call('resolveShopGroups', [['id' => $flat->id, 'qty' => 2]])
        ->assertSet('shopGroups', []);
});

it('renders only selected items when the same shop has unselected cart entries', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $selectedFlat = ProductFlat::factory()->for($product)->create(['shop_id' => $shop->id]);
    $unselectedFlat = ProductFlat::factory()->for($product)->create(['shop_id' => $shop->id]);
    $selectedToken = (new Sqids)->encode([$selectedFlat->id]);

    Livewire::test('ecommerce.checkout.checkout', ['selectedIds' => $selectedToken])
        ->call('resolveShopGroups', [
            ['id' => $selectedFlat->id, 'qty' => 2],
            ['id' => $unselectedFlat->id, 'qty' => 4],
        ])
        ->assertSet('shopGroups.0.items', [$selectedFlat->id => 2])
        ->assertSee('shopItems(', false)
        ->assertDontSee("i.shop_id == {$shop->id}", false);
});

it('dispatches only purchased cart ids after a successful checkout', function () {
    Mail::fake();

    $user = User::factory()->create();
    $location = Location::factory()->for($user)->create();
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $selectedFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'price' => 10_000,
        'stock' => 10,
    ]);
    $unselectedFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'stock' => 10,
    ]);
    $checkout = Order::factory()->for($user)->create(['access_token' => null]);
    $selectedRate = [
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'price' => 5_000,
        'name' => 'JNE Regular',
        'etd' => '2 days',
    ];

    $shippingRatesAction = Mockery::mock(GetShippingRatesAction::class);
    $shippingRatesAction->shouldReceive('handle')->once()->andReturn([$selectedRate]);
    app()->instance(GetShippingRatesAction::class, $shippingRatesAction);

    $storeCheckoutAction = Mockery::mock(StoreCheckoutAction::class);
    $storeCheckoutAction->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(function (CheckoutData $data) use ($selectedFlat, $shop): bool {
            return $data->shops[0]->shopId === $shop->id && $data->shops[0]->items[0]->productFlatId === $selectedFlat->id;
        }), $user)
        ->andReturn($checkout);
    app()->instance(StoreCheckoutAction::class, $storeCheckoutAction);

    Livewire::actingAs($user)
        ->test('ecommerce.checkout.checkout', [
            'selectedIds' => (new Sqids)->encode([$selectedFlat->id]),
        ])
        ->call('resolveShopGroups', [
            ['id' => $selectedFlat->id, 'qty' => 2],
            ['id' => $unselectedFlat->id, 'qty' => 1],
        ])
        ->set('form.selectedLocationId', $location->id)
        ->set('form.shopRates', [$shop->id => $selectedRate])
        ->call('submit', null)
        ->assertDispatched('remove-cart-items', ids: [$selectedFlat->id])
        ->assertNotDispatched('delete-localstorage');
});

it('logs checkout storage failures without guest PII or raw submitted data', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'price' => 10_000,
        'stock' => 10,
        'status' => true,
    ]);
    $selectedRate = [
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'price' => 5_000,
        'name' => 'JNE Regular',
        'etd' => '2 days',
    ];
    $guestData = [
        'contact_name' => 'Sensitive Guest Name',
        'contact_phone' => '081234567890',
        'email' => 'sensitive-guest@example.test',
        'address' => 'Sensitive Guest Street 123',
        'note' => 'Sensitive delivery note',
        'postal_code' => '12345',
        'area_string' => 'Sensitive Area',
        'biteship_area_id' => 'IDNP6IDNC148IDND1198IDZ12950',
        'latitude' => -6.2,
        'longitude' => 106.8,
    ];

    $shippingRatesAction = Mockery::mock(GetShippingRatesAction::class);
    $shippingRatesAction->shouldReceive('handle')->once()->andReturn([$selectedRate]);
    app()->instance(GetShippingRatesAction::class, $shippingRatesAction);

    $storeCheckoutAction = Mockery::mock(StoreCheckoutAction::class);
    $storeCheckoutAction->shouldReceive('handle')
        ->once()
        ->andThrow(new RuntimeException('Provider leaked sensitive-guest@example.test'));
    app()->instance(StoreCheckoutAction::class, $storeCheckoutAction);

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context) use ($shop, $productFlat, $guestData): bool {
            $encodedLog = json_encode([$message, $context], JSON_THROW_ON_ERROR);

            return $message === 'Failed to store order.'
                && $context === [
                    'checkout_actor' => 'guest',
                    'shop_ids' => [$shop->id],
                    'product_flat_ids' => [$productFlat->id],
                    'exception' => RuntimeException::class,
                ]
                && ! str_contains($encodedLog, 'Provider leaked')
                && collect($guestData)->every(
                    fn (mixed $value): bool => ! is_scalar($value) || ! str_contains($encodedLog, (string) $value),
                );
        });

    Livewire::test('ecommerce.checkout.checkout', [
        'selectedIds' => (new Sqids)->encode([$productFlat->id]),
    ])
        ->call('resolveShopGroups', [['id' => $productFlat->id, 'qty' => 2]])
        ->set('form.shopRates', [$shop->id => $selectedRate])
        ->call('submit', $guestData)
        ->assertDispatched('toast', type: 'error', message: 'Gagal membuat order. Silakan coba lagi.')
        ->assertNotDispatched('delete-localstorage');
});

it('logs shipping lookup failures without guest PII or raw submitted data', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'price' => 10_000,
        'stock' => 10,
        'status' => true,
    ]);
    $selectedRate = [
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'price' => 5_000,
        'name' => 'JNE Regular',
        'etd' => '2 days',
    ];
    $guestData = [
        'contact_name' => 'Sensitive Shipping Name',
        'contact_phone' => '089876543210',
        'email' => 'sensitive-shipping@example.test',
        'address' => 'Sensitive Shipping Street 456',
        'note' => 'Sensitive shipping note',
        'postal_code' => '54321',
        'area_string' => 'Sensitive Shipping Area',
        'biteship_area_id' => 'IDNP6IDNC148IDND1198IDZ12950',
        'latitude' => -6.2,
        'longitude' => 106.8,
    ];

    $shippingRatesAction = Mockery::mock(GetShippingRatesAction::class);
    $shippingRatesAction->shouldReceive('handle')
        ->once()
        ->andThrow(new RuntimeException('Provider leaked sensitive-shipping@example.test'));
    app()->instance(GetShippingRatesAction::class, $shippingRatesAction);

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context) use ($shop, $productFlat, $guestData): bool {
            $encodedLog = json_encode([$message, $context], JSON_THROW_ON_ERROR);

            return $message === 'Failed to get shipping rates.'
                && $context === [
                    'shop_id' => $shop->id,
                    'product_flat_ids' => [$productFlat->id],
                    'exception' => RuntimeException::class,
                ]
                && ! str_contains($encodedLog, 'Provider leaked')
                && collect($guestData)->every(
                    fn (mixed $value): bool => ! is_scalar($value) || ! str_contains($encodedLog, (string) $value),
                );
        });

    Livewire::test('ecommerce.checkout.checkout', [
        'selectedIds' => (new Sqids)->encode([$productFlat->id]),
    ])
        ->call('resolveShopGroups', [['id' => $productFlat->id, 'qty' => 2]])
        ->set('form.shopRates', [$shop->id => $selectedRate])
        ->call('submit', $guestData)
        ->assertDispatched(
            'toast',
            type: 'error',
            message: "Gagal mendapatkan tarif pengiriman untuk toko {$shop->name}.",
        );
});

it('preserves unrelated cart entries during exact upsert and repeated selective removal', function () {
    $script = <<<'JS'
const storage = new Map([
    ['cart', JSON.stringify([
        { id: 10, name: 'Unrelated', qty: 7 },
        { id: 20, name: 'Old selected', qty: 2 },
    ])],
]);
let initializeAlpine;
const stores = new Map();

globalThis.localStorage = {
    getItem: (key) => storage.get(key) ?? null,
    setItem: (key, value) => storage.set(key, value),
};
globalThis.document = {
    addEventListener: (name, callback) => {
        if (name === 'alpine:init') initializeAlpine = callback;
    },
};
globalThis.Alpine = {
    store(name, value) {
        if (arguments.length === 2) stores.set(name, value);

        return stores.get(name);
    },
};

await import('./resources/js/cart.js');
initializeAlpine();

const cart = Alpine.store('cart');
cart.upsert({ id: 20, name: 'Exact selected', qty: 4 });
cart.removeMany([20]);
cart.removeMany([20]);

console.log(JSON.stringify(cart.items));
JS;

    $result = Process::path(base_path())->run(['node', '--input-type=module', '--eval', $script]);

    if ($result->failed()) {
        $this->fail($result->errorOutput() ?: $result->output());
    }

    expect(trim($result->output()))->toBe('[{"id":10,"name":"Unrelated","qty":7}]');
});

it('recovers from malformed cart local storage', function () {
    $script = <<<'JS'
const storage = new Map([['cart', '{malformed']]);
let initializeAlpine;
const stores = new Map();

globalThis.localStorage = {
    getItem: (key) => storage.get(key) ?? null,
    setItem: (key, value) => storage.set(key, value),
};
globalThis.document = {
    addEventListener: (name, callback) => {
        if (name === 'alpine:init') initializeAlpine = callback;
    },
};
globalThis.Alpine = {
    store(name, value) {
        if (arguments.length === 2) stores.set(name, value);

        return stores.get(name);
    },
};

await import('./resources/js/cart.js');
initializeAlpine();

console.log(JSON.stringify(Alpine.store('cart').items));
JS;

    $result = Process::path(base_path())->run(['node', '--input-type=module', '--eval', $script]);

    if ($result->failed()) {
        $this->fail($result->errorOutput() ?: $result->output());
    }

    expect(trim($result->output()))->toBe('[]');
});

it('caps hydrated and mutated cart quantities at one hundred', function () {
    $script = <<<'JS'
const storage = new Map([
    ['cart', JSON.stringify([
        { id: 10, name: 'Hydrated', qty: 250 },
        { id: 20, name: 'Added', qty: 80 },
        { id: 40, name: 'Malformed', qty: '99junk' },
    ])],
]);
let initializeAlpine;
const stores = new Map();

globalThis.localStorage = {
    getItem: (key) => storage.get(key) ?? null,
    setItem: (key, value) => storage.set(key, value),
};
globalThis.document = {
    addEventListener: (name, callback) => {
        if (name === 'alpine:init') initializeAlpine = callback;
    },
};
globalThis.Alpine = {
    store(name, value) {
        if (arguments.length === 2) stores.set(name, value);

        return stores.get(name);
    },
};

await import('./resources/js/cart.js');
initializeAlpine();

const cart = Alpine.store('cart');
cart.add({ id: 20, name: 'Added', qty: 80 });
cart.upsert({ id: 30, name: 'Upserted', qty: 999 });
cart.updateQty(10, 500);

console.log(JSON.stringify(cart.items.map(({ id, qty }) => ({ id, qty }))));
JS;

    $result = Process::path(base_path())->run(['node', '--input-type=module', '--eval', $script]);

    if ($result->failed()) {
        $this->fail($result->errorOutput() ?: $result->output());
    }

    expect(trim($result->output()))->toBe('[{"id":10,"qty":100},{"id":20,"qty":100},{"id":40,"qty":1},{"id":30,"qty":100}]');
});

it('normalizes checkout state and display quantities at one hundred', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'stock' => 0,
        'is_unlimited_stock' => true,
    ]);

    Livewire::test('ecommerce.checkout.checkout', [
        'selectedIds' => (new Sqids)->encode([$productFlat->id]),
    ])
        ->call('resolveShopGroups', [['id' => $productFlat->id, 'qty' => 999]])
        ->assertSet('shopGroups.0.items', [$productFlat->id => 100])
        ->assertSee('checkoutItemsSnapshot', false)
        ->assertSee('qty: this.normalizeQuantity(shopItemQuantities[item.id])', false);
});

it('does not parse a partially numeric checkout quantity as its numeric prefix', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create(['shop_id' => $shop->id]);

    Livewire::test('ecommerce.checkout.checkout', [
        'selectedIds' => (new Sqids)->encode([$productFlat->id]),
    ])
        ->call('resolveShopGroups', [['id' => $productFlat->id, 'qty' => '99junk']])
        ->assertSet('shopGroups.0.items', [$productFlat->id => 1]);
});

it('keeps checkout summary rows and resolution on the initial cart snapshot', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'price' => 10_000,
        'stock' => 10,
    ]);
    $cartItems = [[
        'id' => $productFlat->id,
        'shop_id' => $shop->id,
        'shop_name' => $shop->name,
        'name' => $productFlat->name,
        'price' => 10_000,
        'image' => '',
        'qty' => 2,
    ]];

    $component = Livewire::test('ecommerce.checkout.checkout', [
        'selectedIds' => (new Sqids)->encode([$productFlat->id]),
    ])->call('resolveShopGroups', $cartItems);
    $document = HTMLDocument::createFromString($component->html(true), LIBXML_NOERROR);
    $root = $document->querySelector('[x-data*="maximumQuantity"]');
    $nestedDataExpression = collect(iterator_to_array($document->querySelectorAll('[x-data]')))
        ->map(fn ($element): string => (string) $element->getAttribute('x-data'))
        ->first(fn (string $expression): bool => str_contains($expression, 'get shopItems()'), '');

    expect($root)->not->toBeNull();

    $rootDataExpression = base64_encode((string) $root->getAttribute('x-data'));
    $nestedDataExpression = base64_encode($nestedDataExpression);
    $cartItemsJson = json_encode($cartItems, JSON_THROW_ON_ERROR);
    $shopItemQuantitiesJson = json_encode([$productFlat->id => 2], JSON_THROW_ON_ERROR);
    $script = <<<JS
const decode = (value) => Buffer.from(value, 'base64').toString('utf8');
const cart = { items: {$cartItemsJson} };
const resolvedSnapshots = [];
const wire = {
    entangle: (property) => ({ totalShippingCost: 0, insuranceFee: 2500, applicationFee: 1000 })[property],
    resolveShopGroups: async (items) => resolvedSnapshots.push(structuredClone(items)),
};
const localStorage = { getItem: () => null };
const createScope = (expression) => new Function(
    '\$wire',
    '\$store',
    'localStorage',
    'return (' + expression + ');',
)(wire, { cart }, localStorage);
const root = createScope(decode('{$rootDataExpression}'));
const nestedExpression = decode('{$nestedDataExpression}');
const shopItemQuantities = {$shopItemQuantitiesJson};
const rowQuantities = () => {
    if (nestedExpression !== '') {
        return createScope(nestedExpression).shopItems.map((item) => item.qty);
    }

    return root.shopItems(shopItemQuantities).map((item) => item.qty);
};
const state = () => ({
    summaryQuantity: root.checkoutCount,
    subtotal: root.subtotal,
    rowQuantities: rowQuantities(),
});

await root.init();
cart.items[0].qty = 9;
const afterQuantityChange = state();
cart.items = [];
const afterRemoval = state();

console.log(JSON.stringify({
    resolvedQuantity: resolvedSnapshots[0]?.[0]?.qty,
    afterQuantityChange,
    afterRemoval,
}));
JS;

    $result = Process::path(base_path())->run(['node', '--input-type=module', '--eval', $script]);

    if ($result->failed()) {
        $this->fail($result->errorOutput() ?: $result->output());
    }

    expect(json_decode(trim($result->output()), true, flags: JSON_THROW_ON_ERROR))->toBe([
        'resolvedQuantity' => 2,
        'afterQuantityChange' => [
            'summaryQuantity' => 2,
            'subtotal' => 20_000,
            'rowQuantities' => [2],
        ],
        'afterRemoval' => [
            'summaryQuantity' => 2,
            'subtotal' => 20_000,
            'rowQuantities' => [2],
        ],
    ]);
});

it('submits the resolved checkout quantity snapshot', function () {
    Mail::fake();

    $user = User::factory()->create();
    $location = Location::factory()->for($user)->create();
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'price' => 10_000,
        'stock' => 10,
    ]);
    $checkout = Order::factory()->for($user)->create(['access_token' => null]);
    $selectedRate = [
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'price' => 5_000,
        'name' => 'JNE Regular',
        'etd' => '2 days',
    ];

    $shippingRatesAction = Mockery::mock(GetShippingRatesAction::class);
    $shippingRatesAction->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(fn (ShippingRatesData $data): bool => $data->shopId === $shop->id && $data->destinationAreaId === $location->biteship_area_id && $data->items[0]->productFlatId === $productFlat->id && $data->items[0]->quantity === 2))
        ->andReturn([$selectedRate]);
    app()->instance(GetShippingRatesAction::class, $shippingRatesAction);

    $storeCheckoutAction = Mockery::mock(StoreCheckoutAction::class);
    $storeCheckoutAction->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(fn (CheckoutData $data): bool => $data->shops[0]->shopId === $shop->id && $data->shops[0]->items[0]->productFlatId === $productFlat->id && $data->shops[0]->items[0]->quantity === 2), $user)
        ->andReturn($checkout);
    app()->instance(StoreCheckoutAction::class, $storeCheckoutAction);

    Livewire::actingAs($user)
        ->test('ecommerce.checkout.checkout', [
            'selectedIds' => (new Sqids)->encode([$productFlat->id]),
        ])
        ->call('resolveShopGroups', [['id' => $productFlat->id, 'qty' => 2]])
        ->set('form.selectedLocationId', $location->id)
        ->set('form.shopRates', [$shop->id => $selectedRate])
        ->call('submit', null);
});

it('persists the canonical maximum quantity through checkout', function () {
    Mail::fake();

    $user = User::factory()->create();
    $location = Location::factory()->for($user)->create();
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'price' => 10_000,
        'stock' => 0,
        'is_unlimited_stock' => true,
        'status' => true,
    ]);
    $selectedRate = [
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'price' => 5_000,
        'name' => 'JNE Regular',
        'etd' => '2 days',
    ];

    $shippingRatesAction = Mockery::mock(GetShippingRatesAction::class);
    $shippingRatesAction->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(fn (ShippingRatesData $data): bool => $data->shopId === $shop->id && $data->destinationAreaId === $location->biteship_area_id && $data->items[0]->productFlatId === $productFlat->id && $data->items[0]->quantity === 100))
        ->andReturn([$selectedRate]);
    app()->instance(GetShippingRatesAction::class, $shippingRatesAction);

    Livewire::actingAs($user)
        ->test('ecommerce.checkout.checkout', [
            'selectedIds' => (new Sqids)->encode([$productFlat->id]),
        ])
        ->call('resolveShopGroups', [['id' => $productFlat->id, 'qty' => 999]])
        ->set('form.selectedLocationId', $location->id)
        ->set('form.shopRates', [$shop->id => $selectedRate])
        ->call('submit', null);

    $order = Order::query()->latest('id')->firstOrFail();

    expect($order->items()->firstOrFail()->quantity)->toBe(100)
        ->and((float) $order->total_checkout)->toBe(1_000_000.0);
});

it('selects Biteship pricing and persists its shipping details through checkout', function () {
    Mail::fake();

    $user = User::factory()->create();
    $location = Location::factory()->for($user)->create(['biteship_area_id' => null, 'area_string' => null, 'latitude' => '-6.21', 'longitude' => '106.81']);
    $shop = Shop::factory()->create();
    Location::factory()->for($shop)->create(['type' => 'origin', 'biteship_area_id' => null, 'area_string' => null, 'latitude' => '-6.2', 'longitude' => '106.8']);
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create(['shop_id' => $shop->id]);

    $this->mock(BiteshipService::class)->shouldReceive('getRates')->once()
        ->with(Mockery::on(fn (array $payload): bool => $payload['origin_latitude'] === -6.2 && $payload['destination_longitude'] === 106.81
            && ! isset($payload['origin_area_id'], $payload['destination_area_id'])))
        ->andReturn([
        'pricing' => [[
            'courier_code' => 'jne',
            'courier_service_code' => 'reg',
            'courier_name' => 'JNE',
            'courier_service_name' => 'Regular',
            'price' => 23000,
            'duration' => '2-3 days',
        ]],
    ]);

    $shipping = Livewire::actingAs($user)
        ->test('ecommerce.shipping.rates', ['shopId' => $shop->id, 'items' => [$productFlat->id => 1]])
        ->call('fetchRates')
        ->assertSee('JNE Regular')
        ->call('selectRate', 'jne', 'reg', 1, 'Fake', 'Instant')
        ->assertSet('selectedName', 'JNE Regular')
        ->assertSet('selectedEtd', '2-3 days');

    Livewire::actingAs($user)
        ->test('ecommerce.checkout.checkout', ['selectedIds' => (new Sqids)->encode([$productFlat->id])])
        ->call('resolveShopGroups', [['id' => $productFlat->id, 'qty' => 1]])
        ->set('form.selectedLocationId', $location->id)
        ->call('onRateSelected', [
            'shopId' => $shop->id,
            'courier_code' => $shipping->get('selectedCourierCode'),
            'courier_service_code' => $shipping->get('selectedServiceCode'),
            'price' => $shipping->get('selectedPrice'),
            'name' => $shipping->get('selectedName'),
            'etd' => $shipping->get('selectedEtd'),
        ])
        ->assertSet('totalShippingCost', 23000)
        ->call('submit', null)
        ->assertHasNoErrors()
        ->assertDispatched('remove-cart-items', ids: [$productFlat->id]);

    $orderShop = Order::query()->sole()->orderShops()->sole();

    expect($orderShop->shipping_data['name'])->toBe('JNE Regular')
        ->and($orderShop->shipping_data['etd'])->toBe('2-3 days')
        ->and((float) $orderShop->total_shipping)->toBe(23000.0);
});

it('clears the saved guest address only after successful checkout', function () {
    Mail::fake();

    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'price' => 10_000,
        'stock' => 10,
        'status' => true,
    ]);
    $checkout = Order::factory()->create([
        'user_id' => null,
        'access_token' => hash('sha256', 'guest-checkout'),
    ]);
    $selectedRate = [
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'price' => 5_000,
        'name' => 'JNE Regular',
        'etd' => '2 days',
    ];
    $guestData = [
        'contact_name' => 'Guest Buyer',
        'contact_phone' => '081234567890',
        'email' => 'guest@example.test',
        'address' => 'Guest address',
        'note' => null,
        'postal_code' => '12345',
        'latitude' => -6.2,
        'longitude' => 106.8,
    ];

    $shippingRatesAction = Mockery::mock(GetShippingRatesAction::class);
    $shippingRatesAction->shouldReceive('handle')->once()
        ->with(Mockery::on(fn (ShippingRatesData $data): bool => $data->destinationAreaId === '' && $data->destinationLatitude === -6.2 && $data->destinationLongitude === 106.8))
        ->andReturn([$selectedRate]);
    app()->instance(GetShippingRatesAction::class, $shippingRatesAction);

    $storeCheckoutAction = Mockery::mock(StoreCheckoutAction::class);
    $storeCheckoutAction->shouldReceive('handle')->once()
        ->with(Mockery::on(fn (CheckoutData $data): bool => $data->guest->areaId === null && $data->guest->areaString === null), null)
        ->andReturn($checkout);
    app()->instance(StoreCheckoutAction::class, $storeCheckoutAction);

    Livewire::test('ecommerce.checkout.checkout', [
        'selectedIds' => (new Sqids)->encode([$productFlat->id]),
    ])
        ->call('resolveShopGroups', [['id' => $productFlat->id, 'qty' => 2]])
        ->set('form.shopRates', [$shop->id => $selectedRate])
        ->call('submit', $guestData)
        ->assertDispatched('delete-localstorage', key: 'checkout_guest_address');

    $applicationScript = file_get_contents(resource_path('js/app.js'));

    expect($applicationScript)
        ->toContain('Livewire.on("delete-localstorage"')
        ->toContain('localStorage.removeItem(params.key)');
});

it('rejects a delivery location owned by another user', function () {
    $user = User::factory()->create();
    $foreignLocation = Location::factory()->create();
    $this->actingAs($user);

    expect(fn () => app(StoreCheckoutAction::class)->handle(CheckoutData::fromArray([
        'selected_location_id' => $foreignLocation->id,
        'shop_groups' => [],
    ]), $user))->toThrow(ValidationException::class);
});

it('recalculates product prices totals and fees from canonical data', function () {
    Mail::fake();

    $user = User::factory()->create();
    $location = Location::factory()->for($user)->create();
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'price' => 10_000,
        'stock' => 10,
        'status' => true,
    ]);
    $this->actingAs($user);

    $order = app(StoreCheckoutAction::class)->handle(CheckoutData::fromArray([
        'selected_location_id' => $location->id,
        'shop_groups' => [
            $shop->id => [
                'selected_rate' => [
                    'courier_code' => 'jne',
                    'courier_service_code' => 'reg',
                    'price' => 5_000,
                    'name' => 'JNE Regular',
                    'etd' => '2 days',
                ],
                'items' => [
                    $productFlat->id => [
                        'price' => 1,
                        'qty' => 2,
                        'total' => 2,
                        'raw' => ['price' => 1],
                    ],
                ],
                'total_checkout' => 1,
                'total_shipping' => 1,
                'total' => 2,
            ],
        ],
        'total_checkout' => 1,
        'total_rates' => 1,
        'application_fee' => 0,
        'insurance_fee' => 0,
    ]), $user);

    $orderItem = $order->items()->firstOrFail();

    expect((float) $order->total_checkout)->toBe(20_000.0)
        ->and((float) $order->total_shipping)->toBe(5_000.0)
        ->and((float) $order->application_fee)->toBe(1_000.0)
        ->and((float) $order->insurance_fee)->toBe(2_500.0)
        ->and((float) $order->total)->toBe(28_500.0)
        ->and((float) $orderItem->price)->toBe(10_000.0)
        ->and((float) $orderItem->total)->toBe(20_000.0)
        ->and($orderItem->quantity)->toBe(2);
});

it('creates a tokenized guest order and normalizes the email field', function () {
    Mail::fake();

    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'price' => 10_000,
        'stock' => 10,
        'status' => true,
    ]);

    $order = app(StoreCheckoutAction::class)->handle(CheckoutData::fromArray([
        'selected_location_id' => null,
        'guest_data' => [
            'contact_name' => 'Guest Buyer',
            'contact_phone' => '081234567890',
            'email' => 'guest@example.com',
            'address' => 'Guest address',
            'note' => null,
            'postal_code' => '12345',
            'area_string' => 'Jakarta',
            'biteship_area_id' => 'IDNP6IDNC148IDND1198IDZ12950',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ],
        'shop_groups' => [
            $shop->id => [
                'selected_rate' => [
                    'courier_code' => 'jne',
                    'courier_service_code' => 'reg',
                    'price' => 5_000,
                    'name' => 'JNE Regular',
                    'etd' => '2 days',
                ],
                'items' => [
                    $productFlat->id => ['qty' => 1],
                ],
            ],
        ],
    ]), null);

    expect($order->user_id)->toBeNull()
        ->and($order->access_token)->toHaveLength(64)
        ->and($order->guest_data['contact_email'])->toBe('guest@example.com')
        ->and($order->guest_data)->not->toHaveKey('email');
});
