<?php

use App\Actions\Cms\Product\Category\StoreCategoryAction;
use App\Actions\Cms\Product\Category\UpdateCategoryAction;
use App\Models\Product\ProductCategory;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $modelInstance = ProductCategory::class;

    public bool $isUpdate = false;

    #[On('set-action')]
    public function setAction(?int $id = null): void
    {
        $this->resetValidation();

        if ($id) {
            $this->isUpdate = true;
            $this->getRecordData($id);
        } else {
            $this->isUpdate = false;
            $this->resetRecordData();
        }
    }

    // Record data
    #[Locked]
    public ?int $id = null;

    public $name;

    public $description;

    public $is_featured;

    public $oldImage;

    public $image;

    // Get record data
    public function getRecordData(int $id): void
    {
        Gate::authorize('show'.$this->modelInstance);

        $record = ProductCategory::query()->findOrFail($id);
        $this->fill(
            $record->only(
                'id',
                'name',
                'description',
                'is_featured',
            )
        );
        $this->oldImage = $record->getFirstMediaUrl('image');
    }

    // Reset record data
    public function resetRecordData(): void
    {
        $this->reset([
            'id',
            'name',
            'description',
            'image',
            'oldImage',
        ]);
        $this->is_featured = false;
    }

    // Handle form submit
    public function submit(StoreCategoryAction $storeAction, UpdateCategoryAction $updateAction): void
    {
        Gate::authorize(($this->isUpdate ? 'update' : 'create').$this->modelInstance);

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_featured' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($this->isUpdate) {
            $updateAction->handle(
                category: ProductCategory::query()->findOrFail($this->id),
                data: $this->categoryData(),
            );
        } else {
            $storeAction->handle(
                data: $this->categoryData(),
            );
        }

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: $this->isUpdate ? 'Category updated successfully.' : 'Category created successfully.',
        );

        // Reset data table
        $this->dispatch('reset-parent-page');

        // Reset record data
        $this->resetRecordData();

        // Close modal
        Flux::modal('defaultModal')->close();
    }

    /** @return array{name: mixed, description: mixed, is_featured: mixed, image: mixed} */
    private function categoryData(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'is_featured' => $this->is_featured,
            'image' => $this->image,
        ];
    }
};
