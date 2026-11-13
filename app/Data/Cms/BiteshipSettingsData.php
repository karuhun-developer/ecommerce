<?php

namespace App\Data\Cms;

final readonly class BiteshipSettingsData
{
    /** @param array<string, mixed> $configuration */
    public function __construct(public array $configuration) {}
}
