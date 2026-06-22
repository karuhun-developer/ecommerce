<?php

namespace App\Actions\Cms\Product\Category;

use App\Models\Product\ProductCategory;
use App\Traits\WithMediaCollection;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UpdateCategoryAction
{
    use WithMediaCollection;

    /**
     * Handle the action.
     */
    public function handle(ProductCategory $category, array $data): ProductCategory
    {
        // Upload the image if provided
        $image = $data['image'] ?? null;
        if ($image instanceof UploadedFile || $image instanceof TemporaryUploadedFile) {
            $this->saveMedia(
                model: $category,
                file: $image,
                collection: 'image',
            );
        }

        $category->update($data);

        return $category->fresh();
    }
}
