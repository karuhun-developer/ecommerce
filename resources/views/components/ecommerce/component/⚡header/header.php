<?php

use App\Models\Product\ProductCategory;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed(persist: true)]
    public function featuredCategories()
    {
        return ProductCategory::query()
            ->where('is_featured', true)
            ->orderBy('name')
            ->limit(10)
            ->get();
    }
};
