<?php

use App\Services\BiteshipService;
use Livewire\Livewire;

use function Pest\Laravel\mock;

it('registers the ecommerce shipping create update component', function () {
    expect(Livewire::exists('ecommerce.shipping.create-update'))->toBeTrue();
});

it('does not expose Biteship area lookup failures', function () {
    mock(BiteshipService::class)
        ->shouldReceive('getMapsAreas')
        ->once()
        ->andThrow(new RuntimeException('provider-secret-detail'));

    Livewire::test('ecommerce.shipping.create-update')
        ->set('searchArea', 'Bandung')
        ->call('searchBiteshipArea')
        ->assertDispatched(
            'toast',
            type: 'error',
            message: 'Gagal mencari area. Silakan coba lagi.',
        )
        ->assertDontSee('provider-secret-detail');
});
