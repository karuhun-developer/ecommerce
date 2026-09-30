<?php

namespace App\Livewire\Forms\Cms;

use App\Data\Content\StorefrontData;
use App\Models\Setting\Setting;
use Livewire\Form;

class StorefrontForm extends Form
{
    public string $brand_name = '';

    public ?string $tagline = null;

    public ?string $instagram_url = null;

    public ?string $website_url = null;

    public ?string $whatsapp_url = null;

    public function load(): void
    {
        $this->brand_name = config('app.name');
        $setting = Setting::query()->where('key', 'storefront')->first();
        if ($setting) {
            $this->fill($setting->data);
        }
    }

    public function data(): StorefrontData
    {
        $data = $this->validate([
            'brand_name' => ['required', 'string', 'max:255'], 'tagline' => ['nullable', 'string', 'max:2000'],
            'instagram_url' => ['nullable', 'url:http,https', 'max:2048'],
            'website_url' => ['nullable', 'url:http,https', 'max:2048'],
            'whatsapp_url' => ['nullable', 'url:http,https', 'max:2048'],
        ]);

        return new StorefrontData($data['brand_name'], $data['tagline'] ?: null, $data['instagram_url'] ?: null, $data['website_url'] ?: null, $data['whatsapp_url'] ?: null);
    }
}
