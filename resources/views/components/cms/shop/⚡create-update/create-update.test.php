<?php

use App\Actions\Cms\Shop\StoreShopAction;
use App\Actions\Cms\Shop\UpdateShopAction;
use App\Actions\Ecommerce\Location\StoreLocationAction;
use App\Actions\Ecommerce\Location\UpdateLocationAction;
use App\Models\Location\Location;
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
        Permission::findOrCreate('show'.Shop::class, 'api'),
        Permission::findOrCreate('update'.Shop::class, 'api'),
    ]);
});

it('rejects loading a shop owned by another shopowner', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');

    $foreignShop = Shop::factory()->create();

    $component = Livewire::actingAs($shopowner)
        ->test('cms.shop.create-update');

    expect(fn () => $component->call('setAction', $foreignShop->id))
        ->toThrow(ModelNotFoundException::class);
});

it('rejects a foreign shop at the update action boundary', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');
    $this->actingAs($shopowner);

    $foreignShop = Shop::factory()->create();
    $updateLocationAction = Mockery::mock(UpdateLocationAction::class);
    $updateLocationAction->shouldNotReceive('handle');

    expect(fn () => (new UpdateShopAction($updateLocationAction))->handle($foreignShop, []))
        ->toThrow(ModelNotFoundException::class);
});

it('locks the selected shop identifier', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');

    $ownedShop = Shop::factory()->for($shopowner)->create();
    $foreignShop = Shop::factory()->create();

    $component = Livewire::actingAs($shopowner)
        ->test('cms.shop.create-update')
        ->call('setAction', $ownedShop->id);

    expect(fn () => $component->set('id', $foreignShop->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('does not expose raw provider errors while searching areas', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');

    $biteshipService = Mockery::mock(BiteshipService::class);
    $biteshipService->shouldReceive('getMapsAreas')
        ->once()
        ->andThrow(new RuntimeException('provider credential leaked'));
    app()->instance(BiteshipService::class, $biteshipService);

    Livewire::actingAs($shopowner)
        ->test('cms.shop.create-update')
        ->set('searchArea', 'Jakarta')
        ->call('searchBiteshipArea')
        ->assertDispatched(
            'toast',
            type: 'error',
            message: 'Unable to search areas right now. Please try again.',
        )
        ->assertDontSee('provider credential leaked');
});

it('derives shop ownership and sends an explicit location payload', function () {
    $shopowner = User::factory()->create();
    $shopowner->assignRole('shopowner');
    $foreignUser = User::factory()->create();
    $this->actingAs($shopowner);

    $storeLocationAction = Mockery::mock(StoreLocationAction::class);
    $storeLocationAction->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(function (array $payload): bool {
            return $payload['location_name'] === 'Main Warehouse'
                && $payload['contact_name'] === 'Shop Contact'
                && $payload['contact_phone'] === '08123456789'
                && $payload['address'] === 'Main Street'
                && $payload['note'] === null
                && $payload['postal_code'] === '10110'
                && $payload['latitude'] === '-6.2'
                && $payload['longitude'] === '106.8'
                && $payload['biteship_area_id'] === 'area-id'
                && $payload['area_string'] === 'Jakarta'
                && is_int($payload['shop_id'])
                && $payload['type'] === 'origin'
                && ! array_key_exists('user_id', $payload)
                && ! array_key_exists('unexpected', $payload);
        }))
        ->andReturn(new Location);

    $shop = (new StoreShopAction($storeLocationAction))->handle([
        'user_id' => $foreignUser->id,
        'name' => 'Secure Shop',
        'description' => 'Description',
        'location_name' => 'Main Warehouse',
        'contact_name' => 'Shop Contact',
        'contact_phone' => '08123456789',
        'address' => 'Main Street',
        'note' => null,
        'postal_code' => '10110',
        'latitude' => '-6.2',
        'longitude' => '106.8',
        'biteship_area_id' => 'area-id',
        'area_string' => 'Jakarta',
        'unexpected' => 'must not pass through',
    ]);

    expect($shop->user_id)->toBe($shopowner->id)
        ->and($shop->name)->toBe('Secure Shop');
});
