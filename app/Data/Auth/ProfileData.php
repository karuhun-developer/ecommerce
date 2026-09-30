<?php

namespace App\Data\Auth;

use Illuminate\Http\UploadedFile;

final readonly class ProfileData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone = null,
        public ?string $password = null,
        public ?UploadedFile $image = null,
    ) {}

    /** @param array{name: string, email: string, phone?: ?string, password?: ?string, image?: ?UploadedFile} $data */
    public static function fromArray(array $data): self
    {
        return new self($data['name'], $data['email'], $data['phone'] ?? null, $data['password'] ?? null, $data['image'] ?? null);
    }
}
