<?php

namespace App\Actions\Cms\Shop;

use App\Actions\Ecommerce\Location\StoreLocationAction;
use App\Data\Cms\ShopData;
use App\Models\Shop\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StoreShopAction
{
    public function __construct(private readonly StoreLocationAction $storeLocationAction) {}

    public function handle(ShopData $data, User $actor): Shop
    {
        Gate::forUser($actor)->authorize('create'.Shop::class);

        return DB::transaction(function () use ($data, $actor): Shop {
            $shop = Shop::create(['user_id' => $actor->id, 'name' => $data->name, 'description' => $data->description]);
            $this->storeLocationAction->handle($data->location->forShop($shop->id), $actor);

            return $shop;
        });
    }
}
