<?php

use function Laravel\Folio\name;

name('home');

?>

<x-layouts.ecommerce title="Best Online Store | {{ config('app.name') }}">
    <livewire:ecommerce.home />
</x-layouts.ecommerce>
