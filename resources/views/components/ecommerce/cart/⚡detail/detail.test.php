<?php

use Livewire\Livewire;

it('registers the ecommerce.cart.detail component', function () {
    expect(Livewire::exists('ecommerce.cart.detail'))->toBeTrue();
});
