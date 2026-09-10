<?php

use App\Actions\Cms\Product\Product\StoreProductAction;
use App\Models\Attribute\AttributeGroup;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Shop\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $modelInstance = Product::class;

    #[On('reset-form')]
    public function resetForm(): void
    {
        Gate::authorize('create'.$this->modelInstance);

        $this->reset([
            'shop_id',
            'product_category_id',
            'name',
            'description',
            'selectedAttributes',
        ]);

        $this->shop_id = isSingleShop() ? $this->shops->first()?->id : null;
        $this->price = 0;
        $this->weight = 0;
        $this->length = 0;
        $this->width = 0;
        $this->height = 0;
        $this->is_unlimited_stock = false;
        $this->type = 'simple';

        $this->resetSelectedAttributes();

        $this->dispatch('update-jodit-content', '');
    }

    public function updatedShopId(): void
    {
        $this->resetSelectedAttributes();
    }

    #[Computed]
    public function attributeGroups(): Collection
    {
        $shop = $this->selectedShop();

        if (! $shop) {
            return new Collection;
        }

        return AttributeGroup::query()
            ->where(function ($query) use ($shop): void {
                $query->whereNull('shop_id')->orWhere('shop_id', $shop->id);
            })
            ->with(['attributes' => function ($query) use ($shop): void {
                $query->whereNull('shop_id')->orWhere('shop_id', $shop->id);
            }])
            ->get();
    }

    #[Computed]
    public function shops(): Collection
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return Shop::query()->accessibleTo($user)->get();
    }

    #[Computed]
    public function categories(): Collection
    {
        return ProductCategory::all();
    }

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

    public array $selectedAttributes = [];

    public function submit(StoreProductAction $storeAction): void
    {
        Gate::authorize('create'.$this->modelInstance);

        $this->price = currencyToNumber($this->price);

        $this->validate([
            'shop_id' => 'required|integer',
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
            'selectedAttributes' => 'array',
            'selectedAttributes.*' => 'array',
            'selectedAttributes.*.*' => 'integer',
        ]);

        $shop = $this->selectedShop() ?? abort(404);

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

        $product = $storeAction->handle(
            shop: $shop,
            data: [
                'product_category_id' => $this->product_category_id,
                'type' => $this->type,
                'name' => $this->name,
                'description' => $this->description,
                'price' => $this->price,
                'weight' => $this->weight,
                'length' => $this->length,
                'width' => $this->width,
                'height' => $this->height,
                'is_unlimited_stock' => $this->is_unlimited_stock,
                'attributes' => $attributesData,
            ],
        );

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: 'Product created successfully!',
        );

        // Redirect to edit page
        $this->redirectRoute('cms.product.edit', ['product_id' => $product->id], navigate: true);
    }

    private function selectedShop(): ?Shop
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        if (! $this->shop_id) {
            return null;
        }

        return Shop::query()
            ->accessibleTo($user)
            ->find((int) $this->shop_id);
    }

    private function resetSelectedAttributes(): void
    {
        $this->selectedAttributes = [];

        foreach ($this->attributeGroups as $group) {
            $this->selectedAttributes[$group->id] = $group->attributes->pluck('id')->all();
        }
    }
};
