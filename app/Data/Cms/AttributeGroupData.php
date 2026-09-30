<?php

namespace App\Data\Cms;

final readonly class AttributeGroupData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self($data['name'], $data['description'] ?? null);
    }

    public function attributes(): array
    {
        return ['name' => $this->name, 'description' => $this->description];
    }
}
