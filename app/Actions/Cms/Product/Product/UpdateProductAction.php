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
use App\Traits\WithMediaCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateProductAction
{
    use WithMediaCollection;

    /**
     * Handle the action.
     */
    public function handle(Product $product, Shop $shop, array $data, array $imagesData = []): Product
    {
        Gate::authorize('update'.Product::class);

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $product = Product::query()->accessibleTo($user)->findOrFail($product->getKey());
        $shop = Shop::query()->accessibleTo($user)->findOrFail($shop->getKey());

        $productFlats = $data['productFlats'] ?? [];
        $this->ensureFlatIdsBelongToProduct($product, array_keys($productFlats));
        $this->ensureFlatIdsBelongToProduct($product, array_keys($imagesData));

        return DB::transaction(function () use ($product, $shop, $data, $productFlats, $imagesData): Product {
            $product->update([
                'shop_id' => $shop->id,
                'product_category_id' => $data['product_category_id'],
                'status' => $data['status'] ?? $product->status,
            ]);

            $product->productFlats()->update(['shop_id' => $shop->id]);

            if ($product->type === 'simple') {
                $flat = $product->productFlats()->firstOrFail();
                $flat->update($this->flatPayload($flat, $shop, $productFlats));
                $this->processImages($flat, $imagesData[$flat->id] ?? []);
            } else {
                $this->updateVariableProduct($product, $shop, $data['attributes'] ?? [], $productFlats, $imagesData);
            }

            return $product->fresh();
        });
    }

    /**
     * @param  array<int, array{group_id: int|string, attributes: array<int, int|string>}>  $attributesData
     * @param  array<int|string, array<string, mixed>>  $productFlats
     * @param  array<int|string, array<int, UploadedFile|string|null>>  $imagesData
     */
    private function updateVariableProduct(
        Product $product,
        Shop $shop,
        array $attributesData,
        array $productFlats,
        array $imagesData,
    ): void {
        $groups = collect($attributesData)->filter(fn (array $group): bool => ! empty($group['attributes']));

        if ($groups->isEmpty()) {
            throw ValidationException::withMessages([
                'selectedAttributes' => 'Please select at least one attribute for variable product.',
            ]);
        }

        $resolvedGroups = $groups->map(function (array $groupData) use ($shop): array {
            $attributeGroup = $this->resolveAttributeGroup($shop, $groupData);

            return [
                'group' => $attributeGroup,
                'attributes' => $this->resolveAttributes($shop, $attributeGroup, $groupData['attributes']),
            ];
        });

        $newGroupIds = $resolvedGroups->pluck('group.id')->all();
        ProductAttributeGroup::query()
            ->where('product_id', $product->id)
            ->whereNotIn('attribute_group_id', $newGroupIds)
            ->delete();

        $attributePools = [];

        foreach ($resolvedGroups as $resolvedGroup) {
            $group = ProductAttributeGroup::firstOrCreate([
                'product_id' => $product->id,
                'attribute_group_id' => $resolvedGroup['group']->id,
            ]);

            $attributePools[] = $resolvedGroup['attributes']->map(function (Attribute $attribute) use ($group): array {
                return [
                    'attribute' => $attribute,
                    'group_id' => $group->id,
                ];
            })->toArray();
        }

        $newComboData = [];

        foreach ($this->cartesianProduct($attributePools) as $combo) {
            $attributeIds = collect($combo)
                ->map(fn (array $item): int => $item['attribute']->id)
                ->sort()
                ->values()
                ->all();
            $newComboData[implode('-', $attributeIds)] = $combo;
        }

        $existingComboKeys = [];
        $existingFlats = $product->productFlats()->with('productAttributes')->get();

        foreach ($existingFlats as $flat) {
            $attributeIds = $flat->productAttributes->pluck('attribute_id')->sort()->values()->all();
            $key = implode('-', $attributeIds);

            if (! array_key_exists($key, $newComboData)) {
                $flat->clearMediaCollection('images');
                $flat->delete();

                continue;
            }

            $flat->update($this->flatPayload($flat, $shop, $productFlats));
            $existingComboKeys[] = $key;
            $this->processImages($flat, $imagesData[$flat->id] ?? []);
        }

        foreach ($newComboData as $key => $combo) {
            if (in_array($key, $existingComboKeys, true)) {
                continue;
            }

            $attributeNames = collect($combo)->map(fn (array $item): string => $item['attribute']->name)->join(', ');
            $flat = ProductFlat::create([
                'shop_id' => $shop->id,
                'product_id' => $product->id,
                'name' => $product->name.' - '.$attributeNames,
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

            foreach ($combo as $item) {
                ProductAttribute::create([
                    'product_id' => $product->id,
                    'product_flat_id' => $flat->id,
                    'product_attribute_group_id' => $item['group_id'],
                    'attribute_id' => $item['attribute']->id,
                ]);
            }
        }
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $productFlats
     * @return array<string, mixed>
     */
    private function flatPayload(ProductFlat $flat, Shop $shop, array $productFlats): array
    {
        $flatData = $productFlats[$flat->id] ?? null;

        if (! is_array($flatData)) {
            throw ValidationException::withMessages([
                'productFlats' => 'The product variant data is incomplete.',
            ]);
        }

        return [
            'shop_id' => $shop->id,
            'name' => $flatData['name'],
            'description' => $flatData['description'] ?? null,
            'price' => $flatData['price'],
            'weight' => $flatData['weight'],
            'length' => $flatData['length'],
            'width' => $flatData['width'],
            'height' => $flatData['height'],
            'stock' => $flatData['stock'],
            'is_unlimited_stock' => $flatData['is_unlimited_stock'],
        ];
    }

    /**
     * @param  array<int, int|string>  $flatIds
     */
    private function ensureFlatIdsBelongToProduct(Product $product, array $flatIds): void
    {
        foreach (array_unique($flatIds) as $flatId) {
            $product->productFlats()->findOrFail($flatId);
        }
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
     * @param  array<int, UploadedFile|string|null>  $slots
     */
    private function processImages(ProductFlat $flat, array $slots): void
    {
        foreach ($slots as $index => $file) {
            if (! in_array($index, [0, 1, 2, 3], true)) {
                throw ValidationException::withMessages([
                    'images' => 'The selected image slot is invalid.',
                ]);
            }

            if ($file instanceof UploadedFile) {
                $this->saveMedia($flat, $file, "image_slot_{$index}");

                continue;
            }

            if ($file === 'delete') {
                $this->deleteMedia($flat, "image_slot_{$index}");

                continue;
            }

            throw ValidationException::withMessages([
                'images' => 'The selected image is invalid.',
            ]);
        }
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
            foreach ($remaining as $remainingItems) {
                $result[] = array_merge([$item], $remainingItems);
            }
        }

        return $result;
    }
}
