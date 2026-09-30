<?php

namespace App\Actions\Cms\Product\Category;

use App\Models\Product\ProductCategory;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteCategoryAction
{
    public function handle(ProductCategory $category, User $actor): bool
    {
        Gate::forUser($actor)->authorize('delete'.ProductCategory::class);

        return $category->delete();
    }
}
