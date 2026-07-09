<?php

namespace App\Actions\Ecommerce\Checkout;

use App\Models\Order\Order;
use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopItem;
use App\Traits\WithGenerateReference;
use Illuminate\Support\Facades\DB;

class StoreCheckoutAction
{
    use WithGenerateReference;

    /**
     * Groups checkout items by shop_id.
     */
    public function handle(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Generate a unique reference for the order
            $reference = $this->generateReference(
                model: Order::whereDate('created_at', '=', now()),
                prefix: 'TRX-'.now()->format('Ymd').'-',
            );

            // Create the order
            $order = Order::create([
                'user_id' => auth()?->id(),
                'location_id' => $data['selected_location_id'],
                'reference' => $reference['code'],
                'ref_number' => $reference['number'],
                'guest_data' => $data['guest_data'],
                'total_checkout' => $data['total_checkout'],
                'total_shipping' => $data['total_rates'],
                'application_fee' => $data['jasa_aplikasi'],
                'insurance_fee' => $data['asuransi_pengiriman'],
                'payment_fee' => 0, // Fill later after user selects payment method
                'tax_total' => 0, // Fill later after calculating tax
                'total' => $data['total_checkout'] + $data['total_rates'] + $data['asuransi_pengiriman'] + $data['jasa_aplikasi'],
                'status' => false, // Fill later after payment confirmation
            ]);

            // Create order shops for each shop group
            foreach ($data['shop_groups'] as $shopId => $item) {
                $orderShop = OrderShop::create([
                    'order_id' => $order->id,
                    'shop_id' => $shopId,
                    'waybill_number' => null, // Fill later after shipping confirmation
                    'shipping_data' => $item['selected_rate'],
                    'total_checkout' => $item['total_checkout'],
                    'total_shipping' => $item['total_shipping'],
                    'tax' => 0,
                    'total' => $item['total'],
                    'shipping_status' => false,
                ]);

                // Create order items for each shop
                foreach ($item['items'] as $id => $item) {
                    OrderShopItem::create([
                        'order_id' => $order->id,
                        'order_shop_id' => $orderShop->id,
                        'product_flat_id' => $id,
                        'product_data' => $item['raw'],
                        'quantity' => $item['qty'],
                        'price' => $item['price'],
                        'total' => $item['total'],
                    ]);
                }
            }

            return $order;
        });
    }
}
