<?php

namespace App\Data\Content;

final readonly class StorefrontData
{
    public function __construct(public string $brand_name, public ?string $tagline = null, public ?string $instagram_url = null, public ?string $website_url = null, public ?string $whatsapp_url = null) {}

    public function attributes(): array
    {
        return get_object_vars($this);
    }
}
