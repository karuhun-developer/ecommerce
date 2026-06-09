<?php

namespace App\Actions\Cms\Product;

use App\Models\Attribute\Attribute;
use App\Models\Product\Product;
use App\Models\Product\ProductAttribute;
use App\Models\Product\ProductAttributeGroup;
use App\Models\Product\ProductFlat;
use Illuminate\Support\Facades\DB;

class StoreProductAction
{
    /**
     * Handle the action.
     */
    public function handle(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                'shop_id' => $data['shop_id'],
                'type' => $data['type'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'] ?? 0,
                'weight' => $data['weight'] ?? 0,
                'length' => $data['length'] ?? 0,
                'width' => $data['width'] ?? 0,
                'height' => $data['height'] ?? 0,
                'is_unlimited_stock' => $data['is_unlimited_stock'] ?? false,
                'status' => $data['status'] ?? true,
                'stock' => 0,
            ]);

            if ($product->type === 'simple') {
                ProductFlat::create([
                    'shop_id' => $product->shop_id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'weight' => $product->weight,
                    'length' => $product->length,
                    'width' => $product->width,
                    'height' => $product->height,
                    'is_unlimited_stock' => $product->is_unlimited_stock,
                    'status' => $product->status,
                    'stock' => 0,
                ]);
            } else {
                $groups = collect($data['attributes'] ?? [])->filter(fn ($g) => ! empty($g['attributes']));

                $createdGroups = [];
                $attributePools = [];

                foreach ($groups as $groupData) {
                    $group = ProductAttributeGroup::create([
                        'product_id' => $product->id,
                        'attribute_group_id' => $groupData['group_id'],
                    ]);

                    $createdGroups[$groupData['group_id']] = $group->id;

                    $attrs = Attribute::whereIn('id', $groupData['attributes'])->get();
                    $attributePools[] = $attrs->map(function ($attr) use ($group) {
                        return [
                            'attribute' => $attr,
                            'group_id' => $group->id,
                        ];
                    })->toArray();
                }

                $combinations = $this->cartesianProduct($attributePools);

                foreach ($combinations as $combo) {
                    // Combine names, e.g. "T-Shirt - Red, M"
                    // $combo is array of attribute elements like [['attribute' => attr1, 'group_id' => x], ...]
                    if (! is_array($combo)) {
                        $combo = [$combo];
                    }

                    $attrNames = collect($combo)->map(fn ($c) => $c['attribute']->name)->join(', ');
                    $flatName = $product->name.' - '.$attrNames;

                    $flat = ProductFlat::create([
                        'shop_id' => $product->shop_id,
                        'product_id' => $product->id,
                        'name' => $flatName,
                        'description' => $product->description,
                        'price' => $product->price,
                        'weight' => $product->weight,
                        'length' => $product->length,
                        'width' => $product->width,
                        'height' => $product->height,
                        'is_unlimited_stock' => $product->is_unlimited_stock,
                        'status' => $product->status,
                        'stock' => 0,
                    ]);

                    foreach ($combo as $c) {
                        ProductAttribute::create([
                            'product_id' => $product->id,
                            'product_flat_id' => $flat->id,
                            'product_attribute_group_id' => $c['group_id'],
                            'attribute_id' => $c['attribute']->id,
                        ]);
                    }
                }
            }

            return $product;
        });
    }

    private function cartesianProduct($arrays)
    {
        if (empty($arrays)) {
            return [[]];
        }

        $result = [];
        $first = array_shift($arrays);
        $remaining = $this->cartesianProduct($arrays);

        foreach ($first as $item) {
            foreach ($remaining as $rem) {
                $result[] = array_merge([$item], $rem);
            }
        }

        return $result;
    }
}
