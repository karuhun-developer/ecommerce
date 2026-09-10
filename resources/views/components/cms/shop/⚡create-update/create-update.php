<?php

use App\Actions\Cms\Shop\StoreShopAction;
use App\Actions\Cms\Shop\UpdateShopAction;
use App\Models\Shop\Shop;
use App\Models\User;
use App\Services\BiteshipService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $modelInstance = Shop::class;

    #[Locked]
    public bool $isUpdate = false;

    #[On('set-action')]
    public function setAction(int|string|null $id = null): void
    {
        $this->resetValidation();

        if ($id) {
            $this->isUpdate = true;
            $this->getRecordData($id);
        } else {
            $this->isUpdate = false;
            $this->resetRecordData();
        }
    }

    #[Locked]
    public $id;

    public $name;

    public $description;

    public $location_name;

    public $contact_name;

    public $contact_phone;

    public $address;

    public $note;

    public $postal_code;

    public $latitude;

    public $longitude;

    public $biteship_area_id;

    public $area_string;

    public $searchArea;

    public array $areas = [];

    public function getRecordData(int|string $id): void
    {
        Gate::authorize('show'.$this->modelInstance);

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $record = Shop::query()->accessibleTo($user)->findOrFail($id);
        $this->fill(
            $record->only(
                'id',
                'name',
                'description',
            )
        );

        // Set location details if available
        if ($record->location) {
            $this->location_name = $record->location->name;
            $this->contact_name = $record->location->contact_name;
            $this->contact_phone = $record->location->contact_phone;
            $this->address = $record->location->address;
            $this->note = $record->location->note;
            $this->postal_code = $record->location->postal_code;
            $this->latitude = $record->location->latitude;
            $this->longitude = $record->location->longitude;
            $this->biteship_area_id = $record->location->biteship_area_id;
            $this->area_string = $record->location->area_string;
            $this->searchArea = $record->location->area_string;
        }

        // Set jodit content
        $this->dispatch('update-jodit-content', $this->description);
    }

    public function resetRecordData(): void
    {
        $this->reset([
            'id', 'name', 'description', 'location_name', 'contact_name',
            'contact_phone', 'address', 'note', 'postal_code',
            'biteship_area_id', 'area_string', 'searchArea',
        ]);
        $this->latitude = null;
        $this->longitude = null;
        $this->areas = [];

        // Set jodit content
        $this->dispatch('update-jodit-content', '');
    }

    public function searchBiteshipArea(BiteshipService $biteshipService): void
    {
        $this->validate([
            'searchArea' => 'required|string|min:3',
        ]);

        try {
            $res = $biteshipService->getMapsAreas([
                'input' => $this->searchArea,
            ]);
            $this->areas = $res['areas'] ?? [];
        } catch (Throwable $exception) {
            report($exception);

            $this->dispatch('toast',
                type: 'error',
                message: 'Unable to search areas right now. Please try again.',
            );
        }
    }

    public function selectArea(string $id, string $name, string $postal_code): void
    {
        $this->biteship_area_id = $id;
        $this->area_string = $name;
        $this->postal_code = $postal_code;
        $this->searchArea = $name;
        $this->areas = [];
    }

    public function submit(StoreShopAction $storeAction, UpdateShopAction $updateAction): void
    {
        Gate::authorize('update'.$this->modelInstance);

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'address' => 'required|string',
            'note' => 'nullable|string',
            'postal_code' => 'required|numeric',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'biteship_area_id' => 'required|string',
        ]);

        $shop = null;

        if ($this->isUpdate) {
            $user = auth()->user();
            abort_unless($user instanceof User, 403);
            $shop = Shop::query()->accessibleTo($user)->findOrFail($this->id);
        }

        try {
            if ($shop) {
                $updateAction->handle(shop: $shop, data: $this->shopPayload());
            } else {
                $storeAction->handle(data: $this->shopPayload());
            }
        } catch (Throwable $exception) {
            report($exception);

            $this->dispatch(
                'toast',
                type: 'error',
                message: 'Unable to save the shop right now. Please try again.',
            );

            return;
        }

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: $this->isUpdate ? 'Shop updated successfully.' : 'Shop created successfully.',
        );

        // Reset data table
        $this->dispatch('reset-parent-page');

        // Close modal
        Flux::modal('defaultModal')->close();
    }

    private function shopPayload(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'location_name' => $this->location_name,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'address' => $this->address,
            'note' => $this->note,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'biteship_area_id' => $this->biteship_area_id,
            'area_string' => $this->area_string,
        ];
    }
};
