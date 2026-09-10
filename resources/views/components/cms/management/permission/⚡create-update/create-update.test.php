<?php

use Livewire\Livewire;

it('registers the cms.management.permission.create-update component', function () {
    expect(Livewire::exists('cms.management.permission.create-update'))->toBeTrue();
});
