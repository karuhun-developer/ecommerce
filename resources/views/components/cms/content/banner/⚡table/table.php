<?php

use App\Actions\Cms\Content\DeleteBannerAction;
use App\Livewire\BaseComponent;
use App\Models\Content\Banner;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

new class extends BaseComponent
{
    #[Locked]
    public string $modelInstance = Banner::class;

    /** @var array<int, array{name: string, field: string, no_search?: bool}> */
    #[Locked]
    public array $searchBy = [
        ['name' => 'Judul', 'field' => 'title'],
        ['name' => 'Status', 'field' => 'active', 'no_search' => true],
        ['name' => 'Urutan', 'field' => 'sort_order', 'no_search' => true],
        ['name' => 'Mulai tampil', 'field' => 'starts_at', 'no_search' => true],
        ['name' => 'Selesai tampil', 'field' => 'ends_at', 'no_search' => true],
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
            model: Banner::query()->with('media'),
            searchBy: $this->searchBy,
            orderBy: $this->paginationOrderBy,
            order: $this->paginationOrder,
            paginate: $this->paginate,
            s: $this->search,
        );

        return $this->view(['data' => $data]);
    }

    #[On('delete')]
    public function delete(int $id, DeleteBannerAction $deleteAction): void
    {
        Gate::authorize('manageWebsiteContent');

        $deleteAction->handle(Banner::query()->findOrFail($id), auth()->user());
        $this->resetPage();
        $this->dispatch('toast', type: 'success', message: 'Banner dihapus.');
    }
};
