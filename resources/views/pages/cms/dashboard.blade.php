<?php

use function Laravel\Folio\name;

name('cms.dashboard');

?>

<x-layouts.app :title="__('Dashboard')">
    <livewire:cms.dashboard.analytics />
</x-layouts.app>
