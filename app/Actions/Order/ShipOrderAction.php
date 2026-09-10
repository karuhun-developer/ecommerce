<?php

namespace App\Actions\Order;

use App\Models\Order\Order;
use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopShipment;
use App\Models\Shop\Shop;
use App\Models\User;
use App\Services\BiteshipService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use UnexpectedValueException;

class ShipOrderAction
{
    public function __construct(private readonly BiteshipService $biteshipService) {}

    /**
     * @return array{success: bool, message: string, shipment: OrderShopShipment|null, response: array<string, mixed>}
     */
    public function execute(OrderShop $orderShop, User $user): array
    {
        return Cache::lock($this->lockKey($orderShop), 120)->block(5, function () use ($orderShop, $user): array {
            $orderShop = OrderShop::query()
                ->accessibleTo($user)
                ->with([
                    'shop.location',
                    'shop.user',
                    'order.location',
                    'order.user',
                    'items',
                ])
                ->findOrFail($orderShop->getKey());

            $providerEventKey = $this->providerEventKey($orderShop);
            $existingShipment = $this->existingShipment($orderShop, $providerEventKey);

            if ($existingShipment !== null || $orderShop->shipping_status || filled($orderShop->waybill_number)) {
                return $this->alreadyShippedResult($existingShipment);
            }

            if (! $orderShop->order->status) {
                throw new RuntimeException('Pesanan belum dibayar.');
            }

            $payload = $this->buildPayload($orderShop, $orderShop->shop, $orderShop->order);

            $response = $this->biteshipService->createOrder($payload);

            if (! is_array($response) || ! isset($response['id'])) {
                throw new UnexpectedValueException('Gagal membuat pesanan di Biteship.');
            }

            try {
                return DB::transaction(function () use ($orderShop, $payload, $providerEventKey, $response, $user): array {
                    $lockedOrderShop = OrderShop::query()
                        ->accessibleTo($user)
                        ->lockForUpdate()
                        ->findOrFail($orderShop->getKey());

                    $existingShipment = $this->existingShipment($lockedOrderShop, $providerEventKey);

                    if ($existingShipment !== null) {
                        return $this->alreadyShippedResult($existingShipment);
                    }

                    $shipment = $lockedOrderShop->shipments()->create([
                        'event' => 'create_order',
                        'provider_event_key' => $providerEventKey,
                        'courier_tracking_id' => $response['courier']['tracking_id'] ?? null,
                        'courier_waybill_id' => $response['courier']['waybill_id'] ?? null,
                        'courier_name' => $response['courier']['name'] ?? $payload['courier_company'],
                        'courier_company' => $response['courier']['company'] ?? $payload['courier_company'],
                        'courier_type' => $response['courier']['type'] ?? $payload['courier_type'],
                        'courier_driver_name' => $response['courier']['driver_name'] ?? null,
                        'courier_driver_phone' => $response['courier']['driver_phone'] ?? null,
                        'courier_driver_photo_url' => $response['courier']['driver_photo_url'] ?? null,
                        'courier_driver_plate_number' => $response['courier']['driver_plate_number'] ?? null,
                        'courier_link' => $response['courier']['link'] ?? null,
                        'status' => $response['status'] ?? 'allocated',
                    ]);

                    $lockedOrderShop->update([
                        'waybill_number' => $response['courier']['waybill_id'] ?? null,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Pesanan berhasil dikirim melalui kurir',
                        'shipment' => $shipment,
                        'response' => $response,
                    ];
                }, 3);
            } catch (UniqueConstraintViolationException) {
                return $this->alreadyShippedResult(
                    OrderShopShipment::query()
                        ->where('provider_event_key', $providerEventKey)
                        ->firstOrFail(),
                );
            }
        });
    }

    private function existingShipment(OrderShop $orderShop, string $providerEventKey): ?OrderShopShipment
    {
        return $orderShop->shipments()
            ->where(function ($query) use ($providerEventKey): void {
                $query->where('provider_event_key', $providerEventKey)
                    ->orWhere(function ($query): void {
                        $query->where('event', 'create_order')
                            ->whereNotNull('courier_waybill_id');
                    });
            })
            ->latest('id')
            ->first();
    }

    /**
     * @return array{success: bool, message: string, shipment: OrderShopShipment|null, response: array<string, mixed>}
     */
    private function alreadyShippedResult(?OrderShopShipment $shipment): array
    {
        return [
            'success' => true,
            'message' => 'Pesanan sudah dikirim melalui kurir',
            'shipment' => $shipment,
            'response' => [],
        ];
    }

    private function lockKey(OrderShop $orderShop): string
    {
        return 'order-shop:'.$orderShop->getKey().':ship';
    }

    private function providerEventKey(OrderShop $orderShop): string
    {
        return hash('sha256', 'biteship:create-order:'.$orderShop->getKey());
    }

    private function buildPayload(OrderShop $orderShop, Shop $shop, Order $order): array
    {
        $originLocation = $shop->location;
        $originContactName = $originLocation?->contact_name ?? $shop->name;
        $originContactPhone = $originLocation?->contact_phone;
        $originAddress = $originLocation?->address;
        $originNote = $originLocation?->note ?? '';
        $originPostalCode = $originLocation?->postal_code;
        $originLat = $originLocation?->latitude;
        $originLng = $originLocation?->longitude;

        if ($order->guest_data) {
            $destContactName = $order->guest_data['contact_name'] ?? null;
            $destContactPhone = $order->guest_data['contact_phone'] ?? null;
            $destContactEmail = $order->guest_data['contact_email'] ?? null;
            $destAddress = $order->guest_data['address'] ?? null;
            $destPostalCode = $order->guest_data['postal_code'] ?? null;
            $destNote = $order->guest_data['note'] ?? '';
            $destLat = $order->guest_data['latitude'] ?? null;
            $destLng = $order->guest_data['longitude'] ?? null;
        } else {
            $destContactName = $order->location?->contact_name ?? $order->user?->name;
            $destContactPhone = $order->location?->contact_phone;
            $destContactEmail = $order->location?->contact_email ?? $order->user?->email;
            $destAddress = $order->location?->address;
            $destPostalCode = $order->location?->postal_code;
            $destNote = $order->location?->note ?? '';
            $destLat = $order->location?->latitude;
            $destLng = $order->location?->longitude;
        }

        $courierCompany = $orderShop->shipping_data['company'] ?? null;
        $courierType = $orderShop->shipping_data['type'] ?? null;

        $items = $orderShop->items->map(function ($item): array {
            return [
                'name' => $item->product_data['name'] ?? null,
                'description' => Str::limit(strip_tags($item->product_data['description'] ?? ''), 200, ''),
                'value' => (float) $item->price,
                'quantity' => $item->quantity,
                'weight' => $item->product_data['weight'] ?? null,
            ];
        })->all();

        $payload = [
            'shipper_contact_name' => $shop->user->name ?? $shop->name,
            'shipper_contact_phone' => $shop->user->phone ?? $originContactPhone,
            'shipper_contact_email' => $shop->user->email,
            'shipper_organization' => $shop->name,
            'origin_contact_name' => $originContactName,
            'origin_contact_phone' => $originContactPhone,
            'origin_address' => $originAddress,
            'origin_note' => $originNote,
            'origin_postal_code' => $originPostalCode,
            'origin_coordinate' => [
                'latitude' => $originLat,
                'longitude' => $originLng,
            ],
            'destination_contact_name' => $destContactName,
            'destination_contact_phone' => $destContactPhone,
            'destination_contact_email' => $destContactEmail,
            'destination_address' => $destAddress,
            'destination_postal_code' => $destPostalCode,
            'destination_note' => $destNote,
            'destination_coordinate' => [
                'latitude' => $destLat,
                'longitude' => $destLng,
            ],
            'courier_company' => $courierCompany,
            'courier_type' => $courierType,
            'delivery_type' => 'now',
            'delivery_date' => now()->format('Y-m-d'),
            'delivery_time' => now()->addMinutes(30)->format('H:i'),
            'order_note' => $order->reference,
            'metadata' => [
                'order_shop_id' => $orderShop->id,
            ],
            'items' => $items,
        ];

        $this->ensurePayloadIsComplete($payload);

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private function ensurePayloadIsComplete(array $payload): void
    {
        $requiredFields = [
            'shipper_contact_name',
            'shipper_contact_phone',
            'shipper_contact_email',
            'shipper_organization',
            'origin_contact_name',
            'origin_contact_phone',
            'origin_address',
            'origin_postal_code',
            'destination_contact_name',
            'destination_contact_phone',
            'destination_contact_email',
            'destination_address',
            'destination_postal_code',
            'courier_company',
            'courier_type',
        ];

        foreach ($requiredFields as $field) {
            if (blank(data_get($payload, $field))) {
                throw new RuntimeException('Data pengiriman belum lengkap.');
            }
        }

        if (! $this->hasValidCoordinates($payload, 'origin_coordinate')
            || ! $this->hasValidCoordinates($payload, 'destination_coordinate')) {
            throw new RuntimeException('Data pengiriman belum lengkap.');
        }

        if (empty($payload['items'])) {
            throw new RuntimeException('Data pengiriman belum lengkap.');
        }

        foreach ($payload['items'] as $item) {
            if (blank($item['name'] ?? null)
                || ! is_numeric($item['value'] ?? null)
                || (float) $item['value'] < 0
                || ! is_numeric($item['quantity'] ?? null)
                || (int) $item['quantity'] < 1
                || ! is_numeric($item['weight'] ?? null)
                || (float) $item['weight'] <= 0) {
                throw new RuntimeException('Data pengiriman belum lengkap.');
            }
        }
    }

    /** @param array<string, mixed> $payload */
    private function hasValidCoordinates(array $payload, string $field): bool
    {
        $latitude = data_get($payload, $field.'.latitude');
        $longitude = data_get($payload, $field.'.longitude');

        return is_numeric($latitude)
            && is_numeric($longitude)
            && (float) $latitude >= -90
            && (float) $latitude <= 90
            && (float) $longitude >= -180
            && (float) $longitude <= 180;
    }
}
