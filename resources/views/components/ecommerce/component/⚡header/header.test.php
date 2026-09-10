<?php

use Livewire\Livewire;

it('registers the ecommerce.component.header component', function () {
    expect(Livewire::exists('ecommerce.component.header'))->toBeTrue();
});
