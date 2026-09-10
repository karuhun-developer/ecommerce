<?php

namespace App\Actions\Cms\Shop;

use App\Actions\Ecommerce\Location\StoreLocationAction;
use App\Models\Shop\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StoreShopAction
{
    public function __construct(
        public readonly StoreLocationAction $storeLocationAction,
    ) {}

    /**
     * Handle the action.
     */
    public function handle(array $data): Shop
    {
        Gate::authorize('update'.Shop::class);

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return DB::transaction(function () use ($data, $user) {
            $shop = Shop::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            $this->storeLocationAction->handle([
                ...$this->locationPayload($data),
                'shop_id' => $shop->id,
                'type' => 'origin',
            ]);

            return $shop;
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
