<?php

namespace App\Actions\Cms\Shop;

use App\Actions\Ecommerce\Location\DeleteLocationAction;
use App\Models\Shop\Shop;
use Illuminate\Support\Facades\DB;

class DeleteShopAction
{
    public function __construct(
        public readonly DeleteLocationAction $deleteLocationAction,
    ) {}

    /**
     * Handle the action.
     */
    public function handle(Shop $shop): bool
    {
        return DB::transaction(function () use ($shop) {
            $this->deleteLocationAction->handle($shop->location);

            return $shop->delete();
        });
    }
}
