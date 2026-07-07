<?php

namespace App\Actions\Ecommerce\Shipping;

use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Services\BiteshipService;
use Exception;

class GetShippingRatesAction
{
    private const COURIERS = 'jne,tiki,lion,ninja,jnt,sicepat';

    /**
     * Get shipping rates from Biteship API for a specific shop and items.
     *
     * @param  array  $items  Array with product flat IDs as keys and quantities as values.
     *
     * @throws Exception
     */
    public function handle(int $shopId, string $destinationAreaId, array $items): array
    {
        // --- Origin: shop location ---
        $shop = Shop::with('location')->find($shopId);

        if (! $shop || ! $shop->location || ! $shop->location->biteship_area_id) {
            throw new Exception('Informasi lokasi toko belum lengkap.');
        }

        // --- Destination ---
        if (blank($destinationAreaId)) {
            throw new Exception('Pilih alamat pengiriman terlebih dahulu.');
        }

        // --- Items: load weight/dimensions from ProductFlat ---
        $itemIds = collect($items)->keys()->toArray();
        $flats = ProductFlat::whereIn('id', $itemIds)->get()->keyBy('id');

        if ($flats->isEmpty()) {
            throw new Exception('Item tidak ditemukan.');
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

        $biteshipService = app(BiteshipService::class);

        $response = cache()->remember($cacheKey, now()->addHours(24), function () use ($biteshipService, $requestPayload) {
            return $biteshipService->getRates($requestPayload);
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
