<?php

namespace App\Actions\Cms\Shop;

use App\Models\Shop\Shop;
use App\Services\BiteshipService;
use Illuminate\Support\Facades\DB;

class UpdateShopAction
{
    public function handle(Shop $shop, array $data): Shop
    {
        return DB::transaction(function () use ($shop, $data) {
            $shop->update([
                'name' => $data['name'],
                'slug' => \Str::slug($data['name']),
                'description' => $data['description'] ?? null,
            ]);

            $location = $shop->locations()->first();

            if ($location) {
                $biteshipService = new BiteshipService;
                if ($location->biteship_location_id) {
                    $biteshipService->updateLocation($location->biteship_location_id, [
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

                $location->update([
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
            }

            return $shop->fresh();
        });
    }
}
