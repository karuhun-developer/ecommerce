<?php

namespace App\Livewire\Forms\Cms;

use App\Data\Cms\ProductUpdateData;
use App\Models\Product\Product;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class ProductEditForm extends Form
{
    public ?int $shop_id = null;

    public ?int $product_category_id = null;

    public array $productFlats = [];

    public array $selectedAttributes = [];

    public array $images = [];

    public array $deletedImages = [];

    public function data(Product $product): ProductUpdateData
    {
        foreach ($this->productFlats as $id => $flat) {
            $this->productFlats[$id]['price'] = currencyToNumber($flat['price'] ?? 0);
        }
        $data = $this->validate([
            'shop_id' => ['required', 'integer'],
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'productFlats' => ['required', 'array'],
            'productFlats.*.name' => ['required', 'string', 'max:255'],
            'productFlats.*.description' => ['nullable', 'string', 'max:50000'],
            'productFlats.*.price' => ['required', 'numeric', 'min:0'],
            'productFlats.*.weight' => ['required', 'numeric', 'min:0'],
            'productFlats.*.length' => ['required', 'numeric', 'min:0'],
            'productFlats.*.width' => ['required', 'numeric', 'min:0'],
            'productFlats.*.height' => ['required', 'numeric', 'min:0'],
            'productFlats.*.stock' => ['required', 'integer', 'min:0'],
            'productFlats.*.is_unlimited_stock' => ['required', 'boolean'],
            'images' => ['array'],
            'images.*' => ['array'],
            'images.*.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'deletedImages' => ['array'],
            'deletedImages.*' => ['array'],
            'deletedImages.*.*' => ['nullable', 'boolean'],
            'selectedAttributes' => ['array'],
            'selectedAttributes.*' => ['array'],
            'selectedAttributes.*.*' => ['integer'],
        ]);
        $attributes = [];
        if ($product->type === 'variable') {
            foreach ($data['selectedAttributes'] as $groupId => $ids) {
                if ($ids !== []) {
                    $attributes[] = ['group_id' => (int) $groupId, 'attributes' => $ids];
                }
            }
            if ($attributes === []) {
                throw ValidationException::withMessages(['form.selectedAttributes' => 'Please select at least one attribute for variable product.']);
            }
        }
        $images = [];
        foreach ($product->productFlats as $flat) {
            for ($slot = 0; $slot < 4; $slot++) {
                if (isset($this->images[$flat->id][$slot])) {
                    $images[$flat->id][$slot] = $this->images[$flat->id][$slot];
                } elseif (($this->deletedImages[$flat->id][$slot] ?? false) === true) {
                    $images[$flat->id][$slot] = 'delete';
                }
            }
        }

        return ProductUpdateData::fromArray(['product_category_id' => $this->product_category_id, 'productFlats' => $data['productFlats'], 'attributes' => $attributes], $images);
    }
}
