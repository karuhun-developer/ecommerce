<?php

namespace Database\Seeders;

use App\Models\Menu\Menu;
use App\Models\Spatie\Role;
use Illuminate\Database\Seeder;

class ShopownerMenuSeeder extends Seeder
{
    public $role;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->role = Role::where('name', 'shopowner')->first();
        if (! $this->role) {
            return;
        }

        Menu::where('role_id', $this->role->id)->delete();

        // Create menu
        $this->dashboardMenu();
        $this->shopMenu();
        $this->productMenu();
        $this->orderMenu();
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
            'name' => 'My Shop',
            'url' => 'cms.shop',
            'icon' => 'building-storefront',
            'order' => 100,
            'active_pattern' => 'cms.shop',
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

    public function orderMenu()
    {
        Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Orders',
            'url' => 'cms.order.index',
            'icon' => 'shopping-bag',
            'order' => 250,
            'active_pattern' => 'cms.order.index,cms.order.show',
            'status' => 1,
        ]);
    }
}
