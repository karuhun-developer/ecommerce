<?php

namespace App\Actions\Cms\Shop;

use App\Actions\Ecommerce\Location\UpdateLocationAction;
use App\Models\Shop\Shop;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($shop, $data) {
            $shop->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            // Update the location for the shop
            $this->updateLocationAction->handle($shop->location, $data);

            return $shop->fresh();
        });
    }
}
