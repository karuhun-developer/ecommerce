<?php

use App\Actions\Ecommerce\Location\StoreLocationAction;
use App\Actions\Ecommerce\Location\UpdateLocationAction;
use App\Livewire\Forms\LocationForm;
use App\Models\Location\Location;
use App\Models\User;
use App\Services\BiteshipService;
use App\Services\CourierSettingsService;
use Flux\Flux;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public ?int $id = null;

    public LocationForm $form;

    public string $searchArea = '';

    #[Locked]
    public array $areas = [];

    #[Computed]
    public function requiresArea(): bool
    {
        return app(CourierSettingsService::class)->usesAreaIds();
    }

    #[On('shipping-edit')]
    public function loadForEdit(int $id): void
    {
        $record = Location::query()->where('user_id', auth()->id())->where('type', 'destination')->findOrFail($id);
        $this->resetValidation();
        $this->id = $record->id;
        $this->form->setLocation($record);
        $this->searchArea = $this->form->area_string ?? '';
        $this->areas = [];
        Flux::modal('shippingFormModal')->show();
    }

    #[On('shipping-create')]
    public function openCreate(): void
    {
        abort_unless(auth()->check(), 403);
        $this->resetValidation();
        $this->id = null;
        $this->form->reset();
        $this->searchArea = '';
        $this->areas = [];
        Flux::modal('shippingFormModal')->show();
    }

    public function searchBiteshipArea(BiteshipService $biteshipService): void
    {
        $this->validate(['searchArea' => ['required', 'string', 'min:3', 'max:255']]);
        try {
            $this->areas = $biteshipService->getMapsAreas(['input' => $this->searchArea])['areas'] ?? [];
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatch('toast', type: 'error', message: 'Gagal mencari area. Silakan coba lagi.');
        }
    }

    public function selectArea(string $id, string $name, string $postal_code): void
    {
        $area = collect($this->areas)->firstWhere('id', $id);
        abort_unless($area, 422);
        $this->form->biteship_area_id = $area['id'];
        $this->form->area_string = $area['name'];
        $this->form->postal_code = (string) ($area['postal_code'] ?? '');
        $this->searchArea = $area['name'];
        $this->areas = [];
    }

    public function submit(StoreLocationAction $storeAction, UpdateLocationAction $updateAction): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);
        $data = $this->form->data();
        if ($this->id) {
            $location = Location::query()->where('user_id', $actor->id)->where('type', 'destination')->findOrFail($this->id);
            $updateAction->handle($location, $data, $actor);
        } else {
            $storeAction->handle($data, $actor);
        }
        $this->dispatch('toast', type: 'success', message: $this->id ? 'Alamat berhasil diperbarui.' : 'Alamat berhasil ditambahkan.');
        $this->dispatch('shipping-list-refresh');
        Flux::modal('shippingFormModal')->close();
    }
};
