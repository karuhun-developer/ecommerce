<?php

namespace Database\Factories\Content;

use App\Models\Content\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return ['title' => fake()->sentence(3), 'subtitle' => fake()->sentence(), 'image_alt' => fake()->sentence(4), 'active' => false, 'sort_order' => 0];
    }
}
