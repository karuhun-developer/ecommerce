<?php

use Livewire\Livewire;

it('registers the setting.two-factor component', function () {
    expect(Livewire::exists('setting.two-factor'))->toBeTrue();
});
