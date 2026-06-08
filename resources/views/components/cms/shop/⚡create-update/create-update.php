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
    public $modelInstance = Shop::class;
    public $isUpdate = false;

    public $id;
    public $name;
    public $description;

    public $location_name;
    public $contact_name;
    public $contact_phone;
    public $address;
    public $note;
    public $postal_code;
    public $latitude = null;
    public $longitude = null;
    public $biteship_area_id;
    public $area_string;

    public $searchArea = '';
    public $areas = [];

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

    public function getRecordData($id)
    {
        $shop = Shop::with('locations')->find($id);
        $this->id = $shop->id;
        $this->name = $shop->name;
        $this->description = $shop->description;

        $location = $shop->locations->first();
        if ($location) {
            $this->location_name = $location->name;
            $this->contact_name = $location->contact_name;
            $this->contact_phone = $location->contact_phone;
            $this->address = $location->address;
            $this->note = $location->note;
            $this->postal_code = $location->postal_code;
            $this->latitude = $location->latitude;
            $this->longitude = $location->longitude;
            $this->biteship_area_id = $location->biteship_area_id;
            $this->area_string = $location->area_string;
            $this->searchArea = $location->area_string;
        }
    }

    public function resetRecordData()
    {
        $this->reset([
            'id', 'name', 'description', 'location_name', 'contact_name',
            'contact_phone', 'address', 'note', 'postal_code',
            'biteship_area_id', 'area_string', 'searchArea', 'areas'
        ]);
        $this->latitude = null;
        $this->longitude = null;
    }

    public function searchBiteshipArea(BiteshipService $biteshipService)
    {
        $this->validate([
            'searchArea' => 'required|string|min:3',
        ]);

        try {
            $res = $biteshipService->getMapsAreas(['input' => $this->searchArea]);
            $this->areas = $res['areas'] ?? [];
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: 'Failed to search areas: ' . $e->getMessage());
        }
    }

    public function selectArea($id, $name, $postal_code)
    {
        $this->biteship_area_id = $id;
        $this->area_string = $name;
        $this->postal_code = $postal_code;
        $this->areas = [];
        $this->searchArea = $name;
    }

    public function submit(StoreShopAction $storeAction, UpdateShopAction $updateAction)
    {
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
            $shop = Shop::findOrFail($this->id);
            $updateAction->handle($shop, $this->all());
        } else {
            $storeAction->handle($this->all());
        }

        $this->dispatch('toast',
            type: 'success',
            message: $this->isUpdate ? 'Shop updated successfully.' : 'Shop created successfully.',
        );

        $this->dispatch('reset-parent-page');
        Flux::modal('defaultModal')->close();
    }
};
