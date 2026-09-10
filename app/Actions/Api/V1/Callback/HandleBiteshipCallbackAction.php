<?php

namespace App\Actions\Api\V1\Callback;

use App\Mail\OrderDelivered;
use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopShipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HandleBiteshipCallbackAction
{
    /** @param array<string, mixed> $payload */
    public function handle(array $payload): ?OrderShopShipment
    {
        $event = $payload['event'] ?? null;

        Log::info('Biteship callback received', [
            'event' => is_scalar($event) ? (string) $event : null,
            'status' => is_scalar($payload['status'] ?? null) ? (string) $payload['status'] : null,
            'courier_waybill_id' => $payload['courier_waybill_id'] ?? null,
            'courier_tracking_id' => $payload['courier_tracking_id'] ?? null,
        ]);

        if ($event !== 'order.status') {
            return null;
        }

        $waybillId = $this->nullableString($payload['courier_waybill_id'] ?? null);
        $trackingId = $this->nullableString($payload['courier_tracking_id'] ?? null);

        if (! $waybillId && ! $trackingId) {
            throw new \Exception('Missing courier identification', 400);
        }

        $providerEventKey = $this->providerEventKey($payload);

        return DB::transaction(function () use ($payload, $waybillId, $trackingId, $providerEventKey): OrderShopShipment {
            $latestShipment = OrderShopShipment::query()
                ->where(function (Builder $query) use ($waybillId, $trackingId): void {
                    if ($waybillId) {
                        $query->where('courier_waybill_id', $waybillId);
                    }

                    if ($trackingId) {
                        $waybillId
                            ? $query->orWhere('courier_tracking_id', $trackingId)
                            : $query->where('courier_tracking_id', $trackingId);
                    }
                })
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $latestShipment) {
                Log::warning('Biteship callback received for unknown shipment', [
                    'courier_waybill_id' => $waybillId,
                    'courier_tracking_id' => $trackingId,
                ]);

                throw new \Exception('Unknown shipment', 404);
            }

            $orderShop = OrderShop::query()
                ->with('order.user')
                ->lockForUpdate()
                ->findOrFail($latestShipment->order_shop_id);

            $existingShipment = OrderShopShipment::query()
                ->where('provider_event_key', $providerEventKey)
                ->first();

            if ($existingShipment) {
                return $existingShipment;
            }

            $status = $this->nullableString($payload['status'] ?? null) ?? $latestShipment->status;
            $wasDelivered = $orderShop->shipping_status || OrderShopShipment::query()
                ->where('order_shop_id', $orderShop->getKey())
                ->where('status', 'delivered')
                ->exists();

            $shipment = OrderShopShipment::query()->create([
                'order_shop_id' => $orderShop->getKey(),
                'event' => 'order.status',
                'provider_event_key' => $providerEventKey,
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
                $orderShop->update([
                    'waybill_number' => null,
                    'shipping_status' => false,
                    'shipping_note' => 'Kurir tidak ditemukan. Silakan atur pengiriman ulang.',
                ]);
            } elseif ($status === 'delivered') {
                $orderShop->update([
                    'shipping_status' => true,
                    'shipping_note' => null,
                ]);

                $email = $orderShop->order->user?->email ?? data_get($orderShop->order->guest_data, 'contact_email');

                if (! $wasDelivered && $email) {
                    DB::afterCommit(fn () => Mail::to($email)->send(new OrderDelivered($orderShop)));
                }
            }

            return $shipment;
        });
    }

    /** @param array<string, mixed> $payload */
    private function providerEventKey(array $payload): string
    {
        return hash('sha256', json_encode(
            $this->sortPayload($payload),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    private function sortPayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->sortPayload($value);
            }
        }

        if (! array_is_list($payload)) {
            ksort($payload);
        }

        return $payload;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }
}
