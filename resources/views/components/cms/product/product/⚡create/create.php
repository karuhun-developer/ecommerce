<?php

use App\Actions\Cms\Product\StoreProductAction;
use App\Models\Attribute\AttributeGroup;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Shop\Shop;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // Model instance
    public $modelInstance = Product::class;

    #[On('reset-form')]
    public function resetForm()
    {
        Gate::authorize('create'.$this->modelInstance);

        $this->reset([
            'shop_id',
            'product_category_id',
            'name',
            'description',
        ]);

        $this->shop_id = config('shop.single_shop') ? getDefaultShop()?->id : null;
        $this->price = 0;
        $this->weight = 0;
        $this->length = 0;
        $this->width = 0;
        $this->height = 0;
        $this->is_unlimited_stock = false;
        $this->type = 'simple';

        $groups = AttributeGroup::with('attributes')->get();
        foreach ($groups as $group) {
            $this->selectedAttributes[$group->id] = $group->attributes->pluck('id')->toArray();
        }

        $this->dispatch('update-jodit-content', '');
    }

    #[Computed]
    public function attributeGroups()
    {
        return AttributeGroup::with('attributes')->get();
    }

    #[Computed]
    public function shops()
    {
        return Shop::all();
    }

    #[Computed]
    public function categories()
    {
        return ProductCategory::all();
    }

    // Record data
    public $shop_id;

    public $product_category_id;

    public $name;

    public $description;

    public $price;

    public $weight;

    public $length;

    public $width;

    public $height;

    public $is_unlimited_stock;

    public $type; // simple or variable

    public $selectedAttributes;

    public function submit(StoreProductAction $storeAction)
    {
        Gate::authorize('create'.$this->modelInstance);

        $this->price = currencyToNumber($this->price);

        $this->validate([
            'shop_id' => 'required|exists:shops,id',
            'product_category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:simple,variable',
            'price' => 'required|numeric|min:0',
            'weight' => 'required|numeric|min:0',
            'length' => 'required|numeric|min:0',
            'width' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'is_unlimited_stock' => 'required|boolean',
        ]);

        $attributesData = [];
        if ($this->type === 'variable') {
            foreach ($this->selectedAttributes as $groupId => $attrIds) {
                if (! empty($attrIds)) {
                    $attributesData[] = [
                        'group_id' => $groupId,
                        'attributes' => $attrIds,
                    ];
                }
            }
            if (empty($attributesData)) {
                $this->addError('selectedAttributes', 'Please select at least one attribute for variable product.');

                return;
            }
        }

        $storeAction->handle(
            data: [
                ...$this->all(),
                'attributes' => $attributesData,
            ],
        );

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: 'Product created successfully!',
        );

        // Reset data table
        $this->dispatch('reset-parent-page');

        // Close modal
        Flux::modal('defaultModal')->close();
    }
};
