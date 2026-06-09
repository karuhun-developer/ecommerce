<?php

namespace App\Actions\Cms\Shop;

use App\Models\Shop\Shop;
use App\Services\BiteshipService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteShopAction
{
    public function __construct(
        public readonly BiteshipService $biteshipService,
    ) {}

    /**
     * Handle the action.
     */
    public function handle(Shop $shop): bool
    {
        return DB::transaction(function () use ($shop) {
            if ($shop->location) {
                try {
                    $this->biteshipService->deleteLocation($shop->location->biteship_location_id);
                } catch (\Exception $e) {
                    Log::error('Failed to delete biteship location: '.$e->getMessage());
                    // Optionally, we can proceed to delete local record anyway
                }

                // Delete local location record
                $shop->location->delete();
            }

            return $shop->delete();
        });
    }
}
