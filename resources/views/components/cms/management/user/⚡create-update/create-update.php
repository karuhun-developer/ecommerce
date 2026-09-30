<?php

use App\Actions\Cms\Management\User\StoreUserAction;
use App\Actions\Cms\Management\User\UpdateUserAction;
use App\Livewire\Forms\Cms\UserForm;
use App\Models\Spatie\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public UserForm $form;

    #[Locked]
    public ?int $id = null;

    #[Locked]
    public bool $isUpdate = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);
    }

    #[On('set-action')]
    public function setAction(?int $id = null): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);
        $this->resetValidation();
        $this->form->reset();
        $this->id = $id;
        $this->isUpdate = $id !== null;

        if ($id !== null) {
            Gate::authorize('show'.User::class);
            $this->form->setUser(User::findOrFail($id));
        }
    }

    #[Computed]
    public function roles(): Collection
    {
        return Role::query()->where('guard_name', 'api')->orderBy('name')->get();
    }

    public function submit(StoreUserAction $storeAction, UpdateUserAction $updateAction): void
    {
        Gate::authorize(($this->isUpdate ? 'update' : 'create').User::class);
        $data = $this->form->data($this->id);
        $actor = auth()->user();

        if ($this->isUpdate) {
            $updateAction->handle(User::findOrFail($this->id), $data, $actor);
        } else {
            $storeAction->handle($data, $actor);
        }

        $this->dispatch('toast', type: 'success', message: $this->isUpdate ? 'User updated successfully.' : 'User created successfully.');
        $this->dispatch('reset-parent-page');
        $this->form->password = '';
        Flux::modal('defaultModal')->close();
    }
};
