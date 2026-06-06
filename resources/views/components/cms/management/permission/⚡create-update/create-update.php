<?php

use App\Actions\Cms\Management\Permission\StorePermissionAction;
use App\Actions\Cms\Management\Permission\UpdatePermissionAction;
use App\Models\Spatie\Permission;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // Model instance
    public $modelInstance = Permission::class;

    public $isUpdate = false;

    #[On('set-action')]
    public function setAction($id = null)
    {
        if ($id) {
            $this->isUpdate = true;
            $this->getRecordData($id);
        } else {
            $this->isUpdate = false;
            $this->resetRecordData();
        }
    }

    // Record data
    public $id;

    public $name;

    public $guard_name;

    // Get record data
    public function getRecordData($id)
    {
        Gate::authorize('show'.$this->modelInstance);

        $record = Permission::find($id);
        $this->fill(
            $record->only(
                'id',
                'name',
                'guard_name',
            )
        );
    }

    // Reset record data
    public function resetRecordData()
    {
        $this->reset([
            'id',
            'name',
            'guard_name',
        ]);

        $this->guard_name = 'api';
    }

    // Handle form submit
    public function submit(StorePermissionAction $storeAction, UpdatePermissionAction $updateAction)
    {
        Gate::authorize(($this->isUpdate ? 'update' : 'create').$this->modelInstance);

        $this->validate([
            'name' => 'required|string|max:255',
            'guard_name' => 'required|string|max:255',
        ]);

        if ($this->isUpdate) {
            $updateAction->handle(
                permission: Permission::findOrFail($this->id),
                data: $this->all(),
            );
        } else {
            $storeAction->handle(
                data: $this->all(),
            );
        }

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: $this->isUpdate ? 'Permission updated successfully.' : 'Permission created successfully.',
        );

        // Reset data table
        $this->dispatch('reset-parent-page');

        // Close modal
        Flux::modal('defaultModal')->close();
    }
};
