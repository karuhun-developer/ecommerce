<?php

use App\Actions\Cms\Management\RolePermission\AssignAllRolePermissionsAction;
use App\Actions\Cms\Management\RolePermission\AssignRolePermissionAction;
use App\Actions\Cms\Management\RolePermission\RevokeAllRolePermissionsAction;
use App\Actions\Cms\Management\RolePermission\RevokeRolePermissionAction;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Role $role;

    public function mount(): void
    {
        Gate::authorize('view'.Permission::class);
    }

    #[Computed]
    public function permissions(): Collection
    {
        return Permission::query()->where('guard_name', $this->role->guard_name)->orderBy('name')->get();
    }

    #[Computed]
    public function assignedPermissions(): array
    {
        return $this->role->permissions()->pluck('id')->all();
    }

    public function checkAll(AssignAllRolePermissionsAction $action): void
    {
        $action->handle($this->role, auth()->user());
        unset($this->assignedPermissions);
        $this->dispatch('toast', type: 'success', message: 'All permissions have been granted.');
    }

    public function uncheckAll(RevokeAllRolePermissionsAction $action): void
    {
        $action->handle($this->role, auth()->user());
        unset($this->assignedPermissions);
        $this->dispatch('toast', type: 'success', message: 'All permissions have been revoked.');
    }

    public function toggle(int $permissionId, AssignRolePermissionAction $assign, RevokeRolePermissionAction $revoke): void
    {
        $permission = Permission::query()->where('guard_name', $this->role->guard_name)->findOrFail($permissionId);
        $action = $this->role->hasPermissionTo($permission) ? $revoke : $assign;
        $action->handle($this->role, $permission, auth()->user());
        $this->role->unsetRelation('permissions');
        unset($this->assignedPermissions);
        $this->dispatch('toast', type: 'success', message: 'Permission updated.');
    }
};
