<?php

namespace App\Actions\Cms\Product;

use App\Models\Attribute\Attribute;
use App\Models\Product\Product;
use App\Models\Product\ProductAttribute;
use App\Models\Product\ProductAttributeGroup;
use App\Models\Product\ProductFlat;
use App\Traits\WithMediaCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateProductAction
{
    use WithMediaCollection;

    /**
     * Handle the action.
     */
    public function handle(Product $product, array $data, array $imagesData = []): Product
    {
        return DB::transaction(function () use ($product, $data, $imagesData) {
            $product->update([
                'shop_id' => $data['shop_id'],
                'product_category_id' => $data['product_category_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? $product->description,
                'price' => $data['price'] ?? $product->price,
                'weight' => $data['weight'] ?? $product->weight,
                'length' => $data['length'] ?? $product->length,
                'width' => $data['width'] ?? $product->width,
                'height' => $data['height'] ?? $product->height,
                'is_unlimited_stock' => $data['is_unlimited_stock'] ?? $product->is_unlimited_stock,
                'status' => $data['status'] ?? $product->status,
            ]);

            if ($product->type === 'simple') {
                $flat = $product->productFlats()->first();
                if ($flat) {
                    $flat->update([
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => $product->price,
                        'weight' => $product->weight,
                        'length' => $product->length,
                        'width' => $product->width,
                        'height' => $product->height,
                        'is_unlimited_stock' => $product->is_unlimited_stock,
                        'status' => $product->status,
                    ]);

                    $this->processImages($flat, $imagesData[$flat->id] ?? []);
                }
            } else {
                $groups = collect($data['attributes'] ?? [])->filter(fn ($g) => ! empty($g['attributes']));

                // Sync Attribute Groups
                $newGroupIds = $groups->pluck('group_id')->toArray();
                ProductAttributeGroup::where('product_id', $product->id)
                    ->whereNotIn('attribute_group_id', $newGroupIds)
                    ->delete();

                $groupMap = [];
                foreach ($groups as $groupData) {
                    $group = ProductAttributeGroup::firstOrCreate([
                        'product_id' => $product->id,
                        'attribute_group_id' => $groupData['group_id'],
                    ]);
                    $groupMap[$groupData['group_id']] = $group->id;
                }

                $attributePools = [];
                foreach ($groups as $groupData) {
                    $attrs = Attribute::whereIn('id', $groupData['attributes'])->get();
                    $attributePools[] = $attrs->map(function ($attr) use ($groupMap, $groupData) {
                        return [
                            'attribute' => $attr,
                            'group_id' => $groupMap[$groupData['group_id']],
                        ];
                    })->toArray();
                }

                $newCombinations = $this->cartesianProduct($attributePools);

                // Generate a key for each new combination based on sorted attribute IDs
                $newComboKeys = [];
                $newComboData = [];
                foreach ($newCombinations as $combo) {
                    if (! is_array($combo)) {
                        $combo = [$combo];
                    }
                    $attrIds = collect($combo)->map(fn ($c) => $c['attribute']->id)->sort()->values()->toArray();
                    $key = implode('-', $attrIds);
                    $newComboKeys[] = $key;
                    $newComboData[$key] = $combo;
                }

                $existingFlats = $product->productFlats()->with('productAttributes')->get();
                $existingComboKeys = [];

                foreach ($existingFlats as $flat) {
                    $attrIds = $flat->productAttributes->pluck('attribute_id')->sort()->values()->toArray();
                    $key = implode('-', $attrIds);

                    if (! in_array($key, $newComboKeys)) {
                        // This combination no longer exists, delete it
                        $flat->clearMediaCollection('images');
                        $flat->delete();
                    } else {
                        // This combination still exists, update parent fields
                        $attrNames = collect($newComboData[$key])->map(fn ($c) => $c['attribute']->name)->join(', ');
                        $flatName = $product->name.' - '.$attrNames;

                        $flat->update([
                            'name' => $flatName,
                            'description' => $product->description,
                            'price' => $product->price,
                            'weight' => $product->weight,
                            'length' => $product->length,
                            'width' => $product->width,
                            'height' => $product->height,
                            'is_unlimited_stock' => $product->is_unlimited_stock,
                            'status' => $product->status,
                        ]);
                        $existingComboKeys[] = $key;

                        $this->processImages($flat, $imagesData[$flat->id] ?? []);
                    }
                }

                // Add combinations that don't exist yet
                foreach ($newComboData as $key => $combo) {
                    if (! in_array($key, $existingComboKeys)) {
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
            }

            return $product;
        });
    }

    private function processImages(ProductFlat $flat, array $slots)
    {
        // Delete or replace existing images based on the input
        foreach ($slots as $index => $file) {
            if ($file instanceof UploadedFile) {
                $this->saveMedia($flat, $file, "image_slot_{$index}");
            } elseif ($file === 'delete') {
                $this->deleteMedia($flat, "image_slot_{$index}");
            }
        }
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
