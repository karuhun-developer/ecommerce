<?php

use App\Actions\Cms\Management\User\StoreUserAction;
use App\Actions\Cms\Management\User\UpdateUserAction;
use App\Models\Spatie\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // Model instance
    public $modelInstance = User::class;

    public $isUpdate = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);
    }

    #[On('set-action')]
    public function setAction($id = null)
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

    #[Computed]
    public function roles()
    {
        return Role::all();
    }

    // Record data
    #[Locked]
    public $id;

    public $role;

    public $name;

    public $email;

    public $password;

    // Get record data
    public function getRecordData($id)
    {
        Gate::authorize('show'.$this->modelInstance);

        $record = User::findOrFail($id);
        $this->fill(
            $record->only(
                'id',
                'name',
                'email',
            )
        );
        $this->role = $record->getRoleNames()->first();
        $this->reset('password');
    }

    // Reset record dataw
    public function resetRecordData()
    {
        $this->reset([
            'id',
            'role',
            'name',
            'email',
            'password',
        ]);
    }

    // Handle form submit
    public function submit(StoreUserAction $storeAction, UpdateUserAction $updateAction)
    {
        Gate::authorize(($this->isUpdate ? 'update' : 'create').$this->modelInstance);

        $this->validate([
            'role' => 'required|string|exists:roles,name',
            'name' => 'required|string|max:255',
            'email' => $this->isUpdate ? 'required|string|email|max:255|unique:users,email,'.$this->id : 'required|string|email|max:255|unique:users,email',
            'password' => $this->isUpdate ? 'nullable' : 'required|string|min:8',
        ]);

        if ($this->isUpdate) {
            $updateAction->handle(
                user: User::findOrFail($this->id),
                data: [
                    'role' => $this->role,
                    'name' => $this->name,
                    'email' => $this->email,
                ],
            );
        } else {
            $storeAction->handle(
                data: [
                    'role' => $this->role,
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => $this->password,
                ],
            );
        }

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: $this->isUpdate ? 'User updated successfully.' : 'User created successfully.',
        );

        // Reset data table
        $this->dispatch('reset-parent-page');

        // Close modal
        Flux::modal('defaultModal')->close();
    }
};
