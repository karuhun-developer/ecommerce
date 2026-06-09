<?php

namespace App\Actions\Cms\Shop;

use App\Models\Location\Location;
use App\Models\Shop\Shop;
use App\Services\BiteshipService;
use Illuminate\Support\Facades\DB;

class StoreShopAction
{
    public function __construct(
        public readonly BiteshipService $biteshipService,
    ) {}

    /**
     * Handle the action.
     */
    public function handle(array $data): Shop
    {
        return DB::transaction(function () use ($data) {
            $shop = Shop::create([
                'user_id' => auth()->id(),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            // Create location in Biteship and local database
            $biteshipLocation = $this->biteshipService->createLocation([
                'name' => $data['location_name'],
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'address' => $data['address'],
                'note' => $data['note'] ?? '',
                'postal_code' => (int) $data['postal_code'],
                'latitude' => (float) $data['latitude'],
                'longitude' => (float) $data['longitude'],
                'type' => 'origin',
            ]);

            Location::create([
                'user_id' => auth()->id(),
                'shop_id' => $shop->id,
                'biteship_location_id' => $biteshipLocation['id'] ?? null,
                'biteship_area_id' => $data['biteship_area_id'],
                'area_string' => $data['area_string'],
                'name' => $data['location_name'],
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'address' => $data['address'],
                'note' => $data['note'] ?? null,
                'postal_code' => $data['postal_code'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'type' => 'origin',
            ]);

            return $shop;
        });
    }
}
