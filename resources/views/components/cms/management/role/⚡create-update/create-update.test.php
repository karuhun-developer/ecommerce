<?php

use Livewire\Livewire;

it('registers the cms.management.role.create-update component', function () {
    expect(Livewire::exists('cms.management.role.create-update'))->toBeTrue();
});
