<?php

use App\Actions\Cms\Management\Role\DeleteRoleAction;
use App\Livewire\BaseComponent;
use App\Models\Spatie\Role;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;

new class extends BaseComponent
{
    // Model instance
    public $modelInstance = Role::class;

    // Pagination and Search
    public $searchBy = [
        [
            'name' => 'Name',
            'field' => 'name',
        ],
        [
            'name' => 'Guard Name',
            'field' => 'guard_name',
        ],
    ];

    public function mount()
    {
        Gate::authorize('view'.$this->modelInstance);

        // Set default order by
        $this->paginationOrderBy = 'guard_name';
    }

    public function render()
    {
        if ($this->search != '') {
            $this->resetPage();
        }

        // Query data with filters
        $data = $this->getDataWithFilter(
            model: new Role,
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
    public function delete($id, DeleteRoleAction $deleteAction)
    {
        Gate::authorize('delete'.$this->modelInstance);

        $deleteAction->handle(
            role: Role::findOrFail($id),
        );

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: 'Role deleted successfully',
        );
    }
};
