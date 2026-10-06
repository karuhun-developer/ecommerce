<?php

namespace App\Data\Cms;

final readonly class CourierSettingData
{
    public function __construct(public string $code, public bool $enabled) {}
}
