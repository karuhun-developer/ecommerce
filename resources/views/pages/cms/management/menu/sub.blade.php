<?php

use App\Models\Menu\Menu;
use App\Models\Spatie\Role;
use Illuminate\View\View;

use function Laravel\Folio\name;
use function Laravel\Folio\render;

name('cms.management.menu.sub');

// Page title and breadcrumbs
render(function (View $view) {
    // Get menu
    $menu = Menu::findOrFail(request()->get('menu_id'));

    $title = 'Management Sub Menu - ' . $menu->name;
    $description = 'Manage the application\'s management sub menu items for ' . $menu->name . '.';
    $breadcrumbs = [
        [
            'label' => 'Management',
            'url' => '#'
        ],
        [
            'label' => 'Menu',
            'url' => route('cms.management.menu')
        ],
        [
            'label' => 'Sub Menu',
            'url' => null,
        ],
    ];

    $view->with(compact('title', 'description', 'breadcrumbs', 'menu'));
}); ?>

<x-layouts.app :$title>
    <div class="w-full">
        <div class="flex justify-between items-center mb-5">
            <div class="flex items-center gap-4">
                <flux:button
                    href="{{ route('cms.management.menu') }}"
                    size="sm"
                    variant="primary"
                    icon="arrow-left"

                />
                <h1 class="text-3xl font-bold">{{ $title }}</h1>
            </div>
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
        <div class="border-gray-200 mb-6">
            <flux:text>
                {{ $description }}
            </flux:text>
        </div>
        <livewire:cms.management.menu.sub.table :$menu />
    </div>
</x-layouts.app>

