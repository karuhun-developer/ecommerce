<?php

use function Laravel\Folio\name;
use function Laravel\Folio\render;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Illuminate\View\View;

name('cms.review.index');

render(function (View $view) {
    $title = 'Review Management';
    $description = 'Manage User Reviews';
    
    SEOMeta::setTitle($title . ' | ' . config('app.name'));
    SEOMeta::setDescription($description);
    SEOMeta::addMeta('robots', 'noindex, nofollow');

    OpenGraph::setTitle($title . ' | ' . config('app.name'));
    OpenGraph::setDescription($description);
    
    $view->with(compact('title'));
});
?>

<x-layouts.cms :title="$title . ' | ' . config('app.name')">
    @section('seo')
        {!! SEO::generate() !!}
    @endsection

    <flux:breadcrumbs>
        <flux:breadcrumbs.item icon="home" href="{{ route('cms.dashboard') }}" />
        <flux:breadcrumbs.item>Reviews</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mt-5 mb-4">
        <div>
            <flux:heading size="xl" level="1">Review Management</flux:heading>
            <flux:subheading size="lg" class="mb-6">Validate user reviews for products and shops.</flux:subheading>
        </div>
    </div>

    <livewire:cms.review.table lazy />
</x-layouts.cms>
