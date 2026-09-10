<?php

namespace Database\Factories\Order;

use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopShipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderShopShipment>
 */
class OrderShopShipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_shop_id' => OrderShop::factory(),
            'event' => 'order.status',
            'provider_event_key' => fake()->unique()->sha256(),
            'courier_tracking_id' => fake()->uuid(),
            'courier_waybill_id' => fake()->uuid(),
            'courier_company' => 'jne',
            'courier_type' => 'reg',
            'status' => 'allocated',
        ];
    }
}
