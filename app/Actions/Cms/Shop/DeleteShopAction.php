<?php

namespace App\Actions\Cms\Shop;

use App\Actions\Ecommerce\Location\DeleteLocationAction;
use App\Models\Shop\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteShopAction
{
    public function __construct(private readonly DeleteLocationAction $deleteLocationAction) {}

    public function handle(Shop $shop, User $actor): bool
    {
        Gate::forUser($actor)->authorize('delete'.Shop::class);
        $shop = Shop::query()->accessibleTo($actor)->with('location')->findOrFail($shop->getKey());

        return DB::transaction(function () use ($shop, $actor): bool {
            if ($shop->location) {
                $this->deleteLocationAction->handle($shop->location, $actor);
            }

            return $shop->delete();
        });
    }
}
