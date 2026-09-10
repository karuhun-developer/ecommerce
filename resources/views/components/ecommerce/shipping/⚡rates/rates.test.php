<?php

use App\Actions\Ecommerce\Shipping\GetShippingRatesAction;
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
