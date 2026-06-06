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

    public $paginationOrderBy = 'id';

    public $paginationOrder = 'desc';

    public $paginate = 10;

    public $search = '';

    #[On('reset-parent-page')]
    public function resetParentPage()
    {
        $this->resetPage();
    }
}
