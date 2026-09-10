<?php

namespace App\Actions\Cms\Shop;

use App\Actions\Ecommerce\Location\DeleteLocationAction;
use App\Models\Shop\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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
        Gate::authorize('delete'.Shop::class);

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $shop = Shop::query()
            ->accessibleTo($user)
            ->with('location')
            ->findOrFail($shop->getKey());

        return DB::transaction(function () use ($shop) {
            if ($shop->location) {
                $this->deleteLocationAction->handle($shop->location);
            }

            return $shop->delete();
        });
    }
}
