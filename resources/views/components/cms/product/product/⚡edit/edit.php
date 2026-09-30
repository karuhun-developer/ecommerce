<?php

use App\Actions\Cms\Product\Product\UpdateProductAction;
use App\Livewire\Forms\Cms\ProductEditForm;
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
        $this->form->fill(
            $this->product->only([
                'shop_id',
                'product_category_id',
            ])
        );

        foreach ($this->product->productFlats as $flat) {
            $this->form->productFlats[$flat->id] = [
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
            $this->dispatch('update-jodit-content', editorId: 'description-'.$flat->id, content: $flat->description);
        }

        foreach ($this->attributeGroups as $group) {
            $this->form->selectedAttributes[$group->id] = [];
        }

        if ($this->product->type === 'variable') {
            $existingGroups = $this->product->productAttributeGroups()->with('productAttributes')->get();
            foreach ($existingGroups as $group) {
                $attrIds = $group->productAttributes()
                    ->pluck('attribute_id')
                    ->map(fn ($id) => (string) $id)
                    ->unique()
                    ->toArray();

                $this->form->selectedAttributes[$group->attribute_group_id] = array_values($attrIds);
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

    public ProductEditForm $form;

    public function removeExistingImage(int|string $flatId, int $slotIndex): void
    {
        Gate::authorize('update'.$this->modelInstance);
        $flat = $this->resolveFlat($flatId);
        abort_unless(in_array($slotIndex, [0, 1, 2, 3], true), 404);

        $this->form->deletedImages[$flat->id][$slotIndex] = true;
    }

    public function removeImage(int|string $flatId, int $slotIndex): void
    {
        Gate::authorize('update'.$this->modelInstance);
        $flat = $this->resolveFlat($flatId);
        abort_unless(in_array($slotIndex, [0, 1, 2, 3], true), 404);

        unset($this->form->images[$flat->id][$slotIndex]);
    }

    public function submit(UpdateProductAction $updateAction): void
    {
        Gate::authorize('update'.$this->modelInstance);

        $this->ensureFlatPayloadIsScoped($this->form->productFlats, requireAllFlats: true);
        $this->ensureFlatPayloadIsScoped($this->form->images);
        $this->ensureFlatPayloadIsScoped($this->form->deletedImages);

        $data = $this->form->data($this->product);
        $shop = $this->selectedShop() ?? abort(404);
        $updateAction->handle($this->product, $shop, $data, auth()->user());
        $this->dispatch('toast', type: 'success', message: 'Product updated successfully.');
        $this->form->images = [];
        $this->redirectRoute('cms.product.edit', ['product_id' => $this->product->id], navigate: true);
    }

    private function selectedShop(): ?Shop
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        if (! $this->form->shop_id) {
            return null;
        }

        return Shop::query()
            ->accessibleTo($user)
            ->find((int) $this->form->shop_id);
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
                'form.productFlats' => 'The product variant data is incomplete.',
            ]);
        }
    }
};
