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
    public function handle(Product $product, User $actor): void
    {
        Gate::forUser($actor)->authorize('delete'.Product::class);

        $product = Product::query()
            ->accessibleTo($actor)
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
