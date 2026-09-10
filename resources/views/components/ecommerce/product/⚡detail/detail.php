<?php

use App\Models\Product\Product;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public Product $product;

    public array $variants = [];

    public function mount(): void
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
            ->values()
            ->all();
    }
};
