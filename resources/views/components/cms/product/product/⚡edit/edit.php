<?php

use App\Actions\Cms\Product\Product\UpdateProductAction;
use App\Models\Attribute\AttributeGroup;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $modelInstance = Product::class;

    #[Locked]
    public Product $product;

    public function mount(): void
    {
        Gate::authorize('show'.$this->modelInstance);

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $this->product = Product::query()
            ->accessibleTo($user)
            ->with(['productFlats.media', 'productAttributeGroups.productAttributes'])
            ->findOrFail($this->product->getKey());

        $this->initializeState();
    }

    private function initializeState(): void
    {
        $this->fill(
            $this->product->only([
                'shop_id',
                'product_category_id',
            ])
        );

        foreach ($this->product->productFlats as $flat) {
            $this->productFlats[$flat->id] = [
                'name' => $flat->name,
                'description' => $flat->description,
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

        foreach ($this->attributeGroups as $group) {
            $this->selectedAttributes[$group->id] = [];
        }

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
    public function flats(): Collection
    {
        return $this->product->productFlats()->with('media')->get();
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

    public array $productFlats = [];

    public array $selectedAttributes = [];

    public array $images = [];

    public array $deletedImages = [];

    public function removeExistingImage(int|string $flatId, int $slotIndex): void
    {
        $flat = $this->resolveFlat($flatId);
        abort_unless(in_array($slotIndex, [0, 1, 2, 3], true), 404);

        $this->deletedImages[$flat->id][$slotIndex] = true;
    }

    public function removeImage(int|string $flatId, int $slotIndex): void
    {
        $flat = $this->resolveFlat($flatId);
        abort_unless(in_array($slotIndex, [0, 1, 2, 3], true), 404);

        unset($this->images[$flat->id][$slotIndex]);
    }

    public function submit(UpdateProductAction $updateAction): void
    {
        Gate::authorize('update'.$this->modelInstance);

        $this->ensureFlatPayloadIsScoped($this->productFlats, requireAllFlats: true);
        $this->ensureFlatPayloadIsScoped($this->images);
        $this->ensureFlatPayloadIsScoped($this->deletedImages);

        foreach ($this->productFlats as $index => $flat) {
            $this->productFlats[$index]['price'] = currencyToNumber($flat['price']);
        }

        $this->validate([
            'shop_id' => 'required|integer',
            'product_category_id' => 'required|exists:product_categories,id',
            'productFlats' => 'required|array',
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
            'selectedAttributes' => 'array',
            'selectedAttributes.*' => 'array',
            'selectedAttributes.*.*' => 'integer',
        ]);

        $shop = $this->selectedShop() ?? abort(404);

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

        $updateAction->handle(
            product: $this->product,
            shop: $shop,
            data: [
                'product_category_id' => $this->product_category_id,
                'productFlats' => $this->productFlats,
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

    private function resolveFlat(int|string $flatId): ProductFlat
    {
        return $this->product->productFlats()->findOrFail($flatId);
    }

    private function ensureFlatPayloadIsScoped(array $payload, bool $requireAllFlats = false): void
    {
        foreach (array_keys($payload) as $flatId) {
            $this->resolveFlat($flatId);
        }

        if (! $requireAllFlats) {
            return;
        }

        $submittedFlatIds = collect(array_keys($payload))->map(fn (int|string $flatId): int => (int) $flatId);
        $ownedFlatIds = $this->product->productFlats()->pluck('id');

        if ($submittedFlatIds->sort()->values()->all() !== $ownedFlatIds->sort()->values()->all()) {
            throw ValidationException::withMessages([
                'productFlats' => 'The product variant data is incomplete.',
            ]);
        }
    }
};
