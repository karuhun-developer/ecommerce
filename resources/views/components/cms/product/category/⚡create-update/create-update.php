<?php

use App\Actions\Cms\Product\Category\StoreCategoryAction;
use App\Actions\Cms\Product\Category\UpdateCategoryAction;
use App\Models\Product\ProductCategory;
use App\Models\Shop\Shop;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    // Model instance
    public $modelInstance = ProductCategory::class;

    public $isUpdate = false;

    #[On('set-action')]
    public function setAction($id = null)
    {
        if ($id) {
            $this->isUpdate = true;
            $this->getRecordData($id);
        } else {
            $this->isUpdate = false;
            $this->resetRecordData();
        }
    }

    // Record data
    public $id;

    public $shop_id;

    public $name;

    public $description;

    public $oldImage;

    public $image;

    #[Computed]
    public function shops()
    {
        return Shop::orderBy('name')->get();
    }

    // Get record data
    public function getRecordData($id)
    {
        Gate::authorize('show'.$this->modelInstance);

        $record = ProductCategory::find($id);
        $this->fill(
            $record->only(
                'id',
                'shop_id',
                'name',
                'description',
            )
        );
        $this->oldImage = $record->getFirstMediaUrl('image');
    }

    // Reset record data
    public function resetRecordData()
    {
        $this->reset([
            'id',
            'shop_id',
            'name',
            'description',
            'image',
            'oldImage',
        ]);
        $this->shop_id = config('shop.single_shop') ? getDefaultShop()?->id : null;
    }

    // Handle form submit
    public function submit(StoreCategoryAction $storeAction, UpdateCategoryAction $updateAction)
    {
        Gate::authorize(($this->isUpdate ? 'update' : 'create').$this->modelInstance);

        $this->validate([
            'shop_id' => 'required|exists:shops,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($this->isUpdate) {
            $updateAction->handle(
                category: ProductCategory::findOrFail($this->id),
                data: $this->all(),
            );
        } else {
            $storeAction->handle(
                data: $this->all(),
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
};
