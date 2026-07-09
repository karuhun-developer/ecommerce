<?php

use function Laravel\Folio\name;

name('checkout');

?>
<x-layouts.ecommerce title="Checkout | {{ config('app.name') }}">
    <livewire:ecommerce.checkout.checkout :selectedIds="request('items', '')" />
</x-layouts.ecommerce>
