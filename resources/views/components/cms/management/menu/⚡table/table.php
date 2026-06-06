<?php

use App\Actions\Cms\Management\Menu\DeleteMenuAction;
use App\Livewire\BaseComponent;
use App\Models\Menu\Menu;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;

new class extends BaseComponent
{
    // Model instance
    public $modelInstance = Menu::class;

    // Pagination and Search
    public $searchBy = [
        [
            'name' => 'Role',
            'field' => 'roles.name',
        ],
        [
            'name' => 'Menu',
            'field' => 'menus.name',
        ],
        [
            'name' => 'URL',
            'field' => 'menus.url',
        ],
        [
            'name' => 'Icon',
            'field' => 'menus.icon',
        ],
        [
            'name' => 'Order',
            'field' => 'menus.order',
        ],
        [
            'name' => 'Active Pattern',
            'field' => 'menus.active_pattern',
        ],
        [
            'name' => 'Status',
            'field' => 'menus.status',
        ],
    ];

    public function mount()
    {
        Gate::authorize('view'.$this->modelInstance);

        // Set default order by
        $this->paginationOrderBy = 'menus.order';
    }

    public function render()
    {
        if ($this->search != '') {
            $this->resetPage();
        }

        // Query data with filters
        $model = Menu::query()
            ->join('roles', 'menus.role_id', '=', 'roles.id')
            ->select(
                'menus.*',
                'roles.name as role_name',
            );

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
    public function delete($id, DeleteMenuAction $deleteAction)
    {
        Gate::authorize('delete'.$this->modelInstance);

        $deleteAction->handle(
            menu: Menu::findOrFail($id),
        );

        // Flush cache
        Cache::flush();

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: 'Menu deleted successfully',
        );
    }
};
