<?php

namespace Database\Factories\Content;

use App\Models\Content\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        return ['title' => fake()->sentence(3), 'slug' => fake()->unique()->slug(), 'body' => '<p>'.fake()->paragraph().'</p>', 'published' => false, 'sort_order' => 0];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['published' => true]);
    }
}
