<?php

namespace App\Data\Cms;

final readonly class PermissionData
{
    public function __construct(
        public string $name,
        public string $guard_name = 'api',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self($data['name'], $data['guard_name'] ?? 'api');
    }

    public function attributes(): array
    {
        return ['name' => $this->name, 'guard_name' => $this->guard_name];
    }
}
