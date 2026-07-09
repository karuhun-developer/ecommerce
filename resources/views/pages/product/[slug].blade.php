<?php

use App\Models\Product\Product;
use Illuminate\View\View;

use function Laravel\Folio\name;
use function Laravel\Folio\render;

name('product.detail');

// Page title and breadcrumbs
render(function (View $view, string $slug) {
    $product = Product::where('slug', $slug)->firstOrFail();

    return $view->with(compact('product'));
}); ?>
<x-layouts.ecommerce title="{{ $product->name }} | {{ config('app.name') }}">
    <livewire:ecommerce.product.detail :$product />
</x-layouts.ecommerce>
