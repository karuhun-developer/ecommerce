<?php

use App\Models\Spatie\Role;
use App\Models\User;
use Livewire\Livewire;

it('registers the cms.management.user.create-update component', function () {
    expect(Livewire::exists('cms.management.user.create-update'))->toBeTrue();
});

it('forbids non superadmins from managing users', function () {
    $role = Role::query()->create([
        'name' => 'shopowner',
        'guard_name' => 'api',
    ]);
    $user = User::factory()->create();
    $user->assignRole($role);

    Livewire::actingAs($user)
        ->test('cms.management.user.create-update')
        ->assertForbidden();
});
