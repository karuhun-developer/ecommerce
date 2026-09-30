<?php

namespace App\Actions\Ecommerce\Location;

use App\Data\Location\LocationData;
use App\Models\Location\Location;
use App\Models\Shop\Shop;
use App\Models\User;
use App\Services\BiteshipService;
use Illuminate\Support\Facades\Gate;

class UpdateLocationAction
{
    public function __construct(private readonly BiteshipService $biteshipService) {}

    public function handle(Location $location, LocationData $data, User $actor): bool
    {
        if ($location->shop_id !== null) {
            Gate::forUser($actor)->authorize('update'.Shop::class);
            Shop::query()->accessibleTo($actor)->findOrFail($location->shop_id);
            abort_unless($data->shop_id === $location->shop_id && $data->type === 'origin', 422);
        } else {
            abort_unless($location->user_id === $actor->id && $location->type === 'destination', 404);
            abort_unless($data->shop_id === null && $data->type === 'destination', 422);
        }

        $this->biteshipService->updateLocation($location->biteship_location_id, $data->providerPayload());

        return $location->update($data->attributes());
    }
}
