<?php

namespace Database\Factories\Payment;

use App\Models\Order\Order;
use App\Models\Payment\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'driver' => 'midtrans',
            'payable_type' => Order::class,
            'payable_id' => Order::factory(),
            'order_id' => fake()->unique()->uuid(),
            'transaction_id' => fake()->unique()->uuid(),
            'payment_type' => 'bank_transfer',
            'account_number' => fake()->numerify('##########'),
            'channel' => 'bca',
            'expired_at' => now()->addDay(),
            'amount' => 113_500,
            'fee' => 4_500,
            'total' => 118_000,
        ];
    }
}
