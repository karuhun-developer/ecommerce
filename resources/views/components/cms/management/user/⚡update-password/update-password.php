<?php

use App\Actions\Cms\Management\User\UpdateUserPasswordAction;
use App\Data\Auth\PasswordData;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public ?int $id = null;

    public string $password = '';

    #[On('set-update-password')]
    public function setAction(?int $id = null): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);
        Gate::authorize('show'.User::class);
        $this->resetValidation();
        $this->password = '';
        $this->id = $id === null ? null : User::findOrFail($id)->id;
    }

    public function submit(UpdateUserPasswordAction $action): void
    {
        Gate::authorize('update'.User::class);
        $validated = $this->validate(['password' => ['required', 'string', 'min:8']]);
        $action->handle(User::findOrFail($this->id), new PasswordData($validated['password']), auth()->user());
        $this->password = '';
        $this->dispatch('toast', type: 'success', message: 'Password changed successfully.');
        Flux::modal('changePasswordModal')->close();
    }
};
