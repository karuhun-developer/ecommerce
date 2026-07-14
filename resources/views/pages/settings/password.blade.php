<?php

use function Laravel\Folio\name;

name('user-password.edit');

?>

<x-layouts.app title="Change Password">
    <x-setting.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <livewire:setting.password />
    </x-setting.layout>
</x-layouts.app>
