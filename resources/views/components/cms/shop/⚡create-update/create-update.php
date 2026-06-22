<?php

use App\Actions\Cms\Shop\StoreShopAction;
use App\Actions\Cms\Shop\UpdateShopAction;
use App\Models\Shop\Shop;
use App\Services\BiteshipService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // Model instance
    public $modelInstance = Shop::class;

    public $isUpdate = false;

    #[On('set-action')]
    public function setAction($id = null)
    {
        if ($id) {
            $this->isUpdate = true;
            $this->getRecordData($id);
        } else {
            $this->isUpdate = false;
            $this->resetRecordData();
        }
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

    public function getRecordData($id)
    {
        Gate::authorize('show'.$this->modelInstance);

        $record = Shop::findOrFail($id);
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

    public function resetRecordData()
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

        if ($this->isUpdate) {
            $updateAction->handle(
                shop: Shop::findOrFail($this->id),
                data: $this->all(),
            );
        } else {
            $storeAction->handle(
                data: $this->all(),
            );
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
};
