<?php

namespace App\Actions\Cms\Shop;

use App\Models\Shop\Shop;
use App\Services\BiteshipService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteShopAction
{
    public function handle(Shop $shop): bool
    {
        return DB::transaction(function () use ($shop) {
            $location = $shop->locations()->first();

            if ($location && $location->biteship_location_id) {
                try {
                    $biteshipService = new BiteshipService;
                    $biteshipService->deleteLocation($location->biteship_location_id);
                } catch (\Exception $e) {
                    Log::error('Failed to delete biteship location: '.$e->getMessage());
                    // Optionally, we can proceed to delete local record anyway
                }
            }

            if ($location) {
                $location->delete();
            }

            return $shop->delete();
        });
    }
}
