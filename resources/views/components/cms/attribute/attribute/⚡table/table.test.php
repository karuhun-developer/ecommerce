<?php

use Livewire\Livewire;

it('registers the cms.attribute.attribute.table component', function () {
    expect(Livewire::exists('cms.attribute.attribute.table'))->toBeTrue();
});
