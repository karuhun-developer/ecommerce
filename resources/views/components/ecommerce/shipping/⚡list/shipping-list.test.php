<?php

use App\Models\Location\Location;
use App\Models\Setting\Setting;
use App\Models\User;
use App\Services\BiteshipService;
use App\Services\CourierSettingsService;
use Livewire\Livewire;

use function Pest\Laravel\mock;

it('registers the ecommerce shipping list component', function () {
    expect(Livewire::exists('ecommerce.shipping.list'))->toBeTrue();
});

it('does not expose guest area lookup failures', function () {
    mock(BiteshipService::class)
        ->shouldReceive('getMapsAreas')
        ->once()
        ->andThrow(new RuntimeException('provider-secret-detail'));

    Livewire::test('ecommerce.shipping.list')
        ->set('guest_searchArea', 'Bandung')
        ->call('searchGuestArea')
        ->assertDispatched(
            'toast',
            type: 'error',
            message: 'Gagal mencari area. Silakan coba lagi.',
        )
        ->assertDontSee('provider-secret-detail');
});

it('marks area selection optional for coordinates and required for area IDs', function () {
    Livewire::test('ecommerce.shipping.list')->assertSet('requiresArea', false)->assertSee('Opsional')->assertSee('Kode Pos');
    Setting::query()->create(['key' => CourierSettingsService::KEY, 'data' => ['rate_method' => 'area_id']]);
    Livewire::test('ecommerce.shipping.list')->assertSet('requiresArea', true)->assertDontSee('Opsional');
});

it('selects a new address and updates shipping rates after saving an address', function () {
    $user = User::factory()->create();
    $component = Livewire::actingAs($user)->test('ecommerce.shipping.list');
    $location = Location::factory()->for($user)->create(['biteship_area_id' => null]);
    $component->dispatch('shipping-list-refresh')->assertSet('selectedLocationId', $location->id)
        ->assertDispatched('shipping-address-selected', locationId: $location->id);
});
