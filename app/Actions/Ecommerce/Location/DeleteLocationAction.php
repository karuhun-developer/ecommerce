<?php

namespace App\Actions\Ecommerce\Location;

use App\Models\Location\Location;
use App\Services\BiteshipService;
use Illuminate\Support\Facades\Log;

class DeleteLocationAction
{
    public function __construct(
        public readonly BiteshipService $biteshipService,
    ) {}

    /**
     * Handle the action.
     */
    public function handle(Location $location): bool
    {
        try {
            $this->biteshipService->deleteLocation($location->biteship_location_id);
        } catch (\Exception $e) {
            Log::error('Failed to delete biteship location: '.$e->getMessage());
            // Optionally, we can proceed to delete local record anyway
        }

        return $location->delete();
    }
}
