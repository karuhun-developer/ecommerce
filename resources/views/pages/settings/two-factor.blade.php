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

<x-layouts.app title="Two-Factor Authentication Settings">
    <livewire:setting.two-factor />
</x-layouts.app>
