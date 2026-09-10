<?php

use Livewire\Livewire;

it('registers the setting.delete-user-form component', function () {
    expect(Livewire::exists('setting.delete-user-form'))->toBeTrue();
});
