<?php

use Livewire\Livewire;

it('registers the cms.attribute.group.table component', function () {
    expect(Livewire::exists('cms.attribute.group.table'))->toBeTrue();
});
