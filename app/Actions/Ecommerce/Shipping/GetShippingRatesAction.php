<?php

namespace App\Actions\Ecommerce\Shipping;

use App\Data\Checkout\CheckoutItemData;
use App\Data\Checkout\ShippingRatesData;
use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Services\BiteshipService;
use App\Services\CourierSettingsService;
use Exception;

class GetShippingRatesAction
{
    public function __construct(private BiteshipService $biteshipService, private CourierSettingsService $courierSettings) {}

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

        if (! $shop || ! $shop->location) {
            throw new Exception('Informasi lokasi toko belum lengkap.');
        }

        $usesAreaIds = $this->courierSettings->usesAreaIds();

        if ($usesAreaIds && blank($shop->location->biteship_area_id)) {
            throw new Exception('Informasi lokasi toko belum lengkap.');
        }

        if ($usesAreaIds && blank($destinationAreaId)) {
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

        $couriers = $this->courierSettings->enabledCodes();
        if ($couriers === []) {
            throw new Exception('Belum ada kurir pengiriman yang diaktifkan.');
        }

        $hasInstantCouriers = array_intersect($couriers, CourierSettingsService::COORDINATE_COURIERS) !== [];
        $requestPayload = [
            'couriers' => implode(',', $couriers),
            'items' => $biteshipItems,
        ];

        if ($usesAreaIds) {
            $requestPayload['origin_area_id'] = $shop->location->biteship_area_id;
            $requestPayload['destination_area_id'] = $destinationAreaId;
        } else {
            $coordinates = [
                'origin_latitude' => $shop->location->latitude,
                'origin_longitude' => $shop->location->longitude,
                'destination_latitude' => $data->destinationLatitude,
                'destination_longitude' => $data->destinationLongitude,
            ];
            $validator = validator($coordinates, [
                'origin_latitude' => ['required', 'numeric', 'between:-90,90'],
                'origin_longitude' => ['required', 'numeric', 'between:-180,180'],
                'destination_latitude' => ['required', 'numeric', 'between:-90,90'],
                'destination_longitude' => ['required', 'numeric', 'between:-180,180'],
            ]);

            if ($validator->fails()) {
                throw new Exception('Lengkapi titik lokasi toko dan alamat pengiriman di peta.');
            }

            $requestPayload = array_merge($requestPayload, array_map(fn ($coordinate): float => (float) $coordinate, $coordinates));
        }

        $cacheKey = 'biteship_rates_'.md5(json_encode($requestPayload));

        $response = $hasInstantCouriers
            ? $this->biteshipService->getRates($requestPayload)
            : cache()->remember($cacheKey, now()->addHours(24), fn (): array => $this->biteshipService->getRates($requestPayload));

        $rates = collect($response['pricing'] ?? [])
            ->filter(fn ($rate) => ($rate['available'] ?? true) && ! ($rate['error'] ?? false))
            ->filter(fn (array $rate): bool => in_array($rate['courier_code'] ?? '', $couriers, true))
            ->sortBy('price')
            ->values()
            ->toArray();

        if (empty($rates)) {
            throw new Exception('Tidak ada layanan kurir yang tersedia untuk rute ini.');
        }

        return $rates;
    }
}
