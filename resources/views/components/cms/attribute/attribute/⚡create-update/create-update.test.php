<?php

use Livewire\Livewire;

it('registers the cms.attribute.attribute.create-update component', function () {
    expect(Livewire::exists('cms.attribute.attribute.create-update'))->toBeTrue();
});
