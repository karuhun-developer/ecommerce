<?php

namespace App\Actions\Ecommerce\Checkout;

use App\Data\Checkout\CartData;
use App\Data\Checkout\CheckoutItemData;
use App\Models\Product\ProductFlat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ResolveShopGroupsAction
{
    /**
     * Groups checkout items by shop_id.
     */
    public function handle(CartData $data): array
    {
        $requestedQuantities = collect($data->items)->mapWithKeys(fn (CheckoutItemData $item): array => [$item->productFlatId => $item->quantity]);

        if ($requestedQuantities->isEmpty()) {
            return [];
        }

        $flats = ProductFlat::query()
            ->with(['shop', 'product'])
            ->whereIn('id', $requestedQuantities->keys())
            ->where('status', true)
            ->whereHas('product', fn (Builder $query) => $query->where('status', true))
            ->get()
            ->keyBy('id');

        if ($flats->count() !== $requestedQuantities->count()) {
            throw ValidationException::withMessages([
                'cart' => 'Salah satu produk tidak tersedia lagi.',
            ]);
        }

        $groups = [];
        foreach ($flats as $flat) {
            $quantity = $requestedQuantities->get($flat->id);

            if (! $flat->is_unlimited_stock && $quantity > $flat->stock) {
                throw ValidationException::withMessages([
                    'cart' => "Stok {$flat->name} tidak mencukupi.",
                ]);
            }

            $shopId = $flat->shop_id;
            if (! isset($groups[$shopId])) {
                $groups[$shopId] = [
                    'shop_id' => $shopId,
                    'shop_name' => $flat->shop->name,
                    'items' => [],
                ];
            }

            $groups[$shopId]['items'][$flat->id] = $quantity;
        }

        return array_values($groups);
    }
}
