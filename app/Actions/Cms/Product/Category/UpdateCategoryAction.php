<?php

namespace App\Actions\Cms\Product\Category;

use App\Data\Cms\CategoryData;
use App\Models\Product\ProductCategory;
use App\Models\User;
use App\Traits\WithMediaCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UpdateCategoryAction
{
    use WithMediaCollection;

    public function handle(ProductCategory $category, CategoryData $data, User $actor): ProductCategory
    {
        Gate::forUser($actor)->authorize('update'.ProductCategory::class);

        $image = $data->image;
        if ($image instanceof UploadedFile || $image instanceof TemporaryUploadedFile) {
            $this->saveMedia(
                model: $category,
                file: $image,
                collection: 'image',
            );
        }

        $category->update($data->attributes());

        return $category->fresh();
    }
}
