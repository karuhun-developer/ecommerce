<?php

use App\Actions\Cms\Content\DeleteFooterGroupAction;
use App\Livewire\BaseComponent;
use App\Models\Content\FooterGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

new class extends BaseComponent
{
    #[Locked]
    public string $modelInstance = FooterGroup::class;

    #[Locked]
    public array $searchBy = [
        ['name' => 'Nama', 'field' => 'name'],
        ['name' => 'Status', 'field' => 'active', 'no_search' => true],
        ['name' => 'Urutan', 'field' => 'sort_order', 'no_search' => true],
    ];

    public function mount(): void
    {
        Gate::authorize('manageWebsiteContent');
        $this->paginationOrderBy = 'sort_order';
        $this->paginationOrder = 'asc';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPaginate(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('manageWebsiteContent');
        $data = $this->getDataWithFilter(
            model: FooterGroup::query()->withCount('pages'),
            searchBy: $this->searchBy,
            orderBy: $this->paginationOrderBy,
            order: $this->paginationOrder,
            paginate: $this->paginate,
            s: $this->search,
        );

        return $this->view(['data' => $data]);
    }

    #[On('delete')]
    public function delete(int $id, DeleteFooterGroupAction $action): void
    {
        Gate::authorize('manageWebsiteContent');
        $action->handle(FooterGroup::query()->findOrFail($id), auth()->user());
        $this->resetPage();
        $this->dispatch('toast', type: 'success', message: 'Grup footer dihapus.');
    }
};
