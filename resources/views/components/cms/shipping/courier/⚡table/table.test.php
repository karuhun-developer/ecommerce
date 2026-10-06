<?php

use App\Models\Setting\Setting;
use App\Models\Spatie\Permission;
use App\Models\User;
use App\Services\CourierSettingsService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.biteship.key' => 'test-key']);
    Http::preventStrayRequests();
    $this->catalogStatus = 200;
    $this->couriers = [
        ['courier_code' => 'jne', 'courier_name' => 'JNE', 'courier_service_name' => 'Regular'],
        ['courier_code' => 'gojek', 'courier_name' => 'GoSend', 'courier_service_name' => 'Instant'],
        ['courier_code' => 'grab', 'courier_name' => 'GrabExpress', 'courier_service_name' => 'Instant'],
    ];
    Http::fake(fn (): \GuzzleHttp\Promise\PromiseInterface => Http::response(['couriers' => $this->couriers], $this->catalogStatus));
    $this->actor = User::factory()->create();
    foreach (['view', 'show', 'update'] as $ability) {
        $this->actor->givePermissionTo(Permission::findOrCreate($ability.Setting::class, 'api'));
    }
});

it('renders the catalog and searches couriers by name code and service', function () {
    Livewire::actingAs($this->actor)->test('cms.shipping.courier.table')
        ->assertSet('rateMethod', 'coordinates')
        ->assertSee('GoSend')->assertSee('GrabExpress')->assertSee('JNE')
        ->assertSee('Active')->assertSee('Inactive')
        ->set('search', 'GOJEK')->assertSee('GoSend')->assertDontSee('GrabExpress')->assertDontSee('JNE')
        ->set('search', 'regular')->assertSee('JNE')->assertDontSee('GoSend')
        ->set('search', 'missing')->assertSee('No data found.');
});

it('saves the method and preserves the enabled courier selection', function () {
    Setting::query()->create(['key' => CourierSettingsService::KEY, 'data' => ['enabled_couriers' => ['gojek', 'grab']]]);
    Livewire::actingAs($this->actor)->test('cms.shipping.courier.table')
        ->set('rateMethod', 'area_id')->call('saveRateMethod')
        ->assertHasNoErrors()->assertDispatched('toast');
    expect(app(CourierSettingsService::class)->enabledCodes())->toBe(['gojek', 'grab']);
    Livewire::test('cms.shipping.courier.table')->assertSet('rateMethod', 'area_id');
});

it('validates the rate method before saving', function () {
    Livewire::actingAs($this->actor)->test('cms.shipping.courier.table')
        ->set('rateMethod', 'invalid')->call('saveRateMethod')->assertHasErrors(['rateMethod']);
    expect(Setting::query()->count())->toBe(0);
});

it('paginates the catalog and resets pagination when searching', function () {
    $this->couriers = collect(range(1, 12))
        ->map(fn (int $index): array => ['courier_code' => 'courier'.$index, 'courier_name' => sprintf('Courier %02d', $index), 'courier_service_name' => 'Regular'])->all();
    Livewire::actingAs($this->actor)->test('cms.shipping.courier.table')
        ->set('paginationOrder', 'asc')->assertSee('Courier 01')->assertDontSee('Courier 12')
        ->call('gotoPage', 2)->assertSee('Courier 12')->assertDontSee('Courier 01')
        ->set('search', 'courier1')->assertSet('paginators.page', 1)->assertSee('Courier 12')
        ->set('search', '')->set('paginate', 25)->assertSee('Courier 01')->assertSee('Courier 12');
});

it('shows a retry when the courier provider fails', function () {
    $this->catalogStatus = 500;
    $component = Livewire::actingAs($this->actor)->test('cms.shipping.courier.table')
        ->assertSee('Gagal mengambil daftar kurir. Silakan coba lagi.')->assertSee('Retry');
    $this->catalogStatus = 200;
    $this->couriers = [
        ['courier_code' => 'grab', 'courier_name' => 'GrabExpress', 'courier_service_name' => 'Instant'],
    ];
    $component->call('$refresh')->assertSee('GrabExpress')->assertDontSee('Gagal mengambil daftar kurir.');
});

it('denies viewing and updating without the required permission', function () {
    Livewire::actingAs(User::factory()->create())->test('cms.shipping.courier.table')->assertForbidden();
    $viewer = User::factory()->create();
    $viewer->givePermissionTo(Permission::findOrCreate('view'.Setting::class, 'api'));
    Livewire::actingAs($viewer)->test('cms.shipping.courier.table')
        ->set('rateMethod', 'area_id')->call('saveRateMethod')->assertForbidden();
    expect(Setting::query()->count())->toBe(0);
});

it('renders the CMS page for an authorized user', function () {
    $this->actingAs($this->actor)->get('/cms/shipping/courier')->assertSuccessful()
        ->assertSeeLivewire('cms.shipping.courier.table')->assertSee('Couriers');
});
