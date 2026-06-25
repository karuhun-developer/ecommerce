<?php

use function Laravel\Folio\name;

name('checkout');

?>
<x-layouts.ecommerce>
    <livewire:ecommerce.checkout.checkout :selectedIds="request('items', '')" />
</x-layouts.ecommerce>
