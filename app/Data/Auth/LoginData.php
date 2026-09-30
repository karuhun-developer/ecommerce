<?php

namespace App\Data\Auth;

final readonly class LoginData
{
    public function __construct(public string $email, public string $password) {}

    /** @param array{email: string, password: string} $data */
    public static function fromArray(array $data): self
    {
        return new self($data['email'], $data['password']);
    }

    /** @return array{email: string, password: string} */
    public function credentials(): array
    {
        return ['email' => $this->email, 'password' => $this->password];
    }
}
