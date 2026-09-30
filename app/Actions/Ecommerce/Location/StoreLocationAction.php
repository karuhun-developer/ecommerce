<?php

namespace App\Actions\Ecommerce\Location;

use App\Data\Location\LocationData;
use App\Models\Location\Location;
use App\Models\Shop\Shop;
use App\Models\User;
use App\Services\BiteshipService;
use Illuminate\Support\Facades\Gate;

class StoreLocationAction
{
    public function __construct(private readonly BiteshipService $biteshipService) {}

    public function handle(LocationData $data, User $actor): Location
    {
        if ($data->shop_id !== null) {
            Gate::forUser($actor)->authorize('create'.Shop::class);
            Shop::query()->accessibleTo($actor)->findOrFail($data->shop_id);
            abort_unless($data->type === 'origin', 422);
        } else {
            abort_unless($data->type === 'destination', 422);
        }

        $providerLocation = $this->biteshipService->createLocation($data->providerPayload());

        return Location::create([
            ...$data->attributes(),
            'user_id' => $actor->id,
            'shop_id' => $data->shop_id,
            'biteship_location_id' => $providerLocation['id'] ?? null,
        ]);
    }
}
