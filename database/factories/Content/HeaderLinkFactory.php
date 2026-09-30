<?php

namespace Database\Factories\Content;

use App\Models\Content\HeaderLink;
use Illuminate\Database\Eloquent\Factories\Factory;

class HeaderLinkFactory extends Factory
{
    protected $model = HeaderLink::class;

    public function definition(): array
    {
        return ['key' => fake()->uuid(), 'label' => fake()->words(2, true), 'position' => 'left', 'destination' => 'url', 'url' => 'https://example.test', 'active' => true, 'sort_order' => 0];
    }
}
