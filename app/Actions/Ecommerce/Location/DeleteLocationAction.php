<?php

namespace App\Actions\Ecommerce\Location;

use App\Models\Location\Location;
use App\Models\Shop\Shop;
use App\Models\User;
use App\Services\BiteshipService;
use Illuminate\Support\Facades\Gate;
use Throwable;

class DeleteLocationAction
{
    public function __construct(private readonly BiteshipService $biteshipService) {}

    public function handle(Location $location, User $actor): bool
    {
        if ($location->shop_id !== null) {
            Gate::forUser($actor)->authorize('delete'.Shop::class);
            Shop::query()->accessibleTo($actor)->findOrFail($location->shop_id);
        } else {
            abort_unless($location->user_id === $actor->id && $location->type === 'destination', 404);
        }

        if ($location->biteship_location_id) {
            try {
                $this->biteshipService->deleteLocation($location->biteship_location_id);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $location->delete();
    }
}
