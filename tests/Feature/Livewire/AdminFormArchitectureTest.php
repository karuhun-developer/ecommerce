<?php

use App\Actions\Cms\Management\RolePermission\AssignAllRolePermissionsAction;
use App\Actions\Cms\Management\RolePermission\AssignRolePermissionAction;
use App\Actions\Cms\Management\User\StoreUserAction;
use App\Data\Cms\UserData;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

it('creates and edits users through a validated form DTO', function () {
    $role = Role::create(['name' => 'superadmin', 'guard_name' => 'api']);
    $actor = User::factory()->create();
    $actor->assignRole($role);
    Gate::before(fn () => true);

    Livewire::actingAs($actor)->test('cms.management.user.create-update')
        ->set('form.role', 'superadmin')->set('form.name', 'Admin Baru')
        ->set('form.email', 'new@example.test')->set('form.password', 'safe-password')
        ->call('submit')->assertHasNoErrors();

    $user = User::where('email', 'new@example.test')->firstOrFail();
    expect($user->hasRole('superadmin'))->toBeTrue();

    Livewire::actingAs($actor)->test('cms.management.user.create-update')
        ->call('setAction', $user->id)->set('form.name', 'Updated Admin')
        ->call('submit')->assertHasNoErrors();
    expect($user->fresh()->name)->toBe('Updated Admin');
});

it('blocks direct user action calls by ordinary users', function () {
    $actor = User::factory()->create();
    expect(fn () => app(StoreUserAction::class)->handle(new UserData('Other', 'other@example.test', password: 'safe-password'), $actor))
        ->toThrow(HttpException::class);
    expect(User::where('email', 'other@example.test')->exists())->toBeFalse();
});

it('grants only permissions from the role guard and updates toggles without a reload', function () {
    $actor = User::factory()->create();
    $role = Role::create(['name' => 'manager', 'guard_name' => 'api']);
    $permission = Permission::create(['name' => 'manage-website-content', 'guard_name' => 'api']);
    Permission::create(['name' => 'foreign-permission', 'guard_name' => 'web']);
    Gate::before(fn () => true);

    app(AssignAllRolePermissionsAction::class)->handle($role, $actor);
    expect($role->permissions()->pluck('name')->all())->toBe(['manage-website-content']);

    Livewire::actingAs($actor)->test('cms.management.role.permission', ['role' => $role])
        ->assertSee('manage-website-content')->assertDontSee('foreign-permission')
        ->assertSee('data-flux-checkbox', false)
        ->assertSet('assignedPermissions', [$permission->id])
        ->call('toggle', $permission->id)->assertHasNoErrors()
        ->assertSet('assignedPermissions', [])
        ->call('checkAll')->assertHasNoErrors()
        ->assertSet('assignedPermissions', [$permission->id])
        ->call('uncheckAll')->assertHasNoErrors()
        ->assertSet('assignedPermissions', [])
        ->call('toggle', $permission->id)->assertHasNoErrors()
        ->assertSet('assignedPermissions', [$permission->id])
        ->call('toggle', $permission->id)->assertHasNoErrors()
        ->assertSet('assignedPermissions', []);
    expect($role->fresh()->permissions)->toHaveCount(0);
});

it('rechecks permission authorization on every mutation', function () {
    $actor = User::factory()->create();
    $role = Role::create(['name' => 'manager', 'guard_name' => 'api']);
    $permission = Permission::create(['name' => 'manage-website-content', 'guard_name' => 'api']);
    Gate::define('update'.Role::class, fn () => false);
    expect(fn () => app(AssignRolePermissionAction::class)->handle($role, $permission, $actor))
        ->toThrow(AuthorizationException::class);
    expect($role->permissions)->toHaveCount(0);
});
