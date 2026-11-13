<?php

use App\Actions\Cms\Payment\UpdatePaymentGatewaySettingsAction;
use App\Data\Cms\PaymentGatewaySettingsData;
use App\Enums\PaymentGatewayDriver;
use App\Models\Menu\Menu;
use App\Models\Setting\Setting;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\User;
use App\Services\MidtransService;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentGatewaySettingsService;
use App\Services\Payments\PaywuzPaymentGateway;
use Bayurifkialghifari\DynamicQris\DynamicQrisClient;
use Database\Seeders\IntegrationSettingsMenuSeeder;
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
        'payment.default' => 'midtrans',
        'dynamic-qris.api_key' => null,
        'dynamic-qris.base_url' => 'https://qris.karuhundeveloper.com',
        'dynamic-qris.timeout' => 10,
        'dynamic-qris.connect_timeout' => 3,
        'midtrans.server_key' => null,
        'midtrans.client_key' => null,
        'midtrans.is_production' => false,
        'midtrans.retries' => 0,
        'payment.drivers.paywuz.api_key' => null,
    ]);
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo([
        Permission::findOrCreate('view'.Setting::class, 'api'),
        Permission::findOrCreate('update'.Setting::class, 'api'),
    ]);
});

it('uses environment configuration until CMS settings are saved', function () {
    config()->set('dynamic-qris.api_key', 'environment-key');
    $settings = app(PaymentGatewaySettingsService::class);

    expect(app(PaymentGatewayManager::class)->defaultDriver())->toBe(PaymentGatewayDriver::Midtrans)
        ->and($settings->karuhunQrisConfiguration())->toBe([
            'base_url' => 'https://qris.karuhundeveloper.com',
            'api_key' => 'environment-key',
            'timeout' => 10,
            'connect_timeout' => 3,
        ])
        ->and($settings->hasKaruhunQrisApiKey())->toBeTrue();
});

it('saves QRIS settings through the CMS and uses them for authenticated SDK requests', function () {
    $this->actingAs($this->actor);
    $manager = app(PaymentGatewayManager::class);
    $oldClient = app(DynamicQrisClient::class);
    Http::fake(['https://merchant.example.test/v1/account' => Http::response(['success' => true, 'data' => []])]);

    Livewire::test('cms.payment.settings')
        ->set('driver', 'karuhunqris')
        ->set('configuration.api_key', 'cms-merchant-key')
        ->set('configuration.base_url', 'https://merchant.example.test/')
        ->set('configuration.timeout', '25')
        ->set('configuration.connect_timeout', '5')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('configuration.api_key', '')
        ->assertSet('hasCredentials.api_key', true)
        ->assertDispatched('toast');

    $setting = Setting::query()->where('key', PaymentGatewaySettingsService::KEY)->sole();
    $client = app(DynamicQrisClient::class);
    $client->account()->get();

    expect($manager->defaultDriver())->toBe(PaymentGatewayDriver::KaruhunQris)
        ->and($client)->not->toBe($oldClient)
        ->and($client->timeout)->toBe(25)
        ->and($setting->data['karuhunqris']['connect_timeout'])->toBe(5)
        ->and(Crypt::decryptString($setting->data['karuhunqris']['api_key']))->toBe('cms-merchant-key')
        ->and($setting->getRawOriginal('data'))->not->toContain('cms-merchant-key')
        ->and(Activity::query()->get()->pluck('properties')->toJson())->not->toContain('cms-merchant-key');
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://merchant.example.test/v1/account'
        && $request->hasHeader('x-api-key', 'cms-merchant-key'));

    Livewire::test('cms.payment.settings')
        ->assertSet('configuration.api_key', '')
        ->assertSet('hasCredentials.api_key', true)
        ->assertSet('driver', 'karuhunqris')
        ->assertDontSee('cms-merchant-key');
});

it('preserves the saved key when left blank and picks up later key rotations', function () {
    $action = app(UpdatePaymentGatewaySettingsAction::class);
    $settings = app(PaymentGatewaySettingsService::class);
    $action->handle(new PaymentGatewaySettingsData('karuhunqris', ['api_key' => 'first-key', 'base_url' => 'https://qris.karuhundeveloper.com', 'timeout' => 10, 'connect_timeout' => 3]), $this->actor);
    $firstCiphertext = Setting::query()->sole()->data['karuhunqris']['api_key'];
    $firstClient = app(DynamicQrisClient::class);
    $action->handle(new PaymentGatewaySettingsData('paywuz', ['api_key' => 'paywuz-key', 'base_url' => 'https://api.paywuz.id/v1', 'timeout' => 20, 'connect_timeout' => 3]), $this->actor);

    expect(Setting::query()->count())->toBe(1)
        ->and(Setting::query()->sole()->data['karuhunqris']['api_key'])->toBe($firstCiphertext)
        ->and($settings->karuhunQrisConfiguration()['api_key'])->toBe('first-key')
        ->and(app(PaymentGatewayManager::class)->defaultDriver())->toBe(PaymentGatewayDriver::Paywuz);

    $action->handle(new PaymentGatewaySettingsData('karuhunqris', ['api_key' => 'second-key', 'base_url' => 'https://qris.karuhundeveloper.com', 'timeout' => 30, 'connect_timeout' => 4]), $this->actor);
    Http::fake(['*' => Http::response(['success' => true, 'data' => []])]);
    $client = app(DynamicQrisClient::class);
    $client->account()->get();

    expect($client)->not->toBe($firstClient)
        ->and($client->timeout)->toBe(30);
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-api-key', 'second-key'));
});

it('requires an API key before activating QRIS without existing credentials', function () {
    $this->actingAs($this->actor);
    Livewire::test('cms.payment.settings')->set('driver', 'karuhunqris')->call('save')->assertHasErrors(['configuration.api_key' => 'required']);
    expect(Setting::query()->count())->toBe(0);
});

it('retains environment credentials when the CMS key is left blank', function () {
    config()->set('dynamic-qris.api_key', 'environment-key');
    app(UpdatePaymentGatewaySettingsAction::class)->handle(
        new PaymentGatewaySettingsData('karuhunqris', ['api_key' => '', 'base_url' => 'https://qris.karuhundeveloper.com', 'timeout' => 10, 'connect_timeout' => 3]), $this->actor,
    );

    expect(app(PaymentGatewaySettingsService::class)->karuhunQrisConfiguration()['api_key'])->toBe('environment-key');
});

it('rejects invalid settings without writing to the database', function (array $overrides) {
    $values = [...['api_key' => 'merchant-key', 'base_url' => 'https://qris.karuhundeveloper.com', 'timeout' => 10, 'connect_timeout' => 3], ...$overrides];
    $driver = $values['driver'] ?? 'karuhunqris';
    unset($values['driver']);
    expect(fn () => app(UpdatePaymentGatewaySettingsAction::class)->handle(new PaymentGatewaySettingsData($driver, $values), $this->actor))
        ->toThrow(ValidationException::class);
    expect(Setting::query()->count())->toBe(0);
})->with([
    'unknown gateway' => [['driver' => 'unknown']],
    'plaintext API URL' => [['base_url' => 'http://merchant.example.test']],
    'URL with credentials' => [['base_url' => 'https://user:password@merchant.example.test']],
    'URL with query' => [['base_url' => 'https://merchant.example.test?key=test']],
    'URL with fragment' => [['base_url' => 'https://merchant.example.test#fragment']],
    'header injection' => [['api_key' => "merchant-key\r\nx-other: value"]],
    'zero timeout' => [['timeout' => 0]],
    'noninteger timeout' => [['timeout' => 'abc']],
    'connection exceeds request timeout' => [['connect_timeout' => 11]],
]);

it('denies unauthorized actors and prevents writes from view-only CMS users', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('view'.Setting::class);
    $this->actingAs($this->actor);
    expect(fn () => app(UpdatePaymentGatewaySettingsAction::class)->handle(
        new PaymentGatewaySettingsData('karuhunqris', ['api_key' => 'merchant-key', 'base_url' => 'https://qris.karuhundeveloper.com', 'timeout' => 10, 'connect_timeout' => 3]), $viewer,
    ))->toThrow(AuthorizationException::class);

    $this->actingAs($viewer);
    Livewire::test('cms.payment.settings')->call('save')->assertForbidden();
    expect(Setting::query()->count())->toBe(0);
});

it('protects the settings page and displays the short scheduler reminder to admins', function () {
    $this->get(route('cms.payment'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('cms.payment'))->assertForbidden();
    $this->actingAs($this->actor)->get(route('cms.payment'))
        ->assertOk()->assertSee('Pengaturan Integrasi')->assertSee('Server Key')->assertSee('Client Key')
        ->assertSee('API Key Biteship')->assertSee(route('api.v1.midtrans.callback'))
        ->assertSee(route('api.v1.paywuz.callback'))->assertSee(route('api.v1.biteship.callback'));
    Livewire::test('cms.payment.settings')->set('driver', 'karuhunqris')->assertSee('php artisan schedule:work')->assertSee('schedule:run');
});

it('adds the payment menu without duplicating entries or resetting saved settings', function () {
    $role = Role::findOrCreate('superadmin', 'api');
    $this->actor->assignRole($role);
    Cache::put('menu:'.$role->id, 'stale menu');
    $setting = Setting::query()->create(['key' => PaymentGatewaySettingsService::KEY, 'data' => ['default' => 'paywuz']]);
    Menu::query()->create(['role_id' => $role->id, 'name' => 'Pembayaran', 'url' => 'cms.payment', 'icon' => 'credit-card', 'order' => 360, 'active_pattern' => 'cms.payment', 'status' => 1]);
    $this->seed(IntegrationSettingsMenuSeeder::class);
    $this->seed(IntegrationSettingsMenuSeeder::class);

    expect(Menu::query()->where('role_id', $role->id)->where('url', 'cms.payment')->count())->toBe(1)
        ->and(Menu::query()->where('url', 'cms.payment')->sole()->name)->toBe('Pengaturan Integrasi')
        ->and($setting->refresh()->data)->toBe(['default' => 'paywuz'])
        ->and(Cache::has('menu:'.$role->id))->toBeFalse();
});

it('shows the credentials for the selected gateway and keeps saved provider settings when switching', function () {
    $this->actingAs($this->actor);
    Livewire::test('cms.payment.settings')
        ->assertSee('Merchant ID')->assertSee('Server Key')->assertSee('Client Key')->assertDontSee('Scheduler wajib')
        ->set('configuration.server_key', 'midtrans-cms-server')
        ->set('configuration.client_key', 'midtrans-cms-client')
        ->set('configuration.merchant_id', 'merchant-123')
        ->call('save')->assertHasNoErrors()->assertSet('configuration.server_key', '')
        ->set('driver', 'paywuz')->assertSee('API Key')->assertDontSee('Server Key')
        ->assertSet('configuration.base_url', 'https://api.paywuz.id/v1')
        ->set('configuration.api_key', 'paywuz-cms-key')->call('save')->assertHasNoErrors()
        ->set('driver', 'midtrans')->assertSet('configuration.merchant_id', 'merchant-123')
        ->assertSet('hasCredentials.server_key', true)->assertSet('configuration.server_key', '')
        ->assertDontSee('midtrans-cms-server')->assertDontSee('midtrans-cms-client');
    $configuration = Setting::query()->sole()->data;
    expect($configuration['default'])->toBe('paywuz')
        ->and(Crypt::decryptString($configuration['midtrans']['server_key']))->toBe('midtrans-cms-server')
        ->and(Crypt::decryptString($configuration['paywuz']['api_key']))->toBe('paywuz-cms-key');
});

it('uses CMS Midtrans keys and mode for requests and signature verification', function () {
    $this->actingAs($this->actor);
    Livewire::test('cms.payment.settings')
        ->set('configuration.server_key', 'cms-server-key')
        ->set('configuration.client_key', 'cms-client-key')
        ->set('configuration.is_production', '1')
        ->call('save')->assertHasNoErrors();
    Http::fake(['https://api.midtrans.com/v2/order-cms/status' => Http::response(['transaction_status' => 'pending'])]);
    $service = app(MidtransService::class);
    $service->paymentStatus('order-cms');
    expect($service->clientKey)->toBe('cms-client-key')
        ->and($service->validateSignature('order-cms', '200', '100000.00', hash('sha512', 'order-cms200100000.00cms-server-key')))->toBeTrue();
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.midtrans.com/v2/order-cms/status'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('cms-server-key:')));
    expect(Activity::query()->get()->pluck('properties')->toJson())->not->toContain('cms-server-key')->not->toContain('cms-client-key');
});

it('uses CMS Paywuz keys for requests and validates callback signatures even while another gateway is active', function () {
    $this->actingAs($this->actor);
    Livewire::test('cms.payment.settings')->set('driver', 'paywuz')
        ->set('configuration.api_key', 'cms-paywuz-key')
        ->set('configuration.base_url', 'https://paywuz.example.test/v1')
        ->call('save')->assertHasNoErrors();
    Http::fake(['https://paywuz.example.test/v1/payment-methods' => Http::response(['data' => []])]);
    app(PaywuzPaymentGateway::class)->paymentMethods();
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://paywuz.example.test/v1/payment-methods'
        && $request->hasHeader('Authorization', 'Bearer cms-paywuz-key'));
    Livewire::test('cms.payment.settings')->set('driver', 'midtrans')->set('configuration.server_key', 'cms-midtrans-key')->call('save')->assertHasNoErrors();
    $rawBody = json_encode(['orderId' => 'missing-cms-order', 'status' => 'success'], JSON_THROW_ON_ERROR);
    $this->call('POST', route('api.v1.paywuz.callback'), server: [
        'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_PAYWUZ_SIGNATURE' => 'sha256='.hash_hmac('sha256', $rawBody, 'cms-paywuz-key'),
        'HTTP_X_PAYWUZ_EVENT' => 'transaction.paid', 'HTTP_X_PAYWUZ_DELIVERY' => 'cms-delivery',
    ], content: $rawBody)->assertNotFound();
});

it('requires credentials for each gateway and accepts a blank key when already configured', function (string $driver, string $field) {
    $this->actingAs($this->actor);
    $component = Livewire::test('cms.payment.settings')->set('driver', $driver);
    $component->call('save')->assertHasErrors(['configuration.'.$field => 'required']);
    $component->set('configuration.'.$field, 'provider-key')->call('save')->assertHasNoErrors();
    $ciphertext = Setting::query()->sole()->data[$driver][$field];
    $component->call('save')->assertHasNoErrors();
    expect(Setting::query()->sole()->data[$driver][$field])->toBe($ciphertext);
})->with(['midtrans' => ['midtrans', 'server_key'], 'paywuz' => ['paywuz', 'api_key'], 'karuhunqris' => ['karuhunqris', 'api_key']]);
