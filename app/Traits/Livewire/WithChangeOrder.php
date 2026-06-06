<?php

namespace App\Traits\Livewire;

trait WithChangeOrder
{
    public function changeOrder($paginationOrderBy)
    {
        if ($this->paginationOrderBy == $paginationOrderBy) {
            $this->paginationOrder = $this->paginationOrder == 'desc' ? 'asc' : 'desc';
        }

        $this->paginationOrderBy = $paginationOrderBy;
    }
}
