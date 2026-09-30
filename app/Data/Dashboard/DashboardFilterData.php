<?php

namespace App\Data\Dashboard;

use Carbon\CarbonImmutable;

final readonly class DashboardFilterData
{
    public function __construct(public CarbonImmutable $start, public CarbonImmutable $end, public ?int $shopId = null) {}
}
