<?php

namespace App\Actions\Ecommerce\Location;

use App\Models\Location\Location;
use App\Services\BiteshipService;

class StoreLocationAction
{
    public function __construct(
        public readonly BiteshipService $biteshipService,
    ) {}

    /**
     * Handle the action.
     */
    public function handle(array $data): Location
    {
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

        $location = Location::create([
            'user_id' => auth()->id(),
            'shop_id' => $data['shop_id'] ?? null,
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
            'type' => $data['type'] ?? 'origin', // Default to 'origin' if not provided // origin or destination
        ]);

        return $location;
    }
}
