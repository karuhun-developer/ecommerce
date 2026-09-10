<?php

use Livewire\Livewire;

it('registers the cms.management.user.update-password component', function () {
    expect(Livewire::exists('cms.management.user.update-password'))->toBeTrue();
});
