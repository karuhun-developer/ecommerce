<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('components.layouts.partials.head')
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <flux:sidebar.brand
                    href="#"
                    logo="https://fluxui.dev/img/demo/logo.png"
                    logo:dark="https://fluxui.dev/img/demo/dark-mode-logo.png"
                    :name="config('app.name')"
                />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            @php
                if (! function_exists('menuActive')) {
                    function menuActive($activeRoute = []): bool {
                        // Check if route name contains the active route
                        foreach ($activeRoute as $route) {
                            if(str_contains(request()->route()->getName(), $route) || request()->routeIs($route)) {
                                return true;
                                break;
                            }
                        }

                        return false;
                    }
                }

                if (! function_exists('showDropdown')) {
                    // Show dropdown menu if the route is active
                    function showDropdown($activeRoute = []): bool {
                        foreach ($activeRoute as $route) {
                            if(str_contains(request()->route()->getName(), $route) || request()->routeIs($route)) {
                                return true;
                                break;
                            }
                        }

                        return false;
                    }
                }

                if (! function_exists('echoRoute')) {
                    // Echo route
                    function echoRoute($url) {
                        try {
                            return route($url);
                        } catch (\Exception $e) {
                            return '#';
                        }
                    }
                }

                // Check user roles
                $listMenus = getMenus();
            @endphp
            <flux:sidebar.nav>
                @foreach($listMenus as $mainMenu)
                    @if(count($mainMenu->subMenu) > 0)
                        <flux:sidebar.group
                            heading="{{ $mainMenu->name }}"
                            expandable
                            :expanded="showDropdown(explode(',', $mainMenu->active_pattern))">
                            @if ($mainMenu->icon)
                                <x-slot name="icon">
                                    <flux:icon name="{{ $mainMenu->icon }}" variant="micro" />
                                </x-slot>
                            @endif
                            @foreach($mainMenu->subMenu as $child)
                                <flux:sidebar.item
                                    href="{{ echoRoute($child->url) }}"
                                    :current="menuActive(explode(',', $child->active_pattern))"
                                    wire:navigate>
                                    {{ $child->name }}
                                </flux:sidebar.item>
                            @endforeach
                        </flux:sidebar.group>
                    @else
                        <flux:sidebar.item
                            icon="{{ $mainMenu->icon }}"
                            href="{{ echoRoute($mainMenu->url) }}"
                            :current="menuActive(explode(',', $mainMenu->active_pattern))"
                            wire:navigate>
                            {{ $mainMenu->name }}
                        </flux:sidebar.item>
                    @endif
                @endforeach
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav variant="outline">
                <flux:sidebar.item icon="screen-share" href="{{ url('pulse') }}" target="_blank">
                    Laravel Pulse
                </flux:sidebar.item>

                <flux:sidebar.item icon="scroll-text" href="{{ url('logs') }}" target="_blank">
                    Laravel Logs
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @livewireScriptConfig
        @fluxScripts
    </body>
</html>
