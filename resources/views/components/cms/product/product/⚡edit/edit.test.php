<?php

use App\Actions\Cms\Product\Product\UpdateProductAction;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    $shopownerRole = Role::findOrCreate('shopowner', 'api');
    $shopownerRole->givePermissionTo([
        Permission::findOrCreate('show'.Product::class, 'api'),
        Permission::findOrCreate('update'.Product::class, 'api'),
    ]);
    config()->set('shop.single_shop', false);
});

it('rejects mounting a product owned by another shopowner', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');

    $foreignProduct = Product::factory()->create();

    expect(fn () => Livewire::actingAs($shopowner)
        ->test('cms.product.product.edit', ['product' => $foreignProduct]))
        ->toThrow(ModelNotFoundException::class);
});

it('only exposes shops owned by the shopowner while editing', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');

    $ownedShop = Shop::factory()->for($shopowner)->create(['name' => 'Owned Shop']);
    $foreignShop = Shop::factory()->create(['name' => 'Foreign Shop']);
    $product = Product::factory()->for($ownedShop)->create();
    ProductFlat::factory()->create([
        'product_id' => $product->id,
        'shop_id' => $ownedShop->id,
    ]);

    Livewire::actingAs($shopowner)
        ->test('cms.product.product.edit', ['product' => $product])
        ->assertSee($ownedShop->name)
        ->assertDontSee($foreignShop->name);
});

it('rejects foreign nested product flat identifiers in the component', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');

    $category = ProductCategory::query()->create(['name' => 'Category']);
    $ownedShop = Shop::factory()->for($shopowner)->create();
    $foreignShop = Shop::factory()->create();
    $product = Product::factory()->for($ownedShop)->create(['product_category_id' => $category->id]);
    ProductFlat::factory()->create([
        'product_id' => $product->id,
        'shop_id' => $ownedShop->id,
    ]);
    $foreignProduct = Product::factory()->for($foreignShop)->create(['product_category_id' => $category->id]);
    $foreignFlat = ProductFlat::factory()->create([
        'product_id' => $foreignProduct->id,
        'shop_id' => $foreignShop->id,
    ]);

    $component = Livewire::actingAs($shopowner)
        ->test('cms.product.product.edit', ['product' => $product])
        ->set("productFlats.{$foreignFlat->id}", productFlatPayload());

    expect(fn () => $component->call('submit'))
        ->toThrow(ModelNotFoundException::class);

    expect($foreignFlat->fresh()->name)->not->toBe('Tampered Flat');
});

it('rejects foreign nested product flat identifiers in the action', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');
    $this->actingAs($shopowner);

    $category = ProductCategory::query()->create(['name' => 'Category']);
    $ownedShop = Shop::factory()->for($shopowner)->create();
    $foreignShop = Shop::factory()->create();
    $product = Product::factory()->for($ownedShop)->create(['product_category_id' => $category->id]);
    $ownedFlat = ProductFlat::factory()->create([
        'product_id' => $product->id,
        'shop_id' => $ownedShop->id,
    ]);
    $foreignProduct = Product::factory()->for($foreignShop)->create(['product_category_id' => $category->id]);
    $foreignFlat = ProductFlat::factory()->create([
        'product_id' => $foreignProduct->id,
        'shop_id' => $foreignShop->id,
    ]);

    expect(fn () => app(UpdateProductAction::class)->handle(
        product: $product,
        shop: $ownedShop,
        data: [
            'product_category_id' => $category->id,
            'productFlats' => [
                $ownedFlat->id => productFlatPayload(),
                $foreignFlat->id => productFlatPayload(),
            ],
            'attributes' => [],
        ],
    ))->toThrow(ModelNotFoundException::class);
});

it('locks the product record identifier', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');

    $ownedShop = Shop::factory()->for($shopowner)->create();
    $product = Product::factory()->for($ownedShop)->create();
    ProductFlat::factory()->create([
        'product_id' => $product->id,
        'shop_id' => $ownedShop->id,
    ]);
    $foreignProduct = Product::factory()->create();

    $component = Livewire::actingAs($shopowner)
        ->test('cms.product.product.edit', ['product' => $product]);

    expect(fn () => $component->set('product', $foreignProduct))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

function productFlatPayload(): array
{
    return [
        'name' => 'Tampered Flat',
        'description' => null,
        'price' => 1000,
        'weight' => 100,
        'length' => 10,
        'width' => 10,
        'height' => 10,
        'stock' => 10,
        'is_unlimited_stock' => false,
    ];
}
