<?php

namespace App\Livewire;

use App\Traits\Livewire\WithChangeOrder;
use App\Traits\WithGetFilterData;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

abstract class BaseComponent extends Component
{
    use WithChangeOrder, WithGetFilterData, WithPagination;

    public string $paginationOrderBy = 'id';

    public string $paginationOrder = 'desc';

    public int $paginate = 10;

    public string $search = '';

    #[On('reset-parent-page')]
    public function resetParentPage(): void
    {
        $this->resetPage();
    }
}
