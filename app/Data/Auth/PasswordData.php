<?php

namespace App\Data\Auth;

final readonly class PasswordData
{
    public function __construct(public string $password) {}
}
