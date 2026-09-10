<?php

use Livewire\Livewire;

it('registers the cms.management.menu.table component', function () {
    expect(Livewire::exists('cms.management.menu.table'))->toBeTrue();
});
