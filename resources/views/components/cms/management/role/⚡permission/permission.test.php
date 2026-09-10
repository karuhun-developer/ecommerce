<?php

use Livewire\Livewire;

it('registers the cms.management.role.permission component', function () {
    expect(Livewire::exists('cms.management.role.permission'))->toBeTrue();
});
