<?php

use function Laravel\Folio\name;
use function Laravel\Folio\render;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLd;
use Illuminate\View\View;

name('home');

render(function (View $view) {
    $title = 'Toko Online Terpercaya & Terlengkap';
    $description = 'Temukan berbagai macam produk berkualitas dengan harga terbaik hanya di ' . config('app.name') . '. Belanja aman, mudah, dan cepat.';
    $url = url()->current();
    $logo = asset('logo.png');

    SEOMeta::setTitle(config('app.name') . ' | ' . $title);
    SEOMeta::setDescription($description);
    SEOMeta::setKeywords(['toko online', 'belanja murah', 'ecommerce terpercaya', 'produk berkualitas', config('app.name')]);
    SEOMeta::setCanonical($url);

    OpenGraph::setDescription($description);
    OpenGraph::setTitle(config('app.name') . ' | ' . $title);
    OpenGraph::setUrl($url);
    OpenGraph::addProperty('type', 'website');
    OpenGraph::setSiteName(config('app.name'));
    OpenGraph::addImage($logo);

    TwitterCard::setTitle(config('app.name') . ' | ' . $title);
    TwitterCard::setSite('@' . strtolower(config('app.name')));
    TwitterCard::setDescription($description);
    TwitterCard::setImage($logo);

    JsonLd::setTitle(config('app.name') . ' | ' . $title);
    JsonLd::setDescription($description);
    JsonLd::addImage($logo);

    $view->with(compact('title'));
});

?>

<x-layouts.ecommerce :title="config('app.name') . ' | ' . $title">
    @section('seo')
        {!! SEO::generate() !!}
    @endsection

    <livewire:ecommerce.home />
</x-layouts.ecommerce>
