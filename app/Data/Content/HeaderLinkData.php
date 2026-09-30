<?php

namespace App\Data\Content;

final readonly class HeaderLinkData
{
    public function __construct(public string $label, public string $position, public string $destination, public ?int $page_id, public ?string $url, public bool $active, public int $sort_order) {}

    /** @return array{label: string, position: string, destination: string, page_id: ?int, url: ?string, active: bool, sort_order: int} */
    public function attributes(): array
    {
        return get_object_vars($this);
    }
}
