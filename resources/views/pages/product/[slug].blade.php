<?php

use App\Models\Product\Product;
use Illuminate\View\View;
use function Laravel\Folio\name;
use function Laravel\Folio\render;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLd;

name('product.detail');

// Page title and breadcrumbs
render(function (View $view, string $slug) {
    $product = Product::with('mainProductFlat')->where('slug', $slug)->firstOrFail();

    $title = $product->name;
    $description = str(strip_tags($product->description))->limit(150);

    $url = url()->current();

    // Set SEO meta tags
    $image = $product->mainProductFlat && $product->mainProductFlat->getFirstMediaUrl('image_slot_0') 
        ? $product->mainProductFlat->getFirstMediaUrl('image_slot_0') 
        : asset('logo.png');

    SEOMeta::setTitle($title . ' | ' . config('app.name'));
    SEOMeta::setDescription($description);
    SEOMeta::setKeywords([$product->name, 'beli ' . $product->name, 'harga ' . $product->name, config('app.name')]);
    SEOMeta::setCanonical($url);

    OpenGraph::setDescription($description);
    OpenGraph::setTitle($title . ' | ' . config('app.name'));
    OpenGraph::setUrl($url);
    OpenGraph::addProperty('type', 'product');
    OpenGraph::setSiteName(config('app.name'));
    OpenGraph::addImage($image);

    TwitterCard::setTitle($title . ' | ' . config('app.name'));
    TwitterCard::setSite('@' . strtolower(config('app.name')));
    TwitterCard::setDescription($description);
    TwitterCard::setImage($image);

    JsonLd::setTitle($title . ' | ' . config('app.name'));
    JsonLd::setDescription($description);
    JsonLd::addImage($image);

    return $view->with(compact('product', 'title'));
}); ?>
<x-layouts.ecommerce :title="$title . ' | ' . config('app.name')">
    @section('seo')
        {!! SEO::generate() !!}
    @endsection

    <livewire:ecommerce.product.detail :$product />
</x-layouts.ecommerce>
