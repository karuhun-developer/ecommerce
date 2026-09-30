<?php

namespace Database\Seeders;

use App\Models\Menu\Menu;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

class WebsiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::findOrCreate('manageWebsiteContent', 'api');
        $role = Role::query()->where('name', 'superadmin')->where('guard_name', 'api')->first();
        if ($role) {
            $role->givePermissionTo($permission);
            $menu = Menu::query()->firstOrCreate(['role_id' => $role->id, 'active_pattern' => 'cms.content'], ['name' => 'Konten Website', 'url' => '#', 'icon' => 'document-text', 'order' => 400, 'status' => 1]);
            $items = [
                'identity' => 'Identitas Website',
                'banners' => 'Banner Homepage',
                'header-links' => 'Menu Header',
                'footer-groups' => 'Grup Footer',
                'pages' => 'Menu Footer',
            ];

            foreach ($items as $path => $name) {
                $submenu = $menu->subMenu()->firstOrCreate(
                    ['role_id' => $role->id, 'url' => 'cms.content.'.$path],
                    ['name' => $name, 'order' => 0, 'active_pattern' => 'cms.content.'.$path, 'status' => 1],
                );
                $submenu->update(['name' => $name, 'order' => array_search($path, array_keys($items)) + 1]);
            }
        }
        if ($role) {
            Cache::forget('menu:'.$role->id);
            foreach ($role->users()->with('roles')->get() as $user) {
                Cache::forget('menu:'.$user->roles->pluck('id')->implode(','));
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->call([
            FooterGroupSeeder::class,
            PageSeeder::class,
            HeaderLinkSeeder::class,
        ]);
    }
}
