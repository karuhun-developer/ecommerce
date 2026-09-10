<?php

namespace Database\Factories\Product;

use App\Models\Product\Product;
use App\Models\Product\ProductFlat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductFlat>
 */
class ProductFlatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'shop_id' => fn (array $attributes) => Product::query()->find($attributes['product_id'])?->shop_id,
            'name' => fake()->words(3, true),
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
