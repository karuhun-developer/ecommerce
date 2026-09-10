<?php

use App\Actions\Cms\Product\Product\DeleteProductAction;
use App\Models\Product\Product;
use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $permissions = collect([
        'view'.Product::class,
        'delete'.Product::class,
    ])->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'api'));

    Role::findOrCreate('shopowner', 'api')->givePermissionTo($permissions);
    Role::findOrCreate('superadmin', 'api')->givePermissionTo($permissions);
});

it('only lists the shopowners products and rejects deleting a foreign product', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');

    $ownedShop = Shop::factory()->for($shopowner)->create();
    $foreignShop = Shop::factory()->create();
    $ownedProduct = Product::factory()->for($ownedShop)->create(['name' => 'Owned Product']);
    $foreignProduct = Product::factory()->for($foreignShop)->create(['name' => 'Foreign Product']);
    ProductFlat::factory()->create([
        'product_id' => $ownedProduct->id,
        'shop_id' => $ownedShop->id,
    ]);
    ProductFlat::factory()->create([
        'product_id' => $foreignProduct->id,
        'shop_id' => $foreignShop->id,
    ]);

    $component = Livewire::actingAs($shopowner)
        ->test('cms.product.product.table')
        ->assertSee($ownedProduct->name)
        ->assertDontSee($foreignProduct->name);

    expect(fn () => $component->call('delete', $foreignProduct->id))
        ->toThrow(ModelNotFoundException::class);

    expect($foreignProduct->fresh())->not->toBeNull();
});

it('rejects deleting a foreign product at the action boundary', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');
    $this->actingAs($shopowner);

    $foreignProduct = Product::factory()->create();

    expect(fn () => app(DeleteProductAction::class)->handle($foreignProduct))
        ->toThrow(ModelNotFoundException::class);

    expect($foreignProduct->fresh())->not->toBeNull();
});

it('allows superadmins to list and delete products across tenants', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');

    $firstShop = Shop::factory()->create();
    $secondShop = Shop::factory()->create();
    $firstProduct = Product::factory()->for($firstShop)->create(['name' => 'First Tenant Product']);
    $secondProduct = Product::factory()->for($secondShop)->create(['name' => 'Second Tenant Product']);
    ProductFlat::factory()->create([
        'product_id' => $firstProduct->id,
        'shop_id' => $firstShop->id,
    ]);
    ProductFlat::factory()->create([
        'product_id' => $secondProduct->id,
        'shop_id' => $secondShop->id,
    ]);

    Livewire::actingAs($superadmin)
        ->test('cms.product.product.table')
        ->assertSee($firstProduct->name)
        ->assertSee($secondProduct->name)
        ->call('delete', $secondProduct->id)
        ->assertDispatched('toast', type: 'success', message: 'Product deleted successfully.');

    expect($secondProduct->fresh())->toBeNull();
});
