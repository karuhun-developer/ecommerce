<?php

use Livewire\Livewire;

it('registers the setting.password component', function () {
    expect(Livewire::exists('setting.password'))->toBeTrue();
});
