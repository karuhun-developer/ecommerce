<?php

namespace App\Data\Callbacks;

final readonly class PaywuzCallbackData
{
    public function __construct(
        public string $rawBody,
        public mixed $signature,
        public mixed $event,
        public mixed $deliveryId,
    ) {}
}
