<?php

namespace App\Actions\Ecommerce\Checkout;

use App\Data\Checkout\CheckoutData;
use App\Mail\OrderPlaced;
use App\Models\Order\Order;
use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopItem;
use App\Models\Product\ProductFlat;
use App\Models\User;
use App\Traits\WithGenerateReference;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StoreCheckoutAction
{
    use WithGenerateReference;

    private const int APPLICATION_FEE = 1000;

    private const int INSURANCE_FEE = 2500;

    /**
     * Groups checkout items by shop_id.
     */
    public function handle(CheckoutData $data, ?User $actor): Order
    {
        $order = DB::transaction(function () use ($data, $actor) {
            $user = $actor;
            $locationId = $user ? $data->locationId : null;

            if ($user && ! $user->locations()->whereKey($locationId)->exists()) {
                throw ValidationException::withMessages([
                    'selectedLocationId' => 'Alamat pengiriman tidak valid.',
                ]);
            }

            $guestData = $user ? null : $data->guest;
            if (! $user && ! $guestData) {
                throw ValidationException::withMessages(['guest' => 'Data pengiriman wajib diisi.']);
            }

            $canonicalShopGroups = [];
            $totalCheckout = 0;
            $totalShipping = 0;

            foreach ($data->shops as $shopGroup) {
                $shopId = $shopGroup->shopId;
                $selectedRate = $shopGroup->rate;

                if ($selectedRate->price < 0) {
                    throw ValidationException::withMessages([
                        'shopRates' => 'Tarif pengiriman tidak valid.',
                    ]);
                }

                $canonicalItems = [];
                $shopTotal = 0;

                foreach ($shopGroup->items as $item) {
                    $productFlatId = $item->productFlatId;
                    $quantity = $item->quantity;

                    if ($quantity < 1 || $quantity > 100) {
                        throw ValidationException::withMessages([
                            'cart' => 'Jumlah produk harus antara 1 dan 100.',
                        ]);
                    }

                    $productFlat = ProductFlat::query()
                        ->whereKey($productFlatId)
                        ->where('shop_id', $shopId)
                        ->where('status', true)
                        ->whereHas('product', fn ($query) => $query->where('status', true))
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (! $productFlat->is_unlimited_stock && $quantity > $productFlat->stock) {
                        throw ValidationException::withMessages([
                            'cart' => "Stok {$productFlat->name} tidak mencukupi.",
                        ]);
                    }

                    $itemTotal = (float) $productFlat->price * $quantity;
                    $shopTotal += $itemTotal;
                    $canonicalItems[] = [
                        'product_flat' => $productFlat,
                        'quantity' => $quantity,
                        'total' => $itemTotal,
                    ];
                }

                if ($canonicalItems === []) {
                    throw ValidationException::withMessages([
                        'cart' => 'Keranjang belanja kosong.',
                    ]);
                }

                $shippingTotal = $selectedRate->price;
                $totalCheckout += $shopTotal;
                $totalShipping += $shippingTotal;
                $canonicalShopGroups[] = [
                    'shop_id' => $shopId,
                    'selected_rate' => $selectedRate->attributes(),
                    'items' => $canonicalItems,
                    'total_checkout' => $shopTotal,
                    'total_shipping' => $shippingTotal,
                    'total' => $shopTotal + $shippingTotal,
                ];
            }

            if ($canonicalShopGroups === []) {
                throw ValidationException::withMessages([
                    'cart' => 'Keranjang belanja kosong.',
                ]);
            }
            $reference = $this->generateReference(
                model: Order::whereDate('created_at', '=', now()),
                prefix: 'TRX-'.now()->format('Ymd').'-',
            );
            $order = Order::create([
                'user_id' => $user?->id,
                'location_id' => $locationId,
                'reference' => $reference['code'],
                'access_token' => $user ? null : Str::random(64),
                'ref_number' => $reference['number'],
                'guest_data' => $guestData?->attributes(),
                'total_checkout' => $totalCheckout,
                'total_shipping' => $totalShipping,
                'application_fee' => self::APPLICATION_FEE,
                'insurance_fee' => self::INSURANCE_FEE,
                'payment_fee' => 0,
                'tax_total' => 0,
                'total' => $totalCheckout + $totalShipping + self::INSURANCE_FEE + self::APPLICATION_FEE,
                'status' => false,
            ]);
            foreach ($canonicalShopGroups as $shopGroup) {
                $orderShop = OrderShop::create([
                    'order_id' => $order->id,
                    'shop_id' => $shopGroup['shop_id'],
                    'waybill_number' => null,
                    'shipping_data' => $shopGroup['selected_rate'],
                    'total_checkout' => $shopGroup['total_checkout'],
                    'total_shipping' => $shopGroup['total_shipping'],
                    'tax' => 0,
                    'total' => $shopGroup['total'],
                    'shipping_status' => false,
                ]);
                foreach ($shopGroup['items'] as $item) {
                    $productFlat = $item['product_flat'];
                    OrderShopItem::create([
                        'order_id' => $order->id,
                        'order_shop_id' => $orderShop->id,
                        'product_flat_id' => $productFlat->id,
                        'product_data' => $productFlat->toArray(),
                        'quantity' => $item['quantity'],
                        'price' => $productFlat->price,
                        'total' => $item['total'],
                    ]);
                }
            }

            return $order;
        });

        $email = $order->user?->email ?? $order->guest_data['contact_email'] ?? null;

        if ($email) {
            $order->load(['orderShops.shop', 'orderShops.items.productFlat.media', 'location']);

            try {
                Mail::to($email)->send(new OrderPlaced($order));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $order;
    }
}
