<?php

namespace App\Actions\Cms\Product\Product;

use App\Models\Product\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteProductAction
{
    /**
     * Handle the action.
     */
    public function handle(Product $product): void
    {
        Gate::authorize('delete'.Product::class);

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $product = Product::query()
            ->accessibleTo($user)
            ->with('productFlats')
            ->findOrFail($product->getKey());

        DB::transaction(function () use ($product) {
            foreach ($product->productFlats as $flat) {
                $flat->clearMediaCollection('images');
                $flat->delete();
            }

            $product->delete();
        });
    }
}
