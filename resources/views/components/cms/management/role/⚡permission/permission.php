<?php

use App\Actions\Cms\Management\RolePermission\UpdateRolePermissionsAction;
use App\Livewire\BaseComponent;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use Illuminate\Support\Facades\Gate;

new class extends BaseComponent
{
    // Model instance
    public $modelInstance = Permission::class;

    // List permissions
    public $permissions = [];

    // Role instance
    public Role $role;

    public function mount()
    {
        Gate::authorize('view'.$this->modelInstance);

        // Get role permissions
        $this->getPermissions();
    }

    protected function getPermissions()
    {
        $permission = Permission::all();

        // Get all permission that avaliable
        foreach ($permission as $perm) {
            $perm = explode('App\\', $perm->name);
            $model = 'App\\'.$perm[1];
            $permssion = $perm[0];

            $this->permissions[$model][$permssion] = false;
        }

        // Check if role has permissions
        foreach ($this->role->permissions->pluck('name') as $permission) {
            $perm = explode('App\\', $permission);
            $model = 'App\\'.$perm[1];
            $permssion = $perm[0];

            $this->permissions[$model][$permssion] = true;
        }
    }

    // Check all
    public function checkAll(UpdateRolePermissionsAction $action)
    {
        $action->assignAll($this->role);

        // Alert success message
        $this->dispatch('toast', type: 'success', message: 'All permissions have been granted.');
    }

    // Uncheck all
    public function uncheckAll(UpdateRolePermissionsAction $action)
    {
        $action->revokeAll($this->role);

        // Alert success message
        $this->dispatch('toast', type: 'success', message: 'All permissions have been revoked.');
    }

    // Check
    public function check($action, $model)
    {
        $permission = $action.$model;
        $this->isPermissionExist($permission);
        $this->role->givePermissionTo($permission);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->role)
            ->withProperties([
                'permission' => $permission,
            ])
            ->event('check-permission')
            ->log('Add permission');

        // Alert success message
        $this->dispatch('toast', type: 'success', message: 'Permission has been granted.');
    }

    // Uncheck
    public function uncheck($action, $model)
    {
        $permission = $action.$model;
        $this->isPermissionExist($permission);
        $this->role->revokePermissionTo($permission);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->role)
            ->withProperties([
                'permission' => $permission,
            ])
            ->event('uncheck-permission')
            ->log('Remove permission');

        // Alert success message
        $this->dispatch('toast', type: 'success', message: 'Permission has been revoked.');
    }

    // Is Permission Exist
    public function isPermissionExist($permission)
    {
        $isPermissionExist = Permission::where('name', $permission)->first();
        if (is_null($isPermissionExist)) {
            return false;
        }
    }
};
