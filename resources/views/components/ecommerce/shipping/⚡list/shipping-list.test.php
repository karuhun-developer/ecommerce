<?php

use App\Services\BiteshipService;
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
