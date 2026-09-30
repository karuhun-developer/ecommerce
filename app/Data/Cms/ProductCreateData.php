<?php

namespace App\Data\Cms;

final readonly class ProductCreateData
{
    /** @param list<ProductAttributeSelectionData> $attributes */
    public function __construct(
        public int $product_category_id,
        public string $type,
        public string $name,
        public ?string $description = null,
        public float $price = 0,
        public float $weight = 0,
        public float $length = 0,
        public float $width = 0,
        public float $height = 0,
        public bool $is_unlimited_stock = false,
        public bool $status = true,
        public array $attributes = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['product_category_id'], $data['type'], $data['name'], $data['description'] ?? null,
            (float) ($data['price'] ?? 0), (float) ($data['weight'] ?? 0), (float) ($data['length'] ?? 0),
            (float) ($data['width'] ?? 0), (float) ($data['height'] ?? 0), (bool) ($data['is_unlimited_stock'] ?? false),
            (bool) ($data['status'] ?? true), array_map(ProductAttributeSelectionData::fromArray(...), $data['attributes'] ?? []),
        );
    }
}
