<?php

use App\Actions\Ecommerce\Shipping\GetShippingRatesAction;
use App\Data\Checkout\ShippingRatesData;
use App\Models\Location\Location;
use App\Models\Setting\Setting;
use App\Models\User;
use App\Services\CourierSettingsService;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

use function Pest\Laravel\mock;

it('registers the ecommerce shipping rates component', function () {
    expect(Livewire::exists('ecommerce.shipping.rates'))->toBeTrue();
});

it('does not expose unexpected shipping provider failures', function () {
    mock(GetShippingRatesAction::class)
        ->shouldReceive('handle')
        ->once()
        ->andThrow(new RuntimeException('provider-secret-detail'));

    Livewire::test('ecommerce.shipping.rates', ['shopId' => 1, 'items' => [1]])
        ->call('fetchRates')
        ->assertSet('error', 'Gagal mengambil tarif pengiriman. Silakan coba lagi.')
        ->assertSet('loading', false)
        ->assertDontSee('provider-secret-detail');
});

it('uses provider values when a client submits forged shipping details', function (array $rate) {
    mock(GetShippingRatesAction::class)->shouldReceive('handle')->once()->andReturn([
        $rate,
    ]);

    Livewire::test('ecommerce.shipping.rates', ['shopId' => 1, 'items' => [1 => 2]])
        ->call('fetchRates')
        ->call('selectRate', 'jne', 'reg', 1, 'Fake', 'Instant')
        ->assertSet('selectedPrice', 23000)
        ->assertSet('selectedName', 'JNE Regular')
        ->assertSet('selectedEtd', '2-3 days')
        ->assertDispatched('shipping-rate-selected', fn (string $event, array $params): bool => $params[0] === [
            'shopId' => 1,
            'courier_code' => 'jne',
            'courier_service_code' => 'reg',
            'price' => 23000,
            'name' => 'JNE Regular',
            'etd' => '2-3 days',
        ]);
})->with([
    'normalized rate' => [[
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'price' => 23000,
        'name' => 'JNE Regular',
        'etd' => '2-3 days',
    ]],
    'Biteship pricing without name and etd' => [[
        'courier_code' => 'jne',
        'courier_service_code' => 'reg',
        'price' => 23000,
        'courier_name' => 'JNE',
        'courier_service_name' => 'Regular',
        'duration' => '2-3 days',
    ]],
]);

it('rejects unknown shipping services', function () {
    Livewire::test('ecommerce.shipping.rates', ['shopId' => 1])
        ->call('selectRate', 'fake', 'fake', 0, 'Fake', '')
        ->assertStatus(422)
        ->assertNotDispatched('shipping-rate-selected');
});

it('locks shipping data against client hydration', function (string $property, mixed $value) {
    expect(fn () => Livewire::test('ecommerce.shipping.rates', ['shopId' => 1, 'items' => [1 => 2]])->set($property, $value))
        ->toThrow(CannotUpdateLockedPropertyException::class);
})->with([
    'shop' => ['shopId', 99],
    'items' => ['items', [99 => 1]],
    'rates' => ['rates', [['price' => 0]]],
]);

it('checks guest rates from coordinates without an area selection', function () {
    mock(GetShippingRatesAction::class)->shouldReceive('handle')->once()
        ->with(Mockery::on(fn (ShippingRatesData $data): bool => $data->destinationAreaId === ''
            && $data->destinationLatitude === -6.2 && $data->destinationLongitude === 106.8))
        ->andReturn([]);
    Livewire::test('ecommerce.shipping.rates', ['shopId' => 1, 'items' => [1 => 2]])
        ->assertSet('destinationReady', false)
        ->dispatch('guest-address-updated', areaId: '', postalCode: '10110', latitude: -6.2, longitude: 106.8)
        ->assertSet('destinationReady', true)->call('fetchRates')->assertHasNoErrors();
});

it('loads saved coordinates and resets the rate when an authenticated address changes', function () {
    $user = User::factory()->create();
    $first = Location::factory()->for($user)->create(['biteship_area_id' => null, 'latitude' => '-6.2', 'longitude' => '106.8']);
    $second = Location::factory()->for($user)->create(['biteship_area_id' => null, 'latitude' => '-6.21', 'longitude' => '106.81']);
    Livewire::actingAs($user)->test('ecommerce.shipping.rates', ['shopId' => 1])
        ->assertSet('destinationAreaId', '')->assertSet('destinationReady', true)
        ->call('onAddressSelected', $second->id)->assertSet('destinationLatitude', -6.21)
        ->set('selectedPrice', 5000)->call('onAddressSelected', $first->id)
        ->assertSet('destinationLatitude', -6.2)->assertSet('selectedPrice', 0);
});

it('requires an area in area mode even when destination coordinates exist', function () {
    Setting::query()->create(['key' => CourierSettingsService::KEY, 'data' => ['rate_method' => 'area_id']]);
    Livewire::test('ecommerce.shipping.rates', ['shopId' => 1])
        ->call('setGuestDestination', '', '10110', -6.2, 106.8)->assertSet('destinationReady', false)
        ->call('setGuestDestination', 'destination-area', '10110')->assertSet('destinationReady', true);
});

it('rejects selecting an address belonging to another user', function () {
    $location = Location::factory()->create();
    expect(fn () => Livewire::actingAs(User::factory()->create())->test('ecommerce.shipping.rates', ['shopId' => 1])
        ->call('onAddressSelected', $location->id))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
