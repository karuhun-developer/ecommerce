<?php

use App\Models\Product\Product;
use App\Models\User;
use Database\Factories\OrderReviewFactory;
use Livewire\Livewire;

it('registers the ecommerce.product.review component', function () {
    expect(Livewire::exists('ecommerce.product.review'))->toBeTrue();
});

it('shows only approved reviews and allowlists its filter', function () {
    $product = Product::factory()->create([
        'rating' => 5,
        'total_reviews' => 1,
    ]);
    $reviewer = User::factory()->create(['name' => 'Approved Reviewer']);

    OrderReviewFactory::new()->create([
        'user_id' => $reviewer->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $product->id,
        'rating' => 5,
        'comment' => 'Approved review content',
        'status' => 'approved',
    ]);

    OrderReviewFactory::new()->create([
        'reviewable_type' => Product::class,
        'reviewable_id' => $product->id,
        'rating' => 1,
        'comment' => 'Rejected review content',
        'status' => 'rejected',
    ]);

    Livewire::test('ecommerce.product.review', ['product' => $product])
        ->assertSee('Approved review content')
        ->assertDontSee('Rejected review content')
        ->call('setFilter', 'unsupported-filter')
        ->assertSet('filter', 'all');
});
