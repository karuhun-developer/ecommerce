<?php

use App\Actions\Cms\Product\Product\StoreProductAction;
use App\Livewire\Forms\Cms\ProductCreateForm;
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

        $this->form->reset();
        $this->form->shop_id = isSingleShop() ? $this->shops->first()?->id : null;

        $this->resetSelectedAttributes();

        $this->dispatch('update-jodit-content', editorId: 'product-create-description', content: '');
    }

    public function updatedFormShopId(): void
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

    public ProductCreateForm $form;

    public function submit(StoreProductAction $storeAction): void
    {
        Gate::authorize('create'.$this->modelInstance);
        $data = $this->form->data();
        $shop = $this->selectedShop() ?? abort(404);
        $product = $storeAction->handle($shop, $data, auth()->user());
        $this->dispatch('toast', type: 'success', message: 'Product created successfully!');
        $this->redirectRoute('cms.product.edit', ['product_id' => $product->id], navigate: true);
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

    private function resetSelectedAttributes(): void
    {
        $this->form->selectedAttributes = [];

        foreach ($this->attributeGroups as $group) {
            $this->form->selectedAttributes[$group->id] = $group->attributes->pluck('id')->all();
        }
    }
};
