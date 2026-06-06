<?php

use App\Actions\Cms\Management\User\DeleteUserAction;
use App\Actions\Cms\Management\User\ValidateUserEmailAction;
use App\Livewire\BaseComponent;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;

new class extends BaseComponent
{
    // Model instance
    public $modelInstance = User::class;

    // Pagination and Search
    public $searchBy = [
        [
            'name' => 'Role',
            'field' => 'roles.name',
        ],
        [
            'name' => 'Name',
            'field' => 'users.name',
        ],
        [
            'name' => 'Email',
            'field' => 'users.email',
        ],
        [
            'name' => 'Email Verified',
            'field' => 'users.email_verified_at',
        ],
        [
            'name' => 'Created At',
            'field' => 'users.created_at',
        ],
    ];

    public function mount()
    {
        Gate::authorize('view'.$this->modelInstance);

        // Set default order by
        $this->paginationOrderBy = 'users.created_at';
    }

    public function render()
    {
        if ($this->search != '') {
            $this->resetPage();
        }

        // Query data with filters
        $model = User::query()
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select('users.*', 'roles.name as role');

        $data = $this->getDataWithFilter(
            model: $model,
            searchBy: $this->searchBy,
            orderBy: $this->paginationOrderBy,
            order: $this->paginationOrder,
            paginate: $this->paginate,
            s: $this->search,
        );

        return $this->view([
            'data' => $data,
        ]);
    }

    #[On('delete')]
    public function delete($id, DeleteUserAction $deleteAction)
    {
        Gate::authorize('delete'.$this->modelInstance);

        $deleteAction->handle(
            user: User::findOrFail($id),
        );

        // Toast message
        $this->dispatch('toast', type: 'success', message: 'User deleted successfully.');
    }

    #[On('verifyEmail')]
    public function verifyEmail($id, ValidateUserEmailAction $validateEmailAction)
    {
        Gate::authorize('validate'.$this->modelInstance);

        $validateEmailAction->handle(
            user: User::findOrFail($id),
        );

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: 'Email verified successfully.',
        );
    }
};
