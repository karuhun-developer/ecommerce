<?php

namespace App\Actions\Ecommerce\Location;

use App\Models\Location\Location;
use App\Services\BiteshipService;

class UpdateLocationAction
{
    public function __construct(
        public readonly BiteshipService $biteshipService,
    ) {}

    /**
     * Handle the action.
     */
    public function handle(Location $location, array $data): bool
    {
        $this->biteshipService->updateLocation($location->biteship_location_id, [
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

        return $location->update([
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
}
