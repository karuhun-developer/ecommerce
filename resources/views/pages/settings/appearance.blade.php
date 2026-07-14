<?php

use function Laravel\Folio\name;

name('appearance.edit');

?>

<x-layouts.app title="Appearance">
    <x-setting.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <livewire:setting.appearance />
    </x-setting.layout>
</x-layouts.app>
