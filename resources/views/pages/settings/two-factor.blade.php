<?php

use Laravel\Fortify\Features;

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;

name('two-factor.show');

// Check if two-factor authentication management requires password confirmation
middleware(
    when(
        Features::canManageTwoFactorAuthentication()
            && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
        ['password.confirm'],
        [],
    )
)

?>

<x-layouts.app title="Two Factor Authentication">
    <x-setting.layout :heading="__('Two Factor Authentication')" :subheading="__('Manage your two-factor authentication settings')">
        <livewire:setting.two-factor />
    </x-setting.layout>
</x-layouts.app>
