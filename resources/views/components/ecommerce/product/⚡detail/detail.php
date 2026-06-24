<?php

use App\Models\Product\Product;
use Livewire\Component;

new class extends Component
{
    public Product $product;

    public $variants = [];

    public function mount()
    {
        $this->product->load('productFlats.media', 'shop.location', 'productAttributeGroups.productAttributes.attribute');

        // Flatten all product attributes into a single array of variants, grouped by product flat ID
        $this->variants = $this->product->productAttributeGroups
            ->flatMap(fn ($group) => $group->productAttributes)
            ->groupBy('product_flat_id')
            ->map(fn ($attributes, $productFlatId) => [
                'product_flat_id' => $productFlatId,
                'label' => $attributes->map(fn ($attr) => $attr->attribute->name)->join(' - '),
            ])
            ->values();
    }
};