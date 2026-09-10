<?php

namespace App\Actions\Ecommerce\Checkout;

use App\Mail\OrderPlaced;
use App\Models\Order\Order;
use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopItem;
use App\Models\Product\ProductFlat;
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
    public function handle(array $data): Order
    {
        $order = DB::transaction(function () use ($data) {
            $user = auth()->user();
            $locationId = $data['selected_location_id'] ?? null;

            if ($user && ! $user->locations()->whereKey($locationId)->exists()) {
                throw ValidationException::withMessages([
                    'selectedLocationId' => 'Alamat pengiriman tidak valid.',
                ]);
            }

            $guestData = $user ? null : validator($data['guest_data'] ?? [], [
                'contact_name' => ['required', 'string', 'max:255'],
                'contact_phone' => ['required', 'string', 'max:30'],
                'email' => ['required', 'email', 'max:255'],
                'address' => ['required', 'string', 'max:1000'],
                'note' => ['nullable', 'string', 'max:1000'],
                'postal_code' => ['required', 'string', 'max:20'],
                'area_string' => ['required', 'string', 'max:255'],
                'biteship_area_id' => ['required', 'string', 'max:255'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
            ])->validate();

            $canonicalShopGroups = [];
            $totalCheckout = 0;
            $totalShipping = 0;

            foreach ($data['shop_groups'] ?? [] as $shopId => $shopGroup) {
                $shopId = (int) $shopId;
                $selectedRate = $shopGroup['selected_rate'] ?? null;

                if (! is_array($selectedRate) || ! is_numeric($selectedRate['price'] ?? null) || $selectedRate['price'] < 0) {
                    throw ValidationException::withMessages([
                        'shopRates' => 'Tarif pengiriman tidak valid.',
                    ]);
                }

                $canonicalItems = [];
                $shopTotal = 0;

                foreach ($shopGroup['items'] ?? [] as $productFlatId => $item) {
                    $quantity = (int) ($item['qty'] ?? 0);

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

                $shippingTotal = (float) $selectedRate['price'];
                $totalCheckout += $shopTotal;
                $totalShipping += $shippingTotal;
                $canonicalShopGroups[] = [
                    'shop_id' => $shopId,
                    'selected_rate' => $selectedRate,
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

            // Generate a unique reference for the order
            $reference = $this->generateReference(
                model: Order::whereDate('created_at', '=', now()),
                prefix: 'TRX-'.now()->format('Ymd').'-',
            );

            // Create the order
            $order = Order::create([
                'user_id' => $user?->id,
                'location_id' => $locationId,
                'reference' => $reference['code'],
                'access_token' => $user ? null : Str::random(64),
                'ref_number' => $reference['number'],
                'guest_data' => $user ? null : [
                    'contact_name' => $guestData['contact_name'],
                    'contact_email' => $guestData['email'],
                    'contact_phone' => $guestData['contact_phone'],
                    'address' => $guestData['address'],
                    'note' => $guestData['note'] ?? null,
                    'postal_code' => $guestData['postal_code'],
                    'area_string' => $guestData['area_string'],
                    'biteship_area_id' => $guestData['biteship_area_id'],
                    'latitude' => $guestData['latitude'],
                    'longitude' => $guestData['longitude'],
                ],
                'total_checkout' => $totalCheckout,
                'total_shipping' => $totalShipping,
                'application_fee' => self::APPLICATION_FEE,
                'insurance_fee' => self::INSURANCE_FEE,
                'payment_fee' => 0, // Fill later after user selects payment method
                'tax_total' => 0, // Fill later after calculating tax
                'total' => $totalCheckout + $totalShipping + self::INSURANCE_FEE + self::APPLICATION_FEE,
                'status' => false, // Fill later after payment confirmation
            ]);

            // Create order shops for each shop group
            foreach ($canonicalShopGroups as $shopGroup) {
                $orderShop = OrderShop::create([
                    'order_id' => $order->id,
                    'shop_id' => $shopGroup['shop_id'],
                    'waybill_number' => null, // Fill later after shipping confirmation
                    'shipping_data' => $shopGroup['selected_rate'],
                    'total_checkout' => $shopGroup['total_checkout'],
                    'total_shipping' => $shopGroup['total_shipping'],
                    'tax' => 0,
                    'total' => $shopGroup['total'],
                    'shipping_status' => false,
                ]);

                // Create order items for each shop
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
