<?php

namespace App\Providers;

use App\Contracts\Payments\PaymentGateway;
use App\Models\User;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentGatewaySettingsService;
use Bayurifkialghifari\DynamicQris\DynamicQrisClient;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DynamicQrisClient::class, function (Application $app): DynamicQrisClient {
            $settings = $app->make(PaymentGatewaySettingsService::class)->karuhunQrisConfiguration();

            return new DynamicQrisClient(
                baseUrl: $settings['base_url'],
                apiKey: $settings['api_key'],
                timeout: $settings['timeout'],
                connectTimeout: $settings['connect_timeout'],
            );
        });

        $this->app->bind(
            PaymentGateway::class,
            fn (): PaymentGateway => $this->app->make(PaymentGatewayManager::class)->driver(),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::automaticallyEagerLoadRelationships();

        // Production force HTTPS`
        if ($this->app->isProduction()) {
            URL::forceHttps();
            $this->app['request']->server->set('HTTPS', true);
        }

        // Gate pulse
        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole('superadmin');
        });

        // Timezone
        Carbon::macro('inApplicationTimezone', function () {
            return $this->tz(config('app.timezone_display'));
        });

        // In user timezone
        Carbon::macro('inUserTimezone', function () {
            return $this->tz(auth()->user()?->timezone ?? config('app.timezone_display'));
        });
    }
}
