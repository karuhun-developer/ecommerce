<?php

namespace App\Data\Checkout;

final readonly class CheckoutItemData
{
    public function __construct(public int $productFlatId, public int $quantity) {}
}
