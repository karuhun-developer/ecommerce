<?php

use App\Actions\Cms\Shipping\UpdateShippingRateSettingsAction;
use App\Data\Cms\ShippingRateSettingsData;
use App\Livewire\BaseComponent;
use App\Models\Setting\Setting;
use App\Services\CourierSettingsService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

new class extends BaseComponent
{
    #[Locked]
    public string $modelInstance = Setting::class;

    #[Locked]
    public array $searchBy = [
        ['name' => 'Name', 'field' => 'name'],
        ['name' => 'Code', 'field' => 'code'],
        ['name' => 'Services', 'field' => 'services'],
        ['name' => 'Status', 'field' => 'enabled'],
    ];

    public string $rateMethod = 'coordinates';

    public function mount(CourierSettingsService $courierSettings): void
    {
        Gate::authorize('view'.$this->modelInstance);
        $this->paginationOrderBy = 'name';
        $this->rateMethod = $courierSettings->rateMethod();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPaginate(): void
    {
        $this->resetPage();
    }

    public function saveRateMethod(UpdateShippingRateSettingsAction $action): void
    {
        Gate::authorize('update'.$this->modelInstance);
        $this->validate(['rateMethod' => ['required', Rule::in(CourierSettingsService::RATE_METHODS)]]);
        $action->handle(new ShippingRateSettingsData($this->rateMethod), auth()->user());
        $this->dispatch('toast', type: 'success', message: 'Shipping rate method updated successfully.');
    }

    public function render(): View
    {
        Gate::authorize('view'.$this->modelInstance);
        $this->validate([
            'paginate' => ['required', Rule::in([10, 25, 50, 100])],
            'paginationOrderBy' => ['required', Rule::in(array_column($this->searchBy, 'field'))],
            'paginationOrder' => ['required', Rule::in(['asc', 'desc'])],
        ]);

        $courierSettings = app(CourierSettingsService::class);
        $error = '';
        try {
            $enabled = $courierSettings->enabledCodes();
            $couriers = collect($courierSettings->couriers())
                ->map(fn (array $courier): array => [...$courier, 'enabled' => in_array($courier['code'], $enabled, true)])
                ->filter(fn (array $courier): bool => $this->search === '' || str_contains(mb_strtolower($courier['name'].' '.$courier['code'].' '.$courier['services']), mb_strtolower($this->search)))
                ->sortBy($this->paginationOrderBy, SORT_REGULAR, $this->paginationOrder === 'desc')
                ->values();
        } catch (Throwable $exception) {
            report($exception);
            $error = 'Gagal mengambil daftar kurir. Silakan coba lagi.';
            $couriers = collect();
        }

        $data = new LengthAwarePaginator($couriers->forPage($this->getPage(), $this->paginate)->values(), $couriers->count(), $this->paginate, $this->getPage());

        return $this->view(compact('data', 'error'));
    }
};
