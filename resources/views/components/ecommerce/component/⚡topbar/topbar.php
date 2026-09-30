<?php

use App\Models\Content\HeaderLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function links(): Collection
    {
        return HeaderLink::query()->where('active', true)
            ->where(function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('destination', 'page')->whereHas('page', fn (Builder $query): Builder => $query->where('published', true));
                })->orWhere(function (Builder $query): void {
                    $query->where('destination', 'url')->whereNotNull('url');
                });
            })->with('page')->orderBy('sort_order')->orderBy('id')->get();
    }
};
