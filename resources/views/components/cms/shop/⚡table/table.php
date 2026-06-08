<?php

use App\Actions\Cms\Shop\DeleteShopAction;
use App\Livewire\BaseComponent;
use App\Models\Shop\Shop;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;

new class extends BaseComponent
{
    public $modelInstance = Shop::class;

    public $searchBy = [
        [
            'name' => 'Name',
            'field' => 'name',
        ],
        [
            'name' => 'Description',
            'field' => 'description',
        ],
    ];

    public function mount()
    {
        $this->paginationOrderBy = 'name';
    }

    public function render()
    {
        if ($this->search != '') {
            $this->resetPage();
        }

        $data = $this->getDataWithFilter(
            model: new Shop,
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
    public function delete($id, DeleteShopAction $deleteAction)
    {
        $deleteAction->handle(Shop::findOrFail($id));

        $this->dispatch('toast', type: 'success', message: 'Shop and its location deleted successfully.');
    }
};
