<?php

use App\Actions\Cms\Product\Product\UpdateProductAction;
use App\Models\Attribute\AttributeGroup;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Shop\Shop;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    // Model instance
    public $modelInstance = Product::class;

    public Product $product;

    public function mount()
    {
        Gate::authorize('show'.$this->modelInstance);

        $this->initializeState();
    }

    private function initializeState()
    {
        $this->fill(
            $this->product->only([
                'shop_id',
                'product_category_id',
            ])
        );

        // Initialize flats array with product flat data
        foreach ($this->product->productFlats as $flat) {
            $this->productFlats[$flat->id] = [
                'id' => $flat->id,
                'name' => $flat->name,
                'price' => numberToCurrency($flat->price),
                'weight' => $flat->weight,
                'length' => $flat->length,
                'width' => $flat->width,
                'height' => $flat->height,
                'is_unlimited_stock' => $flat->is_unlimited_stock,
                'stock' => $flat->stock,
            ];
            $this->dispatch('update-jodit-content', [
                'description-'.$flat->id,
                $flat->description,
            ]);
        }

        // Initialize selected attributes array with empty arrays for each group
        foreach ($this->attributeGroups as $group) {
            $this->selectedAttributes[$group->id] = [];
        }

        // If product is variable, pre-fill selected attributes for each group
        if ($this->product->type === 'variable') {
            $existingGroups = $this->product->productAttributeGroups()->with('productAttributes')->get();
            foreach ($existingGroups as $group) {
                $attrIds = $group->productAttributes()
                    ->pluck('attribute_id')
                    ->map(fn ($id) => (string) $id)
                    ->unique()
                    ->toArray();

                $this->selectedAttributes[$group->attribute_group_id] = array_values($attrIds);
            }
        }
    }

    #[Computed]
    public function attributeGroups()
    {
        return AttributeGroup::with('attributes')->get();
    }

    #[Computed]
    public function flats()
    {
        return $this->product->productFlats;
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

    public $productFlats = [];

    public $selectedAttributes;

    public $images = [];

    // UI state to know which images to delete
    public $deletedImages = [];

    // Remove existing image by marking it for deletion
    public function removeExistingImage($flatId, $slotIndex)
    {
        $this->deletedImages[$flatId][$slotIndex] = true;
    }

    // Remove newly uploaded image from the temporary state
    public function removeImage($flatId, $slotIndex)
    {
        unset($this->images[$flatId][$slotIndex]);
    }

    public function submit(UpdateProductAction $updateAction)
    {
        Gate::authorize('update'.$this->modelInstance);

        foreach ($this->productFlats as $index => $flat) {
            $this->productFlats[$index]['price'] = currencyToNumber($flat['price']);
        }

        $this->validate([
            'shop_id' => 'required|exists:shops,id',
            'product_category_id' => 'required|exists:product_categories,id',
            'productFlats.*.id' => 'required|exists:product_flats,id',
            'productFlats.*.name' => 'required|string|max:255',
            'productFlats.*.description' => 'nullable|string',
            'productFlats.*.price' => 'required|numeric|min:0',
            'productFlats.*.weight' => 'required|numeric|min:0',
            'productFlats.*.length' => 'required|numeric|min:0',
            'productFlats.*.width' => 'required|numeric|min:0',
            'productFlats.*.height' => 'required|numeric|min:0',
            'productFlats.*.stock' => 'required|integer|min:0',
            'productFlats.*.is_unlimited_stock' => 'required|boolean',
            'images.*.*' => 'nullable|image|max:2048', // Validate each uploaded image
            'deletedImages.*.*' => 'nullable|boolean', // Validate deleted images flags
        ]);

        // Attributes validation for variable products
        $attributesData = [];
        if ($this->product->type === 'variable') {
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

        $imagesData = [];
        foreach ($this->product->productFlats as $flat) {
            $slots = [];
            for ($i = 0; $i < 4; $i++) {
                if (isset($this->images[$flat->id][$i])) {
                    $slots[$i] = $this->images[$flat->id][$i];
                } elseif (isset($this->deletedImages[$flat->id][$i]) && $this->deletedImages[$flat->id][$i] === true) {
                    $slots[$i] = 'delete';
                }
            }
            if (! empty($slots)) {
                $imagesData[$flat->id] = $slots;
            }
        }

        // Call the action to update the product with all data
        $updateAction->handle(
            product: $this->product,
            data: [
                ...$this->all(),
                'attributes' => $attributesData,
            ],
            imagesData: $imagesData,
        );

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: 'Product updated successfully.',
        );

        // Reset images
        $this->images = [];

        // Reset page
        $this->redirectRoute('cms.product.edit', ['product_id' => $this->product->id], navigate: true);
    }
};
