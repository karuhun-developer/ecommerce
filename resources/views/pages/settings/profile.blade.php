<?php

use function Laravel\Folio\name;

name('profile.edit');

?>

<x-layouts.app title="Edit Profile">
    <x-setting.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <livewire:setting.profile />
    </x-setting.layout>
</x-layouts.app>
