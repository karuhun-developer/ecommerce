<?php

namespace App\Actions\Cms\Shop;

use App\Actions\Ecommerce\Location\UpdateLocationAction;
use App\Data\Cms\ShopData;
use App\Models\Shop\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateShopAction
{
    public function __construct(private readonly UpdateLocationAction $updateLocationAction) {}

    public function handle(Shop $shop, ShopData $data, User $actor): Shop
    {
        Gate::forUser($actor)->authorize('update'.Shop::class);
        $shop = Shop::query()->accessibleTo($actor)->with('location')->findOrFail($shop->getKey());

        return DB::transaction(function () use ($shop, $data, $actor): Shop {
            abort_unless($shop->location, 404);
            $shop->update(['name' => $data->name, 'description' => $data->description]);
            $this->updateLocationAction->handle($shop->location, $data->location->forShop($shop->id), $actor);

            return $shop->fresh();
        });
    }
}
