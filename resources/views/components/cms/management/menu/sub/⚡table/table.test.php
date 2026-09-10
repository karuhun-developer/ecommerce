<?php

use Livewire\Livewire;

it('registers the cms.management.menu.sub.table component', function () {
    expect(Livewire::exists('cms.management.menu.sub.table'))->toBeTrue();
});
