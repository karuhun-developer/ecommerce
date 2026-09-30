<?php

use App\Actions\Cms\Content\DeleteHeaderLinkAction;
use App\Livewire\BaseComponent;
use App\Models\Content\HeaderLink;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

new class extends BaseComponent
{
    #[Locked]
    public string $modelInstance = HeaderLink::class;

    #[Locked]
    public array $searchBy = [
        ['name' => 'Label', 'field' => 'label'],
        ['name' => 'Posisi', 'field' => 'position'],
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
            model: HeaderLink::query()->with('page'),
            searchBy: $this->searchBy,
            orderBy: $this->paginationOrderBy,
            order: $this->paginationOrder,
            paginate: $this->paginate,
            s: $this->search,
        );

        return $this->view(['data' => $data]);
    }

    #[On('delete')]
    public function delete(int $id, DeleteHeaderLinkAction $action): void
    {
        Gate::authorize('manageWebsiteContent');
        $action->handle(HeaderLink::query()->findOrFail($id), auth()->user());
        $this->resetPage();
        $this->dispatch('toast', type: 'success', message: 'Tautan header dihapus.');
    }
};
