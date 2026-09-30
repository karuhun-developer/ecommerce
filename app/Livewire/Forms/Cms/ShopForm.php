<?php

namespace App\Livewire\Forms\Cms;

use App\Data\Cms\ShopData;
use App\Data\Location\LocationData;
use App\Livewire\Forms\LocationForm;
use App\Models\Shop\Shop;

class ShopForm extends LocationForm
{
    public string $name = '';

    public ?string $description = null;

    /** @return array<string, list<string>> */
    protected function rules(): array
    {
        return [...parent::rules(), 'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:50000']];
    }

    public function setShop(Shop $shop): void
    {
        $this->name = $shop->name;
        $this->description = $shop->description;
        if ($shop->location) {
            $this->setLocation($shop->location);
        }
    }

    public function shopData(): ShopData
    {
        $data = $this->validate();

        return new ShopData($data['name'], $data['description'], LocationData::fromArray($data, 'origin'));
    }
}
