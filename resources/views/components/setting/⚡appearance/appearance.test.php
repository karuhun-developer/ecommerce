<?php

use Livewire\Livewire;

it('registers the setting.appearance component', function () {
    expect(Livewire::exists('setting.appearance'))->toBeTrue();
});
