<?php

use Livewire\Livewire;

it('registers the ecommerce.home component', function () {
    expect(Livewire::exists('ecommerce.home'))->toBeTrue();
});
