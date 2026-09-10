<?php

use Livewire\Livewire;

it('registers the cms.dashboard.analytics component', function () {
    expect(Livewire::exists('cms.dashboard.analytics'))->toBeTrue();
});
