<?php

use App\Actions\Cms\Product\Product\DeleteProductAction;
use App\Livewire\BaseComponent;
use App\Models\Product\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

new class extends BaseComponent
{
    #[Locked]
    public string $modelInstance = Product::class;

    // Pagination and Search
    public $searchBy = [
        [
            'name' => 'Name',
            'field' => 'name',
        ],
        [
            'name' => 'Type',
            'field' => 'type',
        ],
    ];

    public function mount(): void
    {
        Gate::authorize('view'.$this->modelInstance);

        // Set default order by
        $this->paginationOrderBy = 'name';
    }

    public function render(): View
    {
        if ($this->search != '') {
            $this->resetPage();
        }

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $data = $this->getDataWithFilter(
            model: Product::query()
                ->accessibleTo($user)
                ->with(['shop', 'category', 'mainProductFlat.media']),
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
    public function delete(int|string $id, DeleteProductAction $deleteAction): void
    {
        Gate::authorize('delete'.$this->modelInstance);

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $deleteAction->handle(
            product: Product::query()->accessibleTo($user)->findOrFail($id),
        );

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: 'Product deleted successfully.',
        );
    }
};
