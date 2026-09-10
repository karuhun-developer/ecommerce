<?php

namespace Database\Factories\Order;

use App\Models\Order\Order;
use App\Models\Order\OrderShop;
use App\Models\Shop\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderShop>
 */
class OrderShopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'shop_id' => Shop::factory(),
            'shipping_data' => [
                'company' => 'jne',
                'type' => 'reg',
                'price' => 10_000,
            ],
            'total_checkout' => 100_000,
            'total_shipping' => 10_000,
            'tax' => 0,
            'total' => 110_000,
            'shipping_status' => false,
        ];
    }
}
