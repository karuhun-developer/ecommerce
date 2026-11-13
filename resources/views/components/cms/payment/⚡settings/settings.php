<?php

use App\Actions\Cms\Payment\UpdatePaymentGatewaySettingsAction;
use App\Data\Cms\PaymentGatewaySettingsData;
use App\Enums\PaymentGatewayDriver;
use App\Models\Setting\Setting;
use App\Services\Payments\PaymentGatewaySettingsService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    public string $driver = 'midtrans';

    public array $configuration = [];

    #[Locked]
    public array $hasCredentials = [];

    public function mount(PaymentGatewaySettingsService $settings): void
    {
        Gate::authorize('view'.Setting::class);
        $this->driver = $settings->defaultDriver();
        $this->loadConfiguration($settings);
    }

    public function updatedDriver(): void
    {
        Gate::authorize('view'.Setting::class);
        $this->resetValidation();
        $this->loadConfiguration(app(PaymentGatewaySettingsService::class));
    }

    public function save(UpdatePaymentGatewaySettingsAction $action, PaymentGatewaySettingsService $settings): void
    {
        Gate::authorize('update'.Setting::class);
        $action->handle(new PaymentGatewaySettingsData(
            driver: $this->driver,
            configuration: $this->configuration,
        ), auth()->user());
        $this->resetValidation();
        $this->loadConfiguration($settings);
        $this->dispatch('toast', type: 'success', message: 'Pengaturan pembayaran tersimpan.');
    }

    private function loadConfiguration(PaymentGatewaySettingsService $settings): void
    {
        $driver = PaymentGatewayDriver::tryFrom($this->driver);
        $this->configuration = $driver ? $settings->configuration($driver, includeSecrets: false) : [];
        $this->hasCredentials = [];
        if ($driver) {
            foreach (PaymentGatewaySettingsService::CREDENTIAL_FIELDS[$driver->value] as $field) {
                $this->hasCredentials[$field] = $settings->hasCredential($driver, $field);
            }
        }
    }
};
