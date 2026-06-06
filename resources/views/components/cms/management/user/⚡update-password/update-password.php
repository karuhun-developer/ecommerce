<?php

use App\Actions\Cms\Management\User\UpdateUserPasswordAction;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // Model instance
    public $modelInstance = User::class;

    public $isUpdate = false;

    #[On('set-update-password')]
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

    public $password;

    // Get record data
    public function getRecordData($id)
    {
        Gate::authorize('show'.$this->modelInstance);

        $record = User::find($id);
        $this->id = $record->id;
        $this->reset('password');
    }

    // Handle change password submit
    public function submit(UpdateUserPasswordAction $updatePasswordAction)
    {
        // Validation rules
        $this->validate([
            'password' => 'required|string|min:8',
        ]);

        // Find user and update password
        $updatePasswordAction->handle(
            user: User::findOrFail($this->id),
            password: $this->password,
        );

        // Toast message
        $this->dispatch('toast', type: 'success', message: 'Password changed successfully.');

        // Close modal
        Flux::modal('changePasswordModal')->close();
    }
};
