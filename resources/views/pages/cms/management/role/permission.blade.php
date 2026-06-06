<?php

use App\Models\Spatie\Role;
use Illuminate\View\View;

use function Laravel\Folio\name;
use function Laravel\Folio\render;

name('cms.management.role.permission');

// Page title and breadcrumbs
render(function (View $view) {
    $title = 'Management Role Permissions';
    $description = 'Manage the application\'s management role permission items here.';
    $breadcrumbs = [
        [
            'label' => 'Management',
            'url' => '#'
        ],
        [
            'label' => 'Role',
            'url' => route('cms.management.role')
        ],
        [
            'label' => 'Permissions',
            'url' => '',
        ],
    ];

    // Get role
    $role = Role::findOrFail(request('role_id'));

    $view->with(compact('title', 'description', 'breadcrumbs', 'role'));
}); ?>

<x-layouts.app :$title>
    <div class="w-full">
        <div class="flex justify-between items-center mb-5">
            <div class="flex items-center gap-4">
                <flux:button
                    href="{{ route('cms.management.role') }}"
                    size="sm"
                    variant="primary"
                    icon="arrow-left"
                    wire:navigate
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
        <livewire:cms.management.role.permission :$role />
    </div>
</x-layouts.app>

