<?php

use App\Actions\Cms\Shipping\UpdateBiteshipSettingsAction;
use App\Data\Cms\BiteshipSettingsData;
use App\Models\Setting\Setting;
use App\Services\BiteshipSettingsService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    public array $configuration = [];

    #[Locked]
    public array $hasCredentials = [];

    public function mount(BiteshipSettingsService $settings): void
    {
        Gate::authorize('view'.Setting::class);
        $this->loadConfiguration($settings);
    }

    public function save(UpdateBiteshipSettingsAction $action, BiteshipSettingsService $settings): void
    {
        Gate::authorize('update'.Setting::class);
        $action->handle(new BiteshipSettingsData($this->configuration), auth()->user());
        $this->resetValidation();
        $this->loadConfiguration($settings);
        $this->dispatch('toast', type: 'success', message: 'Pengaturan Biteship tersimpan.');
    }

    private function loadConfiguration(BiteshipSettingsService $settings): void
    {
        $this->configuration = $settings->configuration(includeSecrets: false);
        $this->configuration['header_key'] ??= 'X-Biteship-Webhook-Secret';
        foreach (BiteshipSettingsService::CREDENTIAL_FIELDS as $field) {
            $this->hasCredentials[$field] = $settings->hasCredential($field);
        }
    }
};
