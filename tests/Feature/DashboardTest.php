<?php

use App\Models\Menu\Menu;
use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get('/cms/dashboard')->assertRedirect('/login');
});

test('shop owners can visit the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::create(['name' => 'shopowner', 'guard_name' => 'api']));
    $this->actingAs($user);

    $this->get('/cms/dashboard')->assertSuccessful();
});

test('ordinary users cannot view merchant analytics', function () {
    $this->actingAs(User::factory()->create())->get('/cms/dashboard')->assertForbidden();
});

test('dashboard navigation appears once for duplicate menus and multiple roles', function () {
    $role = Role::create(['name' => 'superadmin', 'guard_name' => 'api']);
    $owner = Role::create(['name' => 'shopowner', 'guard_name' => 'api']);
    $user = User::factory()->create();
    $user->assignRole([$role, $owner]);
    foreach ([$role, $role, $owner] as $index => $menuRole) {
        Menu::create(['role_id' => $menuRole->id, 'name' => 'Dashboard', 'url' => 'cms.dashboard', 'active_pattern' => 'cms.dashboard', 'order' => $index, 'status' => 1]);
    }
    $this->actingAs($user);

    expect(getMenus()->where('url', 'cms.dashboard'))->toHaveCount(1)
        ->and(Menu::where('url', 'cms.dashboard')->count())->toBe(3);
    $response = $this->get('/cms/dashboard')->assertSuccessful();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $links = (new DOMXPath($document))->query('//a[@data-flux-sidebar-item and @href="'.route('cms.dashboard').'"]');
    expect($links->length)->toBe(1);
});

test('cached duplicate links collapse without removing separate submenu groups', function () {
    $role = Role::create(['name' => 'superadmin', 'guard_name' => 'api']);
    $user = User::factory()->create();
    $user->assignRole($role);
    $attributes = ['role_id' => $role->id, 'active_pattern' => 'cms.dashboard', 'order' => 1, 'status' => 1];
    foreach (['Dashboard', 'Duplicate'] as $name) {
        Menu::create([...$attributes, 'name' => $name, 'url' => 'cms.dashboard']);
    }
    foreach (['Content', 'Catalog'] as $name) {
        $group = Menu::create([...$attributes, 'name' => $name, 'url' => '#']);
        $group->subMenu()->create([...$attributes, 'name' => $name.' submenu', 'url' => 'cms.dashboard']);
    }
    $this->actingAs($user);
    Cache::put('menu:'.$role->id, Menu::with('subMenu')->get(), now()->addDay());

    $menus = getMenus();
    expect($menus->where('url', 'cms.dashboard'))->toHaveCount(1)
        ->and($menus->where('url', '#')->pluck('name')->all())->toBe(['Content', 'Catalog'])
        ->and($menus->where('url', '#')->sum(fn (Menu $menu): int => $menu->subMenu->count()))->toBe(2)
        ->and($menus->keys()->all())->toBe([0, 1, 2]);
});
