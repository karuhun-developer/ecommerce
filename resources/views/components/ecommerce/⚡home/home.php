<?php

use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed(persist: true)]
    public function categories()
    {
        return ProductCategory::query()
            ->with('media')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with('mainProductFlat.media', 'shop')
            ->limit(12)
            ->get();
    }
};
