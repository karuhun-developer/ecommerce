<?php

namespace Database\Factories\Order;

use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'location_id' => null,
            'reference' => 'TRX-'.fake()->unique()->numerify('##########'),
            'access_token' => Str::random(64),
            'ref_number' => fake()->unique()->numberBetween(1, 999_999),
            'total_checkout' => 100_000,
            'total_shipping' => 10_000,
            'application_fee' => 1_000,
            'insurance_fee' => 2_500,
            'payment_fee' => 0,
            'tax_total' => 0,
            'total' => 113_500,
            'status' => false,
        ];
    }

    public function guest(): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'guest_data' => [
                'contact_name' => fake()->name(),
                'contact_email' => fake()->safeEmail(),
                'contact_phone' => fake()->numerify('08##########'),
                'address' => fake()->address(),
                'postal_code' => fake()->postcode(),
                'biteship_area_id' => fake()->uuid(),
            ],
        ]);
    }
}
