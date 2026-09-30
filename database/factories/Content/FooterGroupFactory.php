<?php

namespace Database\Factories\Content;

use App\Models\Content\FooterGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class FooterGroupFactory extends Factory
{
    protected $model = FooterGroup::class;

    public function definition(): array
    {
        return ['key' => fake()->uuid(), 'name' => fake()->words(2, true), 'active' => true, 'sort_order' => 0];
    }
}
