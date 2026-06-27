<?php

use App\Actions\Ecommerce\Location\StoreLocationAction;
use App\Actions\Ecommerce\Location\UpdateLocationAction;
use App\Models\Location\Location;
use App\Services\BiteshipService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // Record data
    public $id;

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

    /** @var array<int, array<string, mixed>> */
    public array $areas = [];

    #[On('shipping-edit')]
    public function loadForEdit(int $id): void
    {
        $record = Location::where('user_id', auth()->id())->findOrFail($id);

        $this->id = $record->id;
        $this->location_name = $record->name;
        $this->fill(
            $record->only([
                'contact_name',
                'contact_phone',
                'address',
                'note',
                'postal_code',
                'latitude',
                'longitude',
                'biteship_area_id',
                'area_string',
            ])
        );
        $this->searchArea = $this->area_string;
        $this->areas = [];

        // Open the modal
        Flux::modal('shippingFormModal')->show();
    }

    #[On('shipping-create')]
    public function openCreate(): void
    {
        // Reset all fields
        $this->reset();
        $this->areas = [];

        // Open the modal
        Flux::modal('shippingFormModal')->show();
    }

    // Search for areas using Biteship API
    public function searchBiteshipArea(BiteshipService $biteshipService)
    {
        $this->validate([
            'searchArea' => 'required|string|min:3',
        ]);

        try {
            $res = $biteshipService->getMapsAreas(['input' => $this->searchArea]);
            $this->areas = $res['areas'] ?? [];
        } catch (Exception $e) {
            // Toast error message
            $this->dispatch('toast',
                type: 'error',
                message: 'Gagal mencari area: '.$e->getMessage(),
            );
        }
    }

    // Select an area from the search results
    public function selectArea(string $id, string $name, string $postalCode)
    {
        $this->biteship_area_id = $id;
        $this->area_string = $name;
        $this->postal_code = $postalCode;
        $this->searchArea = $name;
        $this->areas = [];
    }

    public function submit(StoreLocationAction $storeAction, UpdateLocationAction $updateAction)
    {
        $this->validate([
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

        // Update or create the location record
        if ($this->id) {
            $updateAction->handle(
                location: Location::where('user_id', auth()->id())->findOrFail($this->id),
                data: [
                    ...$this->all(),
                    'type' => 'destination',
                ]
            );
        } else {
            $storeAction->handle(
                data: [
                    ...$this->all(),
                    'user_id' => auth()->id(),
                    'type' => 'destination',
                ]
            );
        }

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: $this->id ? 'Alamat berhasil diperbarui.' : 'Alamat berhasil ditambahkan.',
        );

        // Reset shipping list
        $this->dispatch('shipping-list-refresh');

        // Close modal
        Flux::modal('shippingFormModal')->close();
    }
};
