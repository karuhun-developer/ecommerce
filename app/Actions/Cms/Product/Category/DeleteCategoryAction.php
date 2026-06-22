<?php

namespace App\Actions\Cms\Product\Category;

use App\Models\Product\ProductCategory;

class DeleteCategoryAction
{
    /**
     * Handle the action.
     */
    public function handle(ProductCategory $category): bool
    {
        return $category->delete();
    }
}
