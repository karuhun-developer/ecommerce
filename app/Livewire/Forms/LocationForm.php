<?php

namespace App\Livewire\Forms;

use App\Data\Location\LocationData;
use App\Models\Location\Location;
use App\Services\CourierSettingsService;
use Livewire\Form;

class LocationForm extends Form
{
    public string $location_name = '';

    public string $contact_name = '';

    public string $contact_phone = '';

    public string $address = '';

    public ?string $note = null;

    public string $postal_code = '';

    public int|float|string|null $latitude = null;

    public int|float|string|null $longitude = null;

    public ?string $biteship_area_id = null;

    public ?string $area_string = null;

    /** @return array<string, list<string>> */
    protected function rules(): array
    {
        return [
            'location_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:5000'],
            'note' => ['nullable', 'string', 'max:1000'],
            'postal_code' => ['required', 'regex:/^[0-9]{5}$/'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'biteship_area_id' => [app(CourierSettingsService::class)->usesAreaIds() ? 'required' : 'nullable', 'string', 'max:255'],
            'area_string' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function setLocation(Location $location): void
    {
        $this->fill($location->only(['contact_name', 'contact_phone', 'address', 'note', 'postal_code', 'latitude', 'longitude', 'biteship_area_id', 'area_string']));
        $this->location_name = $location->name;
    }

    public function data(string $type = 'destination'): LocationData
    {
        return LocationData::fromArray($this->validate(), $type);
    }
}
