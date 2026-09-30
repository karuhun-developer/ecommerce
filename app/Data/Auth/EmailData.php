<?php

namespace App\Data\Auth;

final readonly class EmailData
{
    public function __construct(public string $email) {}

    /** @param array{email: string} $data */
    public static function fromArray(array $data): self
    {
        return new self($data['email']);
    }
}
