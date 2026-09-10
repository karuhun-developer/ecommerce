<?php

namespace App\Actions\Cms\Shop;

use App\Actions\Ecommerce\Location\UpdateLocationAction;
use App\Models\Shop\Shop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateShopAction
{
    public function __construct(
        public readonly UpdateLocationAction $updateLocationAction,
    ) {}

    /**
     * Handle the action.
     */
    public function handle(Shop $shop, array $data): Shop
    {
        Gate::authorize('update'.Shop::class);

        return DB::transaction(function () use ($shop, $data) {
            $shop->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            abort_unless($shop->location, 404);

            $this->updateLocationAction->handle($shop->location, $this->locationPayload($data));

            return $shop->fresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function locationPayload(array $data): array
    {
        return [
            'location_name' => $data['location_name'],
            'contact_name' => $data['contact_name'],
            'contact_phone' => $data['contact_phone'],
            'address' => $data['address'],
            'note' => $data['note'] ?? null,
            'postal_code' => $data['postal_code'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'biteship_area_id' => $data['biteship_area_id'],
            'area_string' => $data['area_string'] ?? null,
        ];
    }
}
