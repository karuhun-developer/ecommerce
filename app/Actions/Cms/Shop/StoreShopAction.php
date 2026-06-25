<?php

namespace App\Actions\Cms\Shop;

use App\Actions\Ecommerce\Location\StoreLocationAction;
use App\Models\Shop\Shop;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($data) {
            $shop = Shop::create([
                'user_id' => auth()->id(),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            // Store the location for the shop
            $data['shop_id'] = $shop->id;
            $data['type'] = 'origin'; // Set the type to 'origin' for the location
            $this->storeLocationAction->handle($data);

            return $shop;
        });
    }
}
