<?php

namespace Database\Seeders;

use App\Models\Menu\Menu;
use App\Models\Spatie\Role;
use Illuminate\Database\Seeder;

class SuperadminMenuSeeder extends Seeder
{
    public $role;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->role = Role::where('name', 'superadmin')->first();
        Menu::where('role_id', $this->role->id)->delete();

        // Create menu
        $this->dashboardMenu();
        $this->managementMenu();
    }

    public function dashboardMenu()
    {
        Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Dashboard',
            'url' => 'cms.dashboard',
            'icon' => 'map',
            'order' => 1,
            'active_pattern' => 'cms.dashboard',
            'status' => 1,
        ]);
    }

    public function managementMenu()
    {
        $management = Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Managements',
            'url' => '#',
            'icon' => 'cog',
            'order' => 999,
            'active_pattern' => 'cms.management',
            'status' => 1,
        ]);
        $management->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'Permission',
            'url' => 'cms.management.permission',
            'order' => 1,
            'active_pattern' => 'cms.management.permission',
            'status' => 1,
        ]);
        $management->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'Role',
            'url' => 'cms.management.role',
            'order' => 2,
            'active_pattern' => 'cms.management.role',
            'status' => 1,
        ]);
        $management->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'Menu',
            'url' => 'cms.management.menu',
            'order' => 3,
            'active_pattern' => 'cms.management.menu',
            'status' => 1,
        ]);
        $management->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'User',
            'url' => 'cms.management.user',
            'order' => 4,
            'active_pattern' => 'cms.management.user',
            'status' => 1,
        ]);
    }
}
