<?php

namespace Database\Factories\Location;

use App\Models\Location\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
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
            'shop_id' => null,
            'biteship_area_id' => fake()->uuid(),
            'area_string' => fake()->city(),
            'name' => 'Home',
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'postal_code' => fake()->postcode(),
            'latitude' => (string) fake()->latitude(),
            'longitude' => (string) fake()->longitude(),
            'type' => 'destination',
        ];
    }
}
