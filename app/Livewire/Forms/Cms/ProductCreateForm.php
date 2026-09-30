<?php

namespace App\Livewire\Forms\Cms;

use App\Data\Cms\ProductAttributeSelectionData;
use App\Data\Cms\ProductCreateData;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class ProductCreateForm extends Form
{
    public ?int $shop_id = null;

    public ?int $product_category_id = null;

    public string $name = '';

    public ?string $description = null;

    public int|float|string $price = 0;

    public int|float|string $weight = 0;

    public int|float|string $length = 0;

    public int|float|string $width = 0;

    public int|float|string $height = 0;

    public bool $is_unlimited_stock = false;

    public string $type = 'simple';

    public array $selectedAttributes = [];

    public function data(): ProductCreateData
    {
        $this->price = currencyToNumber($this->price);
        $data = $this->validate([
            'shop_id' => ['required', 'integer'],
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:50000'],
            'type' => ['required', 'in:simple,variable'],
            'price' => ['required', 'numeric', 'min:0'],
            'weight' => ['required', 'numeric', 'min:0'],
            'length' => ['required', 'numeric', 'min:0'],
            'width' => ['required', 'numeric', 'min:0'],
            'height' => ['required', 'numeric', 'min:0'],
            'is_unlimited_stock' => ['required', 'boolean'],
            'selectedAttributes' => ['array'],
            'selectedAttributes.*' => ['array'],
            'selectedAttributes.*.*' => ['integer'],
        ]);
        $attributes = [];
        if ($this->type === 'variable') {
            foreach ($data['selectedAttributes'] as $groupId => $ids) {
                if ($ids !== []) {
                    $attributes[] = new ProductAttributeSelectionData((int) $groupId, array_map(intval(...), $ids));
                }
            }
            if ($attributes === []) {
                throw ValidationException::withMessages(['form.selectedAttributes' => 'Please select at least one attribute for variable product.']);
            }
        }

        return new ProductCreateData(
            product_category_id: $this->product_category_id,
            type: $this->type,
            name: $this->name,
            description: $this->description,
            price: (float) $this->price,
            weight: (float) $this->weight,
            length: (float) $this->length,
            width: (float) $this->width,
            height: (float) $this->height,
            is_unlimited_stock: $this->is_unlimited_stock,
            attributes: $attributes,
        );
    }
}
