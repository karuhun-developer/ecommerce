<?php

namespace App\Data\Cms;

final readonly class UserData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $role = null,
        public ?string $password = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self($data['name'], $data['email'], $data['role'] ?? null, $data['password'] ?? null);
    }

    public function attributes(): array
    {
        return ['name' => $this->name, 'email' => $this->email, 'role' => $this->role, 'password' => $this->password];
    }
}
