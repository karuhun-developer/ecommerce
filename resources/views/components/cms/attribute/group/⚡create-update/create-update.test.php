<?php

use Livewire\Livewire;

it('registers the cms.attribute.group.create-update component', function () {
    expect(Livewire::exists('cms.attribute.group.create-update'))->toBeTrue();
});
