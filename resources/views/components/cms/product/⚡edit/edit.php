<?php

use App\Actions\Cms\Product\UpdateProductAction;
use App\Models\Attribute\AttributeGroup;
use App\Models\Product\Product;
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
                'name',
                'description',
                'weight',
                'length',
                'width',
                'height',
                'is_unlimited_stock',
            ])
        );
        $this->price = numberToCurrency($this->product->price);
        
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
                    ->map(fn($id) => (string) $id)
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

    // Record data
    public $shop_id;
    
    public $name;

    public $description;

    public $price;

    public $weight;

    public $length;

    public $width;

    public $height;

    public $is_unlimited_stock;

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

        $this->price = currencyToNumber($this->price);
        $this->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'weight' => 'required|numeric|min:0',
            'length' => 'required|numeric|min:0',
            'width' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'is_unlimited_stock' => 'required|boolean',
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
            for ($i = 0; $i < 8; $i++) {
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
    }
};
