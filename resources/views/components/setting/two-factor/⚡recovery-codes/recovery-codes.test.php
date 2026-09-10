<?php

use Livewire\Livewire;

it('registers the setting.two-factor.recovery-codes component', function () {
    expect(Livewire::exists('setting.two-factor.recovery-codes'))->toBeTrue();
});
