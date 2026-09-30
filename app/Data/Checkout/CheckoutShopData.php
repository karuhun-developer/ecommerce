<?php

namespace App\Data\Checkout;

final readonly class CheckoutShopData
{
    /** @param list<CheckoutItemData> $items */
    public function __construct(public int $shopId, public ShippingRateData $rate, public array $items) {}
}
