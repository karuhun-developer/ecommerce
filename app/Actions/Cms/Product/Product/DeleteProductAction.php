<?php

namespace App\Actions\Cms\Product\Product;

use App\Models\Product\Product;
use Illuminate\Support\Facades\DB;

class DeleteProductAction
{
    /**
     * Handle the action.
     */
    public function handle(Product $product): void
    {
        DB::transaction(function () use ($product) {
            // Delete product flats individually to trigger Spatie Media Library deletion
            foreach ($product->productFlats as $flat) {
                // Ensure media collections are removed
                $flat->clearMediaCollection('images');
                $flat->delete();
            }

            // ProductAttributeGroup and ProductAttribute should be deleted via database cascade
            // However, to be safe, eloquent delete could be used or just let DB handle it.
            // Since we only need Spatie Media events for Flats, DB cascade is fine for attributes.

            $product->delete();
        });
    }
}
