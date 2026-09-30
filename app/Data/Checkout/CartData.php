<?php

namespace App\Data\Checkout;

final readonly class CartData
{
    /** @param list<CheckoutItemData> $items */
    public function __construct(public array $items) {}

    public static function fromArray(array $cartItems, array $selectedIds): self
    {
        $quantities = [];
        foreach ($cartItems as $item) {
            if (is_array($item) && isset($item['id'],$item['qty']) && in_array((int) $item['id'], $selectedIds, true)) {
                $quantities[(int) $item['id']] = min(100, max(1, (int) $item['qty']));
            }
        }
        $items = [];
        foreach ($quantities as $id => $qty) {
            $items[] = new CheckoutItemData($id, $qty);
        }

        return new self($items);
    }
}
