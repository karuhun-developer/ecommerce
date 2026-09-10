<?php

namespace Database\Factories\Product;

use App\Models\Product\Product;
use App\Models\Shop\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'type' => 'simple',
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(10_000, 1_000_000),
            'weight' => fake()->numberBetween(100, 5_000),
            'length' => 10,
            'width' => 10,
            'height' => 10,
            'stock' => 10,
            'is_unlimited_stock' => false,
            'status' => true,
        ];
    }
}
