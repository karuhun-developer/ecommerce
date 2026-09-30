<?php

namespace App\Data\Cms;

final readonly class ProductFlatData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public float $price,
        public float $weight,
        public float $length,
        public float $width,
        public float $height,
        public int $stock,
        public bool $is_unlimited_stock,
    ) {}

    /** @param array{name: string, description?: ?string, price: int|float|string, weight: int|float|string, length: int|float|string, width: int|float|string, height: int|float|string, stock: int|string, is_unlimited_stock: bool|int|string} $data */
    public static function fromArray(array $data): self
    {
        return new self($data['name'], $data['description'] ?? null, (float) $data['price'], (float) $data['weight'], (float) $data['length'], (float) $data['width'], (float) $data['height'], (int) $data['stock'], (bool) $data['is_unlimited_stock']);
    }

    /** @return array{name: string, description: ?string, price: float, weight: float, length: float, width: float, height: float, stock: int, is_unlimited_stock: bool} */
    public function attributes(): array
    {
        return get_object_vars($this);
    }
}
