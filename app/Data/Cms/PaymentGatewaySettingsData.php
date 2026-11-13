<?php

namespace App\Data\Cms;

final readonly class PaymentGatewaySettingsData
{
    /** @param array<string, mixed> $configuration */
    public function __construct(
        public string $driver,
        public array $configuration,
    ) {}
}
