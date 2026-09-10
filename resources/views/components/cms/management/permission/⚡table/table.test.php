<?php

use Livewire\Livewire;

it('registers the cms.management.permission.table component', function () {
    expect(Livewire::exists('cms.management.permission.table'))->toBeTrue();
});
