<?php

use App\Actions\Cms\Shop\StoreShopAction;
use App\Actions\Cms\Shop\UpdateShopAction;
use App\Models\Shop\Shop;
use App\Services\BiteshipService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    // Model instance
    public $modelInstance = Shop::class;

    public function mount()
    {
        Gate::authorize('view'.$this->modelInstance);

        $this->loadDefaultShop();
    }

    // Record data
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

    public $areas = [];

    public function loadDefaultShop()
    {
        $record = Shop::first();

        // If no shop record exists, we can choose to either create a default one or simply return without setting data.
        if (! $record) {
            return;
        }

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
        }
    }

    // Biteship area search
    public function searchBiteshipArea(BiteshipService $biteshipService)
    {
        $this->validate([
            'searchArea' => 'required|string|min:3',
        ]);

        try {
            $res = $biteshipService->getMapsAreas([
                'input' => $this->searchArea,
            ]);
            $this->areas = $res['areas'] ?? [];
        } catch (Exception $e) {
            // Toast message
            $this->dispatch('toast',
                type: 'error',
                message: 'Failed to search areas: '.$e->getMessage()
            );
        }
    }

    // Select area from search results
    public function selectArea($id, $name, $postal_code)
    {
        $this->biteship_area_id = $id;
        $this->area_string = $name;
        $this->postal_code = $postal_code;
        $this->searchArea = $name;
        $this->areas = [];
    }

    public function submit(StoreShopAction $storeAction, UpdateShopAction $updateAction)
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

        // Create or update shop based on presence of ID
        if ($this->id) {
            $updateAction->handle(
                shop: Shop::findOrFail($this->id),
                data: $this->all(),
            );

            $message = 'Shop updated successfully.';
        } else {
            $storeAction->handle(
                data: $this->all(),
            );

            $message = 'Shop created successfully.';
            $this->loadDefaultShop();
        }

        // Forget default shop cache to reflect changes immediately
        Cache::forget('default:shop');

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: $message,
        );
    }
};
