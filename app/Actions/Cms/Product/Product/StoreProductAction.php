<?php

namespace App\Actions\Cms\Product\Product;

use App\Models\Attribute\Attribute;
use App\Models\Attribute\AttributeGroup;
use App\Models\Product\Product;
use App\Models\Product\ProductAttribute;
use App\Models\Product\ProductAttributeGroup;
use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class StoreProductAction
{
    /**
     * Handle the action.
     */
    public function handle(Shop $shop, array $data): Product
    {
        Gate::authorize('create'.Product::class);

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $shop = Shop::query()->accessibleTo($user)->findOrFail($shop->getKey());

        return DB::transaction(function () use ($shop, $data) {
            $product = Product::create([
                'product_category_id' => $data['product_category_id'],
                'shop_id' => $shop->id,
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
                $groups = collect($data['attributes'] ?? [])->filter(fn (array $group): bool => ! empty($group['attributes']));

                $attributePools = [];

                foreach ($groups as $groupData) {
                    $attributeGroup = $this->resolveAttributeGroup($shop, $groupData);
                    $group = ProductAttributeGroup::create([
                        'product_id' => $product->id,
                        'attribute_group_id' => $attributeGroup->id,
                    ]);

                    $attributes = $this->resolveAttributes($shop, $attributeGroup, $groupData['attributes']);
                    $attributePools[] = $attributes->map(function (Attribute $attribute) use ($group): array {
                        return [
                            'attribute' => $attribute,
                            'group_id' => $group->id,
                        ];
                    })->toArray();
                }

                $combinations = $this->cartesianProduct($attributePools);

                foreach ($combinations as $combo) {
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

    /**
     * @param  array{group_id: int|string, attributes: array<int, int|string>}  $groupData
     */
    private function resolveAttributeGroup(Shop $shop, array $groupData): AttributeGroup
    {
        return AttributeGroup::query()
            ->whereKey((int) $groupData['group_id'])
            ->where(function ($query) use ($shop): void {
                $query->whereNull('shop_id')->orWhere('shop_id', $shop->id);
            })
            ->firstOrFail();
    }

    /**
     * @param  array<int, int|string>  $attributeIds
     * @return Collection<int, Attribute>
     */
    private function resolveAttributes(Shop $shop, AttributeGroup $group, array $attributeIds): Collection
    {
        $attributeIds = collect($attributeIds)
            ->map(fn (mixed $attributeId): int => (int) $attributeId)
            ->unique()
            ->values();

        $attributes = $group->attributes()
            ->whereIn('id', $attributeIds->all())
            ->where(function ($query) use ($shop): void {
                $query->whereNull('shop_id')->orWhere('shop_id', $shop->id);
            })
            ->get();

        if ($attributes->count() !== $attributeIds->count()) {
            throw ValidationException::withMessages([
                'selectedAttributes' => 'The selected attributes are invalid.',
            ]);
        }

        return $attributes;
    }

    /**
     * @param  array<int, array<int, mixed>>  $arrays
     * @return array<int, array<int, mixed>>
     */
    private function cartesianProduct(array $arrays): array
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
