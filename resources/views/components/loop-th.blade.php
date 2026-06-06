@props([
    'searchBy',
    'paginationOrderBy',
    'paginationOrder',
])
@foreach($searchBy as $th)
    @if(!isset($th['hide']))
        <flux:table.column
            sortable
            :sorted="$paginationOrderBy === $th['field']"
            :direction="$paginationOrder"
            wire:click="changeOrder('{{ $th['field'] }}')">
            {{ $th['name'] }}
        </flux:table.column>
    @endif
@endforeach
