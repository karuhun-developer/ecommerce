<?php

use App\Models\Setting\Setting;
use App\Models\Spatie\Permission;
use App\Models\User;
use App\Services\CourierSettingsService;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.biteship.key' => 'test-key']);
    Http::preventStrayRequests();
    Http::fake(['api.biteship.com/v1/couriers' => Http::response(['couriers' => [
        ['courier_code' => 'gojek', 'courier_name' => 'GoSend', 'courier_service_name' => 'Instant'],
        ['courier_code' => 'grab', 'courier_name' => 'GrabExpress', 'courier_service_name' => 'Instant'],
    ]])]);
    $this->actor = User::factory()->create();
    foreach (['show', 'update'] as $ability) {
        $this->actor->givePermissionTo(Permission::findOrCreate($ability.Setting::class, 'api'));
    }
});

it('loads the selected courier and saves active and inactive statuses', function () {
    $component = Livewire::actingAs($this->actor)->test('cms.shipping.courier.create-update')
        ->dispatch('set-action', code: 'gojek')->assertSet('name', 'GoSend')->assertSet('enabled', 0)
        ->assertSee('wire:model.number="enabled"', false)
        ->set('enabled', '1')->call('submit')->assertHasNoErrors()->assertDispatched('reset-parent-page');
    expect(app(CourierSettingsService::class)->enabledCodes())->toContain('gojek');
    $component->dispatch('set-action', code: 'gojek')->assertSet('enabled', 1)
        ->set('enabled', '0')->call('submit')->assertHasNoErrors();
    expect(app(CourierSettingsService::class)->enabledCodes())->not->toContain('gojek');
});

it('locks the selected courier identity', function () {
    $component = Livewire::actingAs($this->actor)->test('cms.shipping.courier.create-update')
        ->dispatch('set-action', code: 'gojek');
    expect(fn () => $component->set('code', 'grab'))->toThrow(CannotUpdateLockedPropertyException::class);
});

it('rejects submission before selecting a courier', function () {
    Livewire::actingAs($this->actor)->test('cms.shipping.courier.create-update')
        ->call('submit')->assertHasErrors(['code']);
    expect(Setting::query()->count())->toBe(0);
});

it('rejects an unknown courier', function () {
    Livewire::actingAs($this->actor)->test('cms.shipping.courier.create-update')
        ->dispatch('set-action', code: 'unknown')->assertNotFound();
});

it('denies loading and saving without permission', function () {
    Livewire::actingAs(User::factory()->create())->test('cms.shipping.courier.create-update')
        ->dispatch('set-action', code: 'gojek')->assertForbidden();
    $viewer = User::factory()->create();
    $viewer->givePermissionTo(Permission::findOrCreate('show'.Setting::class, 'api'));
    Livewire::actingAs($viewer)->test('cms.shipping.courier.create-update')
        ->dispatch('set-action', code: 'gojek')->set('enabled', true)->call('submit')->assertForbidden();
    expect(Setting::query()->count())->toBe(0);
});
