<?php

use App\Actions\Ecommerce\Shipping\GetShippingRatesAction;
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
