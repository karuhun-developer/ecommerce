<?php

use Livewire\Livewire;

it('registers the ecommerce.component.footer component', function () {
    expect(Livewire::exists('ecommerce.component.footer'))->toBeTrue();
});
