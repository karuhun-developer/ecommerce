<?php

use Livewire\Livewire;

it('registers the setting.profile component', function () {
    expect(Livewire::exists('setting.profile'))->toBeTrue();
});
