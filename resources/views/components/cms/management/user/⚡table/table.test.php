<?php

use App\Models\Spatie\Role;
use App\Models\User;
use Livewire\Livewire;

it('registers the cms.management.user.table component', function () {
    expect(Livewire::exists('cms.management.user.table'))->toBeTrue();
});

it('forbids non superadmins from listing users', function () {
    $role = Role::query()->create([
        'name' => 'shopowner',
        'guard_name' => 'api',
    ]);
    $user = User::factory()->create();
    $user->assignRole($role);

    Livewire::actingAs($user)
        ->test('cms.management.user.table')
        ->assertForbidden();
});
