<?php

namespace App\Data\Checkout;

final readonly class ShippingRatesData
{
    /** @param list<CheckoutItemData> $items */
    public function __construct(public int $shopId, public string $destinationAreaId, public array $items, public ?float $destinationLatitude = null, public ?float $destinationLongitude = null) {}

    public static function fromArray(int $shopId, string $destinationAreaId, array $items, ?float $destinationLatitude = null, ?float $destinationLongitude = null): self
    {
        $entries = [];
        foreach ($items as $id => $qty) {
            $entries[] = new CheckoutItemData((int) $id, (int) $qty);
        }

        return new self($shopId, $destinationAreaId, $entries, $destinationLatitude, $destinationLongitude);
    }
}
