<?php

use App\Models\Location\Location;
use App\Models\Product\Product;
use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Models\User;
use Livewire\Livewire;

it('registers the ecommerce.product.detail component', function () {
    expect(Livewire::exists('ecommerce.product.detail'))->toBeTrue();
});

it('renders the selected product and its shop', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->for($owner)->create(['name' => 'Visible Shop']);
    Location::factory()->for($owner)->create([
        'shop_id' => $shop->id,
        'type' => 'origin',
    ]);
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'name' => 'Visible Product Variant',
    ]);

    Livewire::test('ecommerce.product.detail', ['product' => $product])
        ->assertSee($productFlat->name)
        ->assertSee($shop->name)
        ->assertSet('variants', []);
});
