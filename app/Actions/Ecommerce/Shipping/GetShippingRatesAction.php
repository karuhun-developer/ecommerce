<?php

namespace App\Actions\Ecommerce\Shipping;

use App\Data\Checkout\CheckoutItemData;
use App\Data\Checkout\ShippingRatesData;
use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Services\BiteshipService;
use Exception;

class GetShippingRatesAction
{
    private const COURIERS = 'jne,tiki,lion,ninja,jnt,sicepat';

    public function __construct(private BiteshipService $biteshipService) {}

    public function handle(ShippingRatesData $data): array
    {
        $shopId = $data->shopId;
        $destinationAreaId = $data->destinationAreaId;
        $items = collect($data->items)->mapWithKeys(fn (CheckoutItemData $item): array => [$item->productFlatId => $item->quantity])->all();
        foreach ($items as $quantity) {
            if ($quantity < 1 || $quantity > 100) {
                throw new Exception('Item pengiriman tidak valid.');
            }
        }
        $shop = Shop::with('location')->find($shopId);

        if (! $shop || ! $shop->location || ! $shop->location->biteship_area_id) {
            throw new Exception('Informasi lokasi toko belum lengkap.');
        }

        if (blank($destinationAreaId)) {
            throw new Exception('Pilih alamat pengiriman terlebih dahulu.');
        }

        $itemIds = collect($items)->keys()->toArray();
        $flats = ProductFlat::query()
            ->where('shop_id', $shop->id)
            ->whereIn('id', $itemIds)
            ->where('status', true)
            ->get()
            ->keyBy('id');

        if ($flats->count() !== count($itemIds)) {
            throw new Exception('Item pengiriman tidak valid.');
        }

        $biteshipItems = $flats->map(fn ($flat) => [
            'name' => $flat->name,
            'value' => (int) $flat->price,
            'quantity' => $items[$flat->id],
            'weight' => max(1, (int) ($flat->weight ?? 1)),
            'length' => max(1, (int) ($flat->length ?? 1)),
            'width' => max(1, (int) ($flat->width ?? 1)),
            'height' => max(1, (int) ($flat->height ?? 1)),
        ])->values()->toArray();

        $requestPayload = [
            'origin_area_id' => $shop->location->biteship_area_id,
            'destination_area_id' => $destinationAreaId,
            'couriers' => self::COURIERS,
            'items' => $biteshipItems,
        ];

        $cacheKey = 'biteship_rates_'.md5(json_encode($requestPayload));

        $response = cache()->remember($cacheKey, now()->addHours(24), function () use ($requestPayload) {
            return $this->biteshipService->getRates($requestPayload);
        });

        $rates = collect($response['pricing'] ?? [])
            ->filter(fn ($rate) => ($rate['available'] ?? true) && ! ($rate['error'] ?? false))
            ->sortBy('price')
            ->values()
            ->toArray();

        if (empty($rates)) {
            throw new Exception('Tidak ada layanan kurir yang tersedia untuk rute ini.');
        }

        return $rates;
    }
}
