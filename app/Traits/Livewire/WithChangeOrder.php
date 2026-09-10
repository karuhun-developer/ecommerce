<?php

namespace App\Traits\Livewire;

trait WithChangeOrder
{
    public function changeOrder(string $paginationOrderBy): void
    {
        $sortableFields = collect($this->searchBy ?? [])
            ->pluck('field')
            ->filter(fn (mixed $field): bool => is_string($field) && $field !== '')
            ->values()
            ->all();

        abort_unless(in_array($paginationOrderBy, $sortableFields, true), 422);

        if ($this->paginationOrderBy === $paginationOrderBy) {
            $this->paginationOrder = $this->paginationOrder === 'desc' ? 'asc' : 'desc';
        }

        $this->paginationOrderBy = $paginationOrderBy;
    }
}
