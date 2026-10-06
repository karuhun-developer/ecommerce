<?php

namespace App\Data\Cms;

final readonly class ShippingRateSettingsData
{
    public function __construct(public string $method) {}
}
