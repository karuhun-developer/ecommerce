<?php

use Livewire\Livewire;

it('registers the cms.management.role.table component', function () {
    expect(Livewire::exists('cms.management.role.table'))->toBeTrue();
});
