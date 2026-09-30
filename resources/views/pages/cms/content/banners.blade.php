<?php

use Illuminate\View\View;

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;
use function Laravel\Folio\render;

name('cms.content.banners');
middleware(['can:manageWebsiteContent']);

render(function (View $view): void {
    $title = 'Banner Homepage';
    $description = 'Atur gambar promosi, jadwal, dan urutan banner homepage.';
    $breadcrumbs = [
        ['label' => 'Konten website', 'url' => '#'],
        ['label' => 'Banner Homepage', 'url' => null],
    ];

    $view->with(compact('title', 'description', 'breadcrumbs'));
}); ?>

<x-layouts.app :$title>
    <div class="w-full">
        <div class="flex flex-wrap justify-between items-center gap-4 mb-5">
            <h1 class="text-3xl font-bold">{{ $title }}</h1>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('cms.dashboard') }}" icon="home" />
                @foreach($breadcrumbs as $breadcrumb)
                    @if($breadcrumb['url'])
                        <flux:breadcrumbs.item href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</flux:breadcrumbs.item>
                    @else
                        <flux:breadcrumbs.item>{{ $breadcrumb['label'] }}</flux:breadcrumbs.item>
                    @endif
                @endforeach
            </flux:breadcrumbs>
        </div>
        <div class="mb-6">
            <flux:text>{{ $description }}</flux:text>
        </div>
        <livewire:cms.content.banner.table />
    </div>
</x-layouts.app>
