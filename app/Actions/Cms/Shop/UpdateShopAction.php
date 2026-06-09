<?php

namespace App\Actions\Cms\Shop;

use App\Models\Shop\Shop;
use App\Services\BiteshipService;
use Illuminate\Support\Facades\DB;

class UpdateShopAction
{
    public function __construct(
        public readonly BiteshipService $biteshipService,
    ) {}

    /**
     * Handle the action.
     */
    public function handle(Shop $shop, array $data): Shop
    {
        return DB::transaction(function () use ($shop, $data) {
            $shop->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            if ($shop->location) {
                $this->biteshipService->updateLocation($shop->location->biteship_location_id, [
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
            }

            $shop->location->update([
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
            ]);

            return $shop->fresh();
        });
    }
}
