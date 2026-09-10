<?php

use Livewire\Livewire;

it('registers the cms.management.menu.create-update component', function () {
    expect(Livewire::exists('cms.management.menu.create-update'))->toBeTrue();
});
