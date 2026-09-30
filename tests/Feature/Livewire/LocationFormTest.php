<?php

use App\Actions\Ecommerce\Location\UpdateLocationAction;
use App\Data\Location\LocationData;
use App\Models\Location\Location;
use App\Models\User;
use App\Services\BiteshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

it('creates a destination from validated form fields and an explicit actor', function () {
    $user = User::factory()->create();
    $this->mock(BiteshipService::class)->shouldReceive('createLocation')->once()
        ->with(Mockery::on(fn (array $data): bool => $data['type'] === 'destination' && $data['latitude'] === -6.2))
        ->andReturn(['id' => 'destination-id']);

    Livewire::actingAs($user)->test('ecommerce.shipping.create-update')
        ->set('form.location_name', 'Rumah')
        ->set('form.contact_name', 'Penerima')
        ->set('form.contact_phone', '08123456789')
        ->set('form.address', 'Jalan Utama')
        ->set('form.postal_code', '10110')
        ->set('form.latitude', '-6.2')
        ->set('form.longitude', '106.8')
        ->set('form.biteship_area_id', 'area-id')
        ->set('form.area_string', 'Jakarta')
        ->call('submit')->assertHasNoErrors()->assertDispatched('shipping-list-refresh');

    $location = Location::query()->sole();
    expect($location->user_id)->toBe($user->id)->and($location->type)->toBe('destination')
        ->and($location->shop_id)->toBeNull();
});

it('validates coordinate bounds and locks the address identifier', function () {
    $user = User::factory()->create();
    $component = Livewire::actingAs($user)->test('ecommerce.shipping.create-update')
        ->set('form.latitude', 91)->set('form.longitude', 181)
        ->call('submit')->assertHasErrors(['form.latitude', 'form.longitude']);
    expect(fn () => $component->set('id', 123))->toThrow(CannotUpdateLockedPropertyException::class);
});

it('rejects another users address before touching the provider', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create(['type' => 'destination']);
    $provider = Mockery::mock(BiteshipService::class);
    $provider->shouldNotReceive('updateLocation');
    $data = new LocationData('Rumah', 'Penerima', '08123456789', 'Street', null, '10110', -6.2, 106.8, 'area-id', 'Jakarta');

    expect(fn () => (new UpdateLocationAction($provider))->handle($location, $data, $user))
        ->toThrow(HttpException::class);
});
