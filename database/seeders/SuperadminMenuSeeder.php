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
        $this->shopMenu();
        $this->orderMenu();
        $this->productMenu();
        $this->attributeMenu();
        $this->reviewMenu();
        $this->managementMenu();
    }

    public function reviewMenu()
    {
        Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Reviews',
            'url' => 'cms.review.index',
            'icon' => 'star',
            'order' => 195,
            'active_pattern' => 'cms.review.index',
            'status' => 1,
        ]);
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

    public function shopMenu()
    {
        Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Shops',
            'url' => 'cms.shop',
            'icon' => 'building-storefront',
            'order' => 100,
            'active_pattern' => 'cms.shop',
            'status' => 1,
        ]);
    }

    public function orderMenu()
    {
        Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Orders',
            'url' => 'cms.order.index',
            'icon' => 'shopping-bag',
            'order' => 190,
            'active_pattern' => 'cms.order.index,cms.order.show',
            'status' => 1,
        ]);
    }

    public function productMenu()
    {
        $product = Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Products',
            'url' => '#',
            'icon' => 'inbox-stack',
            'order' => 200,
            'active_pattern' => 'cms.product',
            'status' => 1,
        ]);
        $product->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'Product Category',
            'url' => 'cms.product.category',
            'order' => 1,
            'active_pattern' => 'cms.product.category',
            'status' => 1,
        ]);
        $product->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'Product',
            'url' => 'cms.product.index',
            'order' => 2,
            'active_pattern' => 'cms.product.index,cms.product.edit',
            'status' => 1,
        ]);
    }

    public function attributeMenu()
    {
        $attribute = Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Attributes',
            'url' => '#',
            'icon' => 'tag',
            'order' => 300,
            'active_pattern' => 'cms.attribute',
            'status' => 1,
        ]);
        $attribute->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'Attribute Group',
            'url' => 'cms.attribute.group',
            'order' => 1,
            'active_pattern' => 'cms.attribute.group',
            'status' => 1,
        ]);
        $attribute->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'Attribute',
            'url' => 'cms.attribute.attribute',
            'order' => 2,
            'active_pattern' => 'cms.attribute.attribute',
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
