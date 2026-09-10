<?php

use Livewire\Livewire;

it('registers the ecommerce.explore component', function () {
    expect(Livewire::exists('ecommerce.explore'))->toBeTrue();
});
