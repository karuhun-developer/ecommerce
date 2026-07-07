<?php

namespace App\Actions\Ecommerce\Checkout;

class ResolveShopGroupsAction
{
    /**
     * Groups checkout items by shop_id.
     */
    public function handle(array $cartItems, array $selectedIds): array
    {
        // Filter to checked items
        $items = empty($selectedIds) ? $cartItems : array_filter($cartItems, fn ($i) => in_array((int) $i['id'], $selectedIds));

        // Group by shop_id
        $groups = [];
        foreach ($items as $item) {
            $shopId = (int) ($item['shop_id'] ?? 0);
            if (! isset($groups[$shopId])) {
                $groups[$shopId] = [
                    'shop_id' => $shopId,
                    'shop_name' => $item['shop_name'] ?? 'Toko',
                    'items' => [],
                ];
            }

            // Store item id as key and qty as value for easier access in shipping-rates component
            $groups[$shopId]['items'][$item['id']] = $item['qty'];
        }

        return array_values($groups);
    }
}
