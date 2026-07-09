<?php

use function Laravel\Folio\name;

name('cart');

?>

<x-layouts.ecommerce title="Your Cart | {{ config('app.name') }}">
    <livewire:ecommerce.cart.detail />
</x-layouts.ecommerce>
