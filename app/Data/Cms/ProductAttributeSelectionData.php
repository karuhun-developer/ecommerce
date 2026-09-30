<?php

namespace App\Data\Cms;

final readonly class ProductAttributeSelectionData
{
    /** @param list<int> $attributes */
    public function __construct(public int $group_id, public array $attributes) {}

    /** @param array{group_id: int|string, attributes: array<int, int|string>} $data */
    public static function fromArray(array $data): self
    {
        return new self((int) $data['group_id'], array_values(array_unique(array_map(intval(...), $data['attributes']))));
    }

    /** @return array{group_id: int, attributes: list<int>} */
    public function attributes(): array
    {
        return ['group_id' => $this->group_id, 'attributes' => $this->attributes];
    }
}
