<?php

use App\Actions\Cms\Management\Permission\StorePermissionAction;
use App\Actions\Cms\Management\Permission\UpdatePermissionAction;
use App\Data\Cms\PermissionData;
use App\Models\Spatie\Permission;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // Model instance
    #[Locked]
    public string $modelInstance = Permission::class;

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

        $record = Permission::findOrFail($id);
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
    public function submit(StorePermissionAction $storeAction, UpdatePermissionAction $updateAction): void
    {
        Gate::authorize(($this->isUpdate ? 'update' : 'create').$this->modelInstance);

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'guard_name' => 'required|string|max:255',
        ]);

        if ($this->isUpdate) {
            $updateAction->handle(
                permission: Permission::findOrFail($this->id),
                data: PermissionData::fromArray($validated),
                actor: auth()->user(),
            );
        } else {
            $storeAction->handle(
                data: PermissionData::fromArray($validated),
                actor: auth()->user(),
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
