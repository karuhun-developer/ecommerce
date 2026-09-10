<?php

use Livewire\Livewire;

it('registers the ecommerce.check-order component', function () {
    expect(Livewire::exists('ecommerce.check-order'))->toBeTrue();
});
