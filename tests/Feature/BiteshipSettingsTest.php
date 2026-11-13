<?php

use App\Actions\Api\V1\Callback\HandleBiteshipCallbackAction;
use App\Actions\Cms\Shipping\UpdateBiteshipSettingsAction;
use App\Data\Callbacks\BiteshipCallbackData;
use App\Data\Cms\BiteshipSettingsData;
use App\Models\Setting\Setting;
use App\Models\Spatie\Permission;
use App\Models\User;
use App\Services\BiteshipService;
use App\Services\BiteshipSettingsService;
use App\Services\Payments\PaymentGatewaySettingsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
    config()->set([
        'services.biteship.key' => null,
        'services.biteship.webhook.header_key' => null,
        'services.biteship.webhook.header_secret' => null,
    ]);
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo([
        Permission::findOrCreate('view'.Setting::class, 'api'),
        Permission::findOrCreate('update'.Setting::class, 'api'),
    ]);
    $this->configuration = [
        'api_key' => 'cms-biteship-key', 'header_key' => 'X-CMS-Biteship', 'header_secret' => 'cms-webhook-secret',
        'timeout' => 25, 'connect_timeout' => 5, 'retries' => 0,
    ];
});

it('saves Biteship credentials in the CMS and uses them for shipping requests', function () {
    Setting::query()->create(['key' => PaymentGatewaySettingsService::KEY, 'data' => ['default' => 'karuhunqris']]);
    Cache::put('biteship:couriers', [['courier_code' => 'stale']]);
    $this->actingAs($this->actor);
    $component = Livewire::test('cms.shipping.biteship.settings')->set('configuration', $this->configuration)
        ->call('save')->assertHasNoErrors()->assertSet('configuration.api_key', '')->assertSet('configuration.header_secret', '')
        ->assertSet('hasCredentials.api_key', true)->assertSet('hasCredentials.header_secret', true)->assertDispatched('toast');
    expect(Cache::has('biteship:couriers'))->toBeFalse();
    Http::fake(['api.biteship.com/v1/couriers' => Http::response(['success' => true, 'couriers' => [['courier_code' => 'jne']]])]);
    expect(app(BiteshipService::class)->couriers())->toBe([['courier_code' => 'jne']]);
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'cms-biteship-key'));
    $setting = Setting::query()->where('key', BiteshipSettingsService::KEY)->sole();
    expect($setting->data['timeout'])->toBe(25)
        ->and(Crypt::decryptString($setting->data['api_key']))->toBe('cms-biteship-key')
        ->and(Crypt::decryptString($setting->data['header_secret']))->toBe('cms-webhook-secret')
        ->and($setting->getRawOriginal('data'))->not->toContain('cms-biteship-key')->not->toContain('cms-webhook-secret')
        ->and(Activity::query()->get()->pluck('properties')->toJson())->not->toContain('cms-biteship-key')->not->toContain('cms-webhook-secret')
        ->and(Setting::query()->where('key', PaymentGatewaySettingsService::KEY)->sole()->data)->toBe(['default' => 'karuhunqris']);
    $component->call('save')->assertHasNoErrors();
    expect($setting->fresh()->data['api_key'])->toBe($setting->data['api_key']);
    Livewire::test('cms.shipping.biteship.settings')->assertDontSee('cms-biteship-key')->assertDontSee('cms-webhook-secret');
});

it('authenticates Biteship callbacks using the header name and secret stored in the CMS', function () {
    app(UpdateBiteshipSettingsAction::class)->handle(new BiteshipSettingsData($this->configuration), $this->actor);
    $action = Mockery::mock(HandleBiteshipCallbackAction::class);
    $action->shouldReceive('handle')->once()->with(Mockery::type(BiteshipCallbackData::class))->andReturnNull();
    $this->app->instance(HandleBiteshipCallbackAction::class, $action);
    $this->postJson(route('api.v1.biteship.callback'), ['event' => 'order.status'], ['X-CMS-Biteship' => 'wrong-secret'])->assertUnauthorized();
    $this->postJson(route('api.v1.biteship.callback'), ['event' => 'order.status'], ['X-CMS-Biteship' => 'cms-webhook-secret'])->assertOk();
});

it('requires the API key and matching webhook configuration before saving', function () {
    $this->actingAs($this->actor);
    Livewire::test('cms.shipping.biteship.settings')->call('save')
        ->assertHasErrors(['configuration.api_key' => 'required', 'configuration.header_secret' => 'required'])
        ->set('configuration', $this->configuration)->call('save')->assertHasNoErrors();
});

it('rejects invalid Biteship configuration without persisting it', function (array $overrides) {
    expect(fn () => app(UpdateBiteshipSettingsAction::class)->handle(new BiteshipSettingsData([...$this->configuration, ...$overrides]), $this->actor))
        ->toThrow(ValidationException::class);
    expect(Setting::query()->count())->toBe(0);
})->with([
    'invalid header name' => [['header_key' => 'X-Header: injected']],
    'newline API key' => [['api_key' => "api-key\r\nx-other: secret"]],
    'newline secret' => [['header_secret' => "secret\r\nx-other: secret"]],
    'missing header secret' => [['header_secret' => '']],
    'missing header name' => [['header_key' => '']],
    'connection exceeds timeout' => [['connect_timeout' => 30]],
    'invalid retries' => [['retries' => 6]],
]);

it('denies unauthorized Biteship settings changes', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('view'.Setting::class);
    expect(fn () => app(UpdateBiteshipSettingsAction::class)->handle(new BiteshipSettingsData($this->configuration), $viewer))
        ->toThrow(AuthorizationException::class);
    Livewire::actingAs($viewer)->test('cms.shipping.biteship.settings')->call('save')->assertForbidden();
    Livewire::actingAs(User::factory()->create())->test('cms.shipping.biteship.settings')->assertForbidden();
    expect(Setting::query()->count())->toBe(0);
});

it('uses environment credentials until Biteship settings are configured', function () {
    config()->set(['services.biteship.key' => 'environment-api-key', 'services.biteship.webhook.header_key' => 'X-Env-Header', 'services.biteship.webhook.header_secret' => 'environment-secret']);
    $settings = app(BiteshipSettingsService::class);
    expect($settings->configuration()['api_key'])->toBe('environment-api-key')
        ->and($settings->configuration()['header_secret'])->toBe('environment-secret')
        ->and($settings->configuration(includeSecrets: false)['api_key'])->toBe('');
});
