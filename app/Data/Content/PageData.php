<?php

namespace App\Data\Content;

final readonly class PageData
{
    public function __construct(public string $title, public string $slug, public string $body, public bool $published, public ?string $footer_group, public int $sort_order) {}

    public function attributes(): array
    {
        return get_object_vars($this);
    }
}
