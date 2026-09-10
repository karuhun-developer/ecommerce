<?php

use App\Models\Shop\Shop;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\User;
use App\Services\BiteshipService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    $shopownerRole = Role::findOrCreate('shopowner', 'api');
    $shopownerRole->givePermissionTo([
        Permission::findOrCreate('view'.Shop::class, 'api'),
        Permission::findOrCreate('update'.Shop::class, 'api'),
    ]);
});

it('loads only the shopowners default shop', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');

    $ownedShop = Shop::factory()->for($shopowner)->create();
    Shop::factory()->create();

    Livewire::actingAs($shopowner)
        ->test('cms.shop.single')
        ->assertSet('id', $ownedShop->id)
        ->assertSet('shop', fn (?Shop $shop): bool => $shop?->is($ownedShop) === true);
});

it('rejects mounting a shop owned by another shopowner', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');

    $foreignShop = Shop::factory()->create();

    expect(fn () => Livewire::actingAs($shopowner)
        ->test('cms.shop.single', ['shop' => $foreignShop]))
        ->toThrow(ModelNotFoundException::class);
});

it('locks the shop record identifier', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');

    $ownedShop = Shop::factory()->for($shopowner)->create();
    $foreignShop = Shop::factory()->create();

    $component = Livewire::actingAs($shopowner)
        ->test('cms.shop.single', ['shop' => $ownedShop]);

    expect(fn () => $component->set('id', $foreignShop->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('does not expose raw provider errors while searching areas', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');
    $shop = Shop::factory()->for($shopowner)->create();

    $biteshipService = Mockery::mock(BiteshipService::class);
    $biteshipService->shouldReceive('getMapsAreas')
        ->once()
        ->andThrow(new RuntimeException('provider credential leaked'));
    app()->instance(BiteshipService::class, $biteshipService);

    Livewire::actingAs($shopowner)
        ->test('cms.shop.single', ['shop' => $shop])
        ->set('searchArea', 'Jakarta')
        ->call('searchBiteshipArea')
        ->assertDispatched(
            'toast',
            type: 'error',
            message: 'Unable to search areas right now. Please try again.',
        )
        ->assertDontSee('provider credential leaked');
});
