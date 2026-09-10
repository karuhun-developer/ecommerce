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
        'show'.ProductCategory::class,
        'create'.ProductCategory::class,
        'update'.ProductCategory::class,
    ])->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'api'));

    Role::findOrCreate('superadmin', 'api')->givePermissionTo($permissions);
});

it('registers the cms.product.category.create-update component', function () {
    expect(Livewire::exists('cms.product.category.create-update'))->toBeTrue();
});

it('creates and updates a category with explicit category fields', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    Livewire::actingAs($user)
        ->test('cms.product.category.create-update')
        ->set('name', 'Initial Category')
        ->set('description', 'Initial description')
        ->set('is_featured', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success', message: 'Category created successfully.');

    $category = ProductCategory::query()->where('name', 'Initial Category')->sole();

    Livewire::actingAs($user)
        ->test('cms.product.category.create-update')
        ->call('setAction', $category->id)
        ->assertSet('isUpdate', true)
        ->set('name', 'Updated Category')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success', message: 'Category updated successfully.');

    expect($category->fresh()->name)->toBe('Updated Category');
});

it('locks the category model identifier', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    expect(fn () => Livewire::actingAs($user)
        ->test('cms.product.category.create-update')
        ->set('modelInstance', Shop::class))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});
