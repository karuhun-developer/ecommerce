<?php

namespace Database\Seeders;

use App\Models\Menu\Menu;
use App\Models\Spatie\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class IntegrationSettingsMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::query()->where('name', 'superadmin')->where('guard_name', 'api')->first();
        if ($role === null) {
            return;
        }

        $menu = Menu::query()->firstOrCreate(['role_id' => $role->id, 'url' => 'cms.payment'], [
            'name' => 'Pengaturan Integrasi',
            'icon' => 'cog',
            'order' => 360,
            'active_pattern' => 'cms.payment',
            'status' => 1,
        ]);
        $menu->update(['name' => 'Pengaturan Integrasi', 'icon' => 'cog']);

        Cache::forget('menu:'.$role->id);
        foreach ($role->users()->with('roles')->get() as $user) {
            Cache::forget('menu:'.$user->roles->pluck('id')->implode(','));
        }
    }
}
