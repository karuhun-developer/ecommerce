<?php

namespace App\Actions\Api\V1\Callback;

use App\Models\Order\OrderShopShipment;
use Illuminate\Support\Facades\Log;

class HandleBiteshipCallbackAction
{
    public function handle(array $payload)
    {
        Log::info('Biteship Callback Received', [
            'request' => $payload,
        ]);

        if (! isset($payload['event']) || $payload['event'] !== 'order.status') {
            return;
        }

        $waybillId = $payload['courier_waybill_id'] ?? null;
        $trackingId = $payload['courier_tracking_id'] ?? null;

        if (! $waybillId && ! $trackingId) {
            throw new \Exception('Missing courier identification', 400);
        }

        // Find the latest shipment to get order_shop_id
        $latestShipment = OrderShopShipment::where(function ($query) use ($waybillId, $trackingId) {
            if ($waybillId) {
                $query->where('courier_waybill_id', $waybillId);
            }
            if ($trackingId) {
                $query->orWhere('courier_tracking_id', $trackingId);
            }
        })
            ->latest('id')
            ->first();

        if (! $latestShipment) {
            Log::warning('Biteship callback received for unknown shipment', [
                'waybill_id' => $waybillId,
                'tracking_id' => $trackingId,
            ]);
            throw new \Exception('Unknown shipment', 404);
        }

        $status = $payload['status'] ?? $latestShipment->status;

        $shipment = OrderShopShipment::create([
            'order_shop_id' => $latestShipment->order_shop_id,
            'event' => $payload['event'],
            'courier_tracking_id' => $trackingId,
            'courier_waybill_id' => $waybillId,
            'courier_name' => $payload['courier_name'] ?? $latestShipment->courier_name,
            'courier_company' => $payload['courier_company'] ?? $latestShipment->courier_company,
            'courier_type' => $payload['courier_type'] ?? $latestShipment->courier_type,
            'courier_driver_name' => $payload['courier_driver_name'] ?? null,
            'courier_driver_phone' => $payload['courier_driver_phone'] ?? null,
            'courier_driver_photo_url' => $payload['courier_driver_photo_url'] ?? null,
            'courier_driver_plate_number' => $payload['courier_driver_plate_number'] ?? null,
            'courier_link' => $payload['courier_link'] ?? null,
            'status' => $status,
        ]);

        if ($status === 'courier_not_found') {
            $orderShop = $latestShipment->orderShop;
            if ($orderShop) {
                $orderShop->update([
                    'waybill_number' => null,
                    'shipping_status' => false,
                    'shipping_note' => 'Kurir tidak ditemukan. Silakan atur pengiriman ulang.',
                ]);
            }
        }

        return $shipment;
    }
}
