<?php

use App\Models\Product\Product;
use App\Models\Product\ProductFlat;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sanitizes product HTML on writes and when reading legacy descriptions', function (string $model) {
    $html = '<h2>Koleksi</h2><p><strong>Aman</strong></p>'
        .'<script>alert(1)</script><img src="/images/product.jpg" onerror="alert(2)">'
        .'<a href="javascript:alert(3)">Bahaya</a><a href="/products">Katalog</a>';

    $product = $model::factory()->create(['description' => $html]);
    $stored = $product->fresh()->getRawOriginal('description');

    expect($stored)->toContain('<h2>Koleksi</h2>', '<strong>Aman</strong>', 'src="/images/product.jpg"', 'href="/products"', 'rel="noopener noreferrer"')
        ->not->toContain('<script', 'onerror', 'javascript:');

    $model::query()->whereKey($product->id)->update(['description' => $html]);
    $legacyProduct = $product->fresh();

    expect($legacyProduct->getRawOriginal('description'))->toBe($html)
        ->and($legacyProduct->description)->toBe($stored)
        ->and($legacyProduct->toArray()['description'])->toBe($stored);
})->with([
    'products' => [Product::class],
    'product variants' => [ProductFlat::class],
]);
