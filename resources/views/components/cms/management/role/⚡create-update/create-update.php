<?php

use App\Actions\Cms\Management\Role\StoreRoleAction;
use App\Actions\Cms\Management\Role\UpdateRoleAction;
use App\Models\Spatie\Role;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // Model instance
    public $modelInstance = Role::class;

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

        $record = Role::find($id);
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
    public function submit(StoreRoleAction $storeAction, UpdateRoleAction $updateAction)
    {
        Gate::authorize(($this->isUpdate ? 'update' : 'create').$this->modelInstance);

        $this->validate([
            'name' => 'required|string|max:255',
            'guard_name' => 'required|string|max:255',
        ]);

        if ($this->isUpdate) {
            $updateAction->handle(
                role: Role::findOrFail($this->id),
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
            message: $this->isUpdate ? 'Role updated successfully.' : 'Role created successfully.'
        );

        // Reset data table
        $this->dispatch('reset-parent-page');

        // Close modal
        Flux::modal('defaultModal')->close();
    }
};
