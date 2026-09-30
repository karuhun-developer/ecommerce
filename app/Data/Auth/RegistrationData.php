<?php

namespace App\Data\Auth;

final readonly class RegistrationData
{
    public function __construct(public string $name, public string $email, public string $password, public ?string $phone = null) {}

    /** @param array{name: string, email: string, password: string, phone?: ?string} $data */
    public static function fromArray(array $data): self
    {
        return new self($data['name'], $data['email'], $data['password'], $data['phone'] ?? null);
    }

    /** @return array{name: string, email: string, password: string, phone: ?string} */
    public function attributes(): array
    {
        return ['name' => $this->name, 'email' => $this->email, 'password' => $this->password, 'phone' => $this->phone];
    }
}
