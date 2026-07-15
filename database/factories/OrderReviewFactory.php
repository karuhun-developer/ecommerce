<?php

namespace Database\Factories;

use App\Models\Order\OrderReview;
use App\Models\Order\OrderShop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderReviewFactory extends Factory
{
    protected $model = OrderReview::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_shop_id' => OrderShop::factory(),
            'reviewable_type' => 'App\\Models\\Order\\OrderShopItem',
            'reviewable_id' => 1,
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->sentence(),
            'status' => 'pending',
        ];
    }
}
