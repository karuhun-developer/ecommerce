<?php

namespace App\Data\Content;

final readonly class FooterGroupData
{
    public function __construct(public string $name, public bool $active, public int $sort_order) {}

    /** @return array{name: string, active: bool, sort_order: int} */
    public function attributes(): array
    {
        return get_object_vars($this);
    }
}
