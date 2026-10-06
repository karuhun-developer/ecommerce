<?php

use Livewire\Component;
use App\Actions\Cms\Shipping\UpdateCourierSettingAction;
use App\Data\Cms\CourierSettingData;
use App\Models\Setting\Setting;
use App\Services\CourierSettingsService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

new class extends Component
{
    #[Locked]
    public string $modelInstance = Setting::class;

    #[Locked]
    public ?string $code = null;

    #[Locked]
    public string $name = '';

    public int $enabled = 0;

    #[On('set-action')]
    public function setAction(string $code, CourierSettingsService $courierSettings): void
    {
        Gate::authorize('show'.$this->modelInstance);
        $this->resetValidation();
        $this->reset(['code', 'name', 'enabled']);
        try {
            $courier = collect($courierSettings->couriers())->firstWhere('code', $code);
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatch('toast', type: 'error', message: 'Gagal mengambil daftar kurir. Silakan coba lagi.');

            return;
        }
        abort_unless($courier, 404);
        $this->code = $courier['code'];
        $this->name = $courier['name'];
        $this->enabled = (int) in_array($code, $courierSettings->enabledCodes(), true);
    }

    public function submit(UpdateCourierSettingAction $action): void
    {
        Gate::authorize('update'.$this->modelInstance);
        $this->validate(['code' => ['required', 'string'], 'enabled' => ['required', 'boolean']]);

        try {
            $action->handle(new CourierSettingData($this->code, (bool) $this->enabled), auth()->user());
        } catch (RuntimeException $exception) {
            report($exception);
            $this->dispatch('toast', type: 'error', message: 'Gagal menyimpan pengaturan kurir. Silakan coba lagi.');

            return;
        }
        $this->dispatch('toast', type: 'success', message: 'Courier updated successfully.');
        $this->dispatch('reset-parent-page');
        Flux::modal('defaultModal')->close();
    }
};
