<?php

namespace Database\Factories\Order;

use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopItem;
use App\Models\Product\ProductFlat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderShopItem>
 */
class OrderShopItemFactory extends Factory
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
            'order_id' => fn (array $attributes) => OrderShop::query()->find($attributes['order_shop_id'])?->order_id,
            'product_flat_id' => ProductFlat::factory(),
            'product_data' => ['name' => fake()->words(3, true), 'weight' => 100],
            'quantity' => 1,
            'price' => 100_000,
            'total' => 100_000,
        ];
    }
}
