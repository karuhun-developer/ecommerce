<?php

use function Laravel\Folio\name;
use function Laravel\Folio\render;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Illuminate\View\View;

name('account.reviews');

render(function (View $view) {
    $title = 'Ulasan Saya';
    $description = 'Daftar ulasan yang pernah Anda berikan.';
    
    SEOMeta::setTitle($title . ' | ' . config('app.name'));
    SEOMeta::setDescription($description);
    SEOMeta::addMeta('robots', 'noindex, nofollow');

    OpenGraph::setTitle($title . ' | ' . config('app.name'));
    OpenGraph::setDescription($description);
    
    $view->with(compact('title'));
});
?>

<x-layouts.ecommerce :title="$title . ' | ' . config('app.name')">
    @section('seo')
        {!! SEO::generate() !!}
    @endsection

    <div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
        <div class="max-w-6xl mx-auto px-4 md:px-6">
            <livewire:ecommerce.review-list />
        </div>
    </div>
</x-layouts.ecommerce>
