<?php

namespace App\Data\Cms;

final readonly class AttributeData
{
    public function __construct(
        public int $attribute_group_id,
        public string $name,
        public string $value,
        public ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self((int) ($data['attribute_group_id']), $data['name'], $data['value'], $data['description'] ?? null);
    }

    public function attributes(): array
    {
        return ['attribute_group_id' => $this->attribute_group_id, 'name' => $this->name, 'value' => $this->value, 'description' => $this->description];
    }
}
