<?php

use App\Actions\Cms\Shop\DeleteShopAction;
use App\Actions\Ecommerce\Location\DeleteLocationAction;
use App\Models\Shop\Shop;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $permissions = collect([
        'view'.Shop::class,
        'delete'.Shop::class,
    ])->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'api'));

    Role::findOrCreate('shopowner', 'api')->givePermissionTo($permissions);
    Role::findOrCreate('superadmin', 'api')->givePermissionTo($permissions);
    config()->set('services.biteship.key', 'test-key');
});

it('only lists the shopowners shops and rejects deleting a foreign shop', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');
    $ownedShop = Shop::factory()->for($shopowner)->create(['name' => 'Owned Shop']);
    $foreignShop = Shop::factory()->create(['name' => 'Foreign Shop']);

    $component = Livewire::actingAs($shopowner)
        ->test('cms.shop.table')
        ->assertSee($ownedShop->name)
        ->assertDontSee($foreignShop->name);

    expect(fn () => $component->call('delete', $foreignShop->id))
        ->toThrow(ModelNotFoundException::class);

    expect($foreignShop->fresh())->not->toBeNull();
});

it('rejects deleting a foreign shop at the action boundary', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');
    $this->actingAs($shopowner);

    $foreignShop = Shop::factory()->create();
    $deleteLocationAction = Mockery::mock(DeleteLocationAction::class);
    $deleteLocationAction->shouldNotReceive('handle');

    expect(fn () => (new DeleteShopAction($deleteLocationAction))->handle($foreignShop))
        ->toThrow(ModelNotFoundException::class);

    expect($foreignShop->fresh())->not->toBeNull();
});

it('allows superadmins to list and delete shops across tenants', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');

    $firstShop = Shop::factory()->create(['name' => 'First Tenant Shop']);
    $secondShop = Shop::factory()->create(['name' => 'Second Tenant Shop']);

    Livewire::actingAs($superadmin)
        ->test('cms.shop.table')
        ->assertSee($firstShop->name)
        ->assertSee($secondShop->name)
        ->call('delete', $secondShop->id)
        ->assertDispatched(
            'toast',
            type: 'success',
            message: 'Shop and its location deleted successfully.',
        );

    expect($secondShop->fresh())->toBeNull();
});
