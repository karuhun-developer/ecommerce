<?php

namespace App\Data\Cms;

use Illuminate\Http\UploadedFile;

final readonly class ProductUpdateData
{
    /**
     * @param  array<int, ProductFlatData>  $productFlats
     * @param  list<ProductAttributeSelectionData>  $attributes
     * @param  list<ProductImageSlotData>  $images
     */
    public function __construct(public int $product_category_id, public array $productFlats, public array $attributes = [], public array $images = [], public ?bool $status = null) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<int, UploadedFile|string>>  $imagesData
     */
    public static function fromArray(array $data, array $imagesData = []): self
    {
        $flats = [];
        foreach ($data['productFlats'] ?? [] as $id => $flat) {
            $flats[(int) $id] = ProductFlatData::fromArray($flat);
        }
        $images = [];
        foreach ($imagesData as $id => $slots) {
            foreach ($slots as $slot => $image) {
                $images[] = new ProductImageSlotData((int) $id, (int) $slot, $image instanceof UploadedFile ? $image : null, $image === 'delete');
            }
        }

        return new self((int) $data['product_category_id'], $flats, array_map(ProductAttributeSelectionData::fromArray(...), $data['attributes'] ?? []), $images, isset($data['status']) ? (bool) $data['status'] : null);
    }
}
