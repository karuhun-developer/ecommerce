<?php

use App\Actions\Cms\Product\Product\StoreProductAction;
use App\Data\Cms\ProductCreateData;
use App\Models\Attribute\AttributeGroup;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Shop\Shop;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    $shopownerRole = Role::findOrCreate('shopowner', 'api');
    $shopownerRole->givePermissionTo(Permission::findOrCreate('create'.Product::class, 'api'));
    config()->set('shop.single_shop', false);
});

it('only exposes shops owned by the shopowner', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');

    $ownedShop = Shop::factory()->for($shopowner)->create(['name' => 'Owned Shop']);
    Shop::factory()->create(['name' => 'Foreign Shop']);

    Livewire::actingAs($shopowner)
        ->test('cms.product.product.create')
        ->assertSee($ownedShop->name)
        ->assertDontSee('Foreign Shop');
});

it('rejects a foreign shop when creating a product', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');

    Shop::factory()->for($shopowner)->create();
    $foreignShop = Shop::factory()->create();
    $category = ProductCategory::query()->create(['name' => 'Category']);

    Livewire::actingAs($shopowner)
        ->test('cms.product.product.create')
        ->set('form.shop_id', $foreignShop->id)
        ->set('form.product_category_id', $category->id)
        ->set('form.name', 'Injected Product')
        ->set('form.description', 'Attempted cross-tenant product')
        ->set('form.type', 'simple')
        ->set('form.price', 1000)
        ->set('form.weight', 100)
        ->set('form.length', 10)
        ->set('form.width', 10)
        ->set('form.height', 10)
        ->set('form.is_unlimited_stock', false)
        ->call('submit')
        ->assertStatus(404);

    expect(Product::query()->where('name', 'Injected Product')->exists())->toBeFalse();
});

it('rejects a foreign shop at the product action boundary', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');
    $this->actingAs($shopowner);

    $foreignShop = Shop::factory()->create();
    $category = ProductCategory::query()->create(['name' => 'Category']);

    expect(fn () => app(StoreProductAction::class)->handle($foreignShop, ProductCreateData::fromArray([
        'product_category_id' => $category->id,
        'type' => 'simple',
        'name' => 'Foreign Shop Product',
        'description' => null,
        'price' => 1000,
        'weight' => 100,
        'length' => 10,
        'width' => 10,
        'height' => 10,
        'is_unlimited_stock' => false,
    ]), $shopowner))->toThrow(ModelNotFoundException::class);
});

it('uses the trusted shop instead of a submitted shop identifier', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');
    $this->actingAs($shopowner);

    $ownedShop = Shop::factory()->for($shopowner)->create();
    $foreignShop = Shop::factory()->create();
    $category = ProductCategory::query()->create(['name' => 'Category']);

    $product = app(StoreProductAction::class)->handle($ownedShop, ProductCreateData::fromArray([
        'shop_id' => $foreignShop->id,
        'product_category_id' => $category->id,
        'type' => 'simple',
        'name' => 'Owned Product',
        'description' => null,
        'price' => 1000,
        'weight' => 100,
        'length' => 10,
        'width' => 10,
        'height' => 10,
        'is_unlimited_stock' => false,
    ]), $shopowner);

    expect($product->shop_id)->toBe($ownedShop->id)
        ->and($product->productFlats()->sole()->shop_id)->toBe($ownedShop->id);
});

it('locks the product model identifier', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');

    expect(fn () => Livewire::actingAs($shopowner)
        ->test('cms.product.product.create')
        ->set('modelInstance', Shop::class))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('creates variants from the attributes selected in the product form', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');
    $shop = Shop::factory()->for($shopowner)->create();
    $category = ProductCategory::query()->create(['name' => 'Clothing']);
    $group = AttributeGroup::query()->create(['shop_id' => $shop->id, 'name' => 'Size']);
    $small = $group->attributes()->create(['shop_id' => $shop->id, 'name' => 'Small', 'value' => 'S']);
    $large = $group->attributes()->create(['shop_id' => $shop->id, 'name' => 'Large', 'value' => 'L']);

    Livewire::actingAs($shopowner)
        ->test('cms.product.product.create')
        ->set('form.shop_id', $shop->id)
        ->set('form.product_category_id', $category->id)
        ->set('form.name', 'Variant Shirt')
        ->set('form.type', 'variable')
        ->set('form.price', 25000)
        ->set('form.selectedAttributes', [$group->id => [$small->id, $large->id]])
        ->call('submit')
        ->assertHasNoErrors();

    $product = Product::query()->where('name', 'Variant Shirt')->sole();
    expect($product->type)->toBe('variable')
        ->and($product->productFlats()->count())->toBe(2)
        ->and($product->productFlats()->pluck('name')->sort()->values()->all())->toBe(['Variant Shirt - Large', 'Variant Shirt - Small']);
});
