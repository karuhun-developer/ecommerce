<?php

use Livewire\Livewire;

it('registers the ecommerce.component.cart-flyout component', function () {
    expect(Livewire::exists('ecommerce.component.cart-flyout'))->toBeTrue();
});
