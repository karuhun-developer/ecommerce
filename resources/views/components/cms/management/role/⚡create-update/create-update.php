<?php

use App\Actions\Cms\Management\Role\StoreRoleAction;
use App\Actions\Cms\Management\Role\UpdateRoleAction;
use App\Data\Cms\RoleData;
use App\Models\Spatie\Role;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // Model instance
    #[Locked]
    public string $modelInstance = Role::class;

    #[Locked]
    public bool $isUpdate = false;

    #[On('set-action')]
    public function setAction(?int $id = null): void
    {
        $this->resetValidation();

        if ($id) {
            $this->isUpdate = true;
            $this->getRecordData($id);
        } else {
            $this->isUpdate = false;
            $this->resetRecordData();
        }
    }

    // Record data
    #[Locked]
    public ?int $id = null;

    public $name;

    public $guard_name;

    // Get record data
    private function getRecordData(int $id): void
    {
        Gate::authorize('show'.$this->modelInstance);

        $record = Role::findOrFail($id);
        $this->fill(
            $record->only(
                'id',
                'name',
                'guard_name',
            )
        );
    }

    // Reset record data
    private function resetRecordData(): void
    {
        $this->reset([
            'id',
            'name',
            'guard_name',
        ]);

        $this->guard_name = 'api';
    }

    // Handle form submit
    public function submit(StoreRoleAction $storeAction, UpdateRoleAction $updateAction): void
    {
        Gate::authorize(($this->isUpdate ? 'update' : 'create').$this->modelInstance);

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'guard_name' => 'required|string|max:255',
        ]);

        if ($this->isUpdate) {
            $updateAction->handle(
                role: Role::findOrFail($this->id),
                data: RoleData::fromArray($validated),
                actor: auth()->user(),
            );
        } else {
            $storeAction->handle(
                data: RoleData::fromArray($validated),
                actor: auth()->user(),
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
