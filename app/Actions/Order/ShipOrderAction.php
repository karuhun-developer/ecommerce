<?php

namespace App\Actions\Order;

use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopShipment;
use App\Services\BiteshipService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShipOrderAction
{
    protected BiteshipService $biteshipService;

    public function __construct(BiteshipService $biteshipService)
    {
        $this->biteshipService = $biteshipService;
    }

    public function execute(OrderShop $orderShop)
    {
        if ($orderShop->shipping_status) {
            throw new Exception('Order shop sudah dikirim.');
        }

        $orderShop->loadMissing([
            'shop.location',
            'shop.user',
            'order.location',
            'order.user',
            'items',
        ]);

        $shop = $orderShop->shop;
        $order = $orderShop->order;

        $payload = $this->buildPayload($orderShop, $shop, $order);

        try {
            DB::beginTransaction();

            $response = $this->biteshipService->createOrder($payload);

            if (! isset($response['id'])) {
                throw new Exception('Gagal membuat pesanan di Biteship.');
            }

            // Save tracking information
            $shipment = OrderShopShipment::create([
                'order_shop_id' => $orderShop->id,
                'event' => 'create_order',
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

            $orderShop->update([
                'waybill_number' => $response['courier']['waybill_id'] ?? null,
                'shipping_status' => true,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Pesanan berhasil dikirim melalui kurir',
                'shipment' => $shipment,
                'response' => $response,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Biteship ShipOrderAction Error', [
                'order_shop_id' => $orderShop->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function buildPayload(OrderShop $orderShop, $shop, $order): array
    {
        $originContactName = $shop->location->contact_name ?? $shop->name;
        $originContactPhone = $shop->location->contact_phone ?? '08123456789';
        $originAddress = $shop->location->address ?? 'Toko Default Address';
        $originNote = $shop->location->note ?? '';
        $originPostalCode = $shop->location->postal_code ?? '12345';
        $originLat = $shop->location->latitude ?? -6.2253114;
        $originLng = $shop->location->longitude ?? 106.7993735;

        if ($order->guest_data) {
            $destContactName = $order->guest_data['contact_name'] ?? 'Guest';
            $destContactPhone = $order->guest_data['contact_phone'] ?? '08123456789';
            $destContactEmail = $order->guest_data['contact_email'] ?? 'guest@example.com';
            $destAddress = $order->guest_data['address'] ?? '';
            $destPostalCode = $order->guest_data['postal_code'] ?? '12345';
            $destNote = $order->guest_data['note'] ?? '';
            $destLat = $order->guest_data['latitude'] ?? -6.28927;
            $destLng = $order->guest_data['longitude'] ?? 106.77492;
        } else {
            $destContactName = $order->location->contact_name ?? ($order->user->name ?? 'Buyer');
            $destContactPhone = $order->location->contact_phone ?? '08123456789';
            $destContactEmail = $order->location->contact_email ?? ($order->user->email ?? 'buyer@example.com');
            $destAddress = $order->location->address ?? '';
            $destPostalCode = $order->location->postal_code ?? '12345';
            $destNote = $order->location->note ?? '';
            $destLat = $order->location->latitude ?? -6.28927;
            $destLng = $order->location->longitude ?? 106.77492;
        }

        $courierCompany = $orderShop->shipping_data['company'] ?? 'jne';
        $courierType = $orderShop->shipping_data['type'] ?? 'reg';

        $items = $orderShop->items->map(function ($item) {
            return [
                'name' => $item->product_data['name'] ?? $item->name,
                'description' => substr(strip_tags($item->product_data['description'] ?? ''), 0, 200),
                'value' => (float) $item->price,
                'quantity' => $item->qty,
                'weight' => $item->product_data['weight'] ?? 100,
            ];
        })->toArray();

        return [
            'shipper_contact_name' => $shop->user->name ?? $shop->name,
            'shipper_contact_phone' => $shop->user->phone ?? $originContactPhone,
            'shipper_contact_email' => $shop->user->email ?? 'shop@example.com',
            'shipper_organization' => $shop->name,

            'origin_contact_name' => $originContactName,
            'origin_contact_phone' => $originContactPhone,
            'origin_address' => $originAddress,
            'origin_note' => $originNote,
            'origin_postal_code' => $originPostalCode,
            'origin_coordinate' => [
                'latitude' => (float) $originLat,
                'longitude' => (float) $originLng,
            ],

            'destination_contact_name' => $destContactName,
            'destination_contact_phone' => $destContactPhone,
            'destination_contact_email' => $destContactEmail,
            'destination_address' => $destAddress,
            'destination_postal_code' => $destPostalCode,
            'destination_note' => $destNote,
            'destination_coordinate' => [
                'latitude' => (float) $destLat,
                'longitude' => (float) $destLng,
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
    }
}
