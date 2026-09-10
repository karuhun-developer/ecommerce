<?php

use App\Models\Product\ProductCategory;
use App\Models\Shop\Shop;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\User;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    $permissions = collect([
        'view'.ProductCategory::class,
        'create'.ProductCategory::class,
        'update'.ProductCategory::class,
        'delete'.ProductCategory::class,
    ])->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'api'));

    Role::findOrCreate('superadmin', 'api')->givePermissionTo($permissions);
});

it('registers the cms.product.category.table component', function () {
    expect(Livewire::exists('cms.product.category.table'))->toBeTrue();
});

it('lists and deletes categories for an authorized user', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');
    $category = ProductCategory::query()->create(['name' => 'Category To Delete']);

    Livewire::actingAs($user)
        ->test('cms.product.category.table')
        ->assertSee('Category To Delete')
        ->call('delete', $category->id)
        ->assertDispatched('toast', type: 'success', message: 'Category deleted successfully');

    expect($category->fresh())->toBeNull();
});

it('locks the category model identifier', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    expect(fn () => Livewire::actingAs($user)
        ->test('cms.product.category.table')
        ->set('modelInstance', Shop::class))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});
