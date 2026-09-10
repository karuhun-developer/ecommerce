<?php

use Livewire\Livewire;

it('registers the ecommerce.component.banner component', function () {
    expect(Livewire::exists('ecommerce.component.banner'))->toBeTrue();
});
