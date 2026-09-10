<?php

use Livewire\Livewire;

it('registers the cms.management.menu.sub.create-update component', function () {
    expect(Livewire::exists('cms.management.menu.sub.create-update'))->toBeTrue();
});
