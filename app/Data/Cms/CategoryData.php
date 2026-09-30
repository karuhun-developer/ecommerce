<?php

namespace App\Data\Cms;

use Illuminate\Http\UploadedFile;

final readonly class CategoryData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public bool $is_featured = false,
        public ?UploadedFile $image = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self($data['name'], $data['description'] ?? null, (bool) ($data['is_featured'] ?? false), $data['image'] ?? null);
    }

    public function attributes(): array
    {
        return ['name' => $this->name, 'description' => $this->description, 'is_featured' => $this->is_featured];
    }
}
