<?php

use App\Actions\Cms\Dashboard\GetDashboardAction;
use App\Data\Dashboard\DashboardData;
use App\Data\Dashboard\DashboardFilterData;
use App\Livewire\Forms\Cms\DashboardFilterForm;
use App\Models\Shop\Shop;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    public DashboardFilterForm $form;

    #[Locked]
    public string $appliedStart = '';

    #[Locked]
    public string $appliedEnd = '';

    #[Locked]
    public ?int $appliedShopId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['superadmin', 'shopowner']), 403);
        $this->form->defaults();
        $this->apply();
    }

    public function apply(): void
    {
        $data = $this->form->data();
        if ($data->shopId !== null) {
            Shop::query()->accessibleTo(auth()->user())->findOrFail($data->shopId);
        }
        $this->appliedStart = $data->start->toDateString();
        $this->appliedEnd = $data->end->toDateString();
        $this->appliedShopId = $data->shopId;
        unset($this->dashboard);
    }

    #[Computed]
    public function shops(): Collection
    {
        return Shop::query()->accessibleTo(auth()->user())->orderBy('name')->get();
    }

    #[Computed]
    public function dashboard(): DashboardData
    {
        return app(GetDashboardAction::class)->handle(new DashboardFilterData(CarbonImmutable::parse($this->appliedStart)->startOfDay(), CarbonImmutable::parse($this->appliedEnd)->endOfDay(), $this->appliedShopId), auth()->user());
    }
};
