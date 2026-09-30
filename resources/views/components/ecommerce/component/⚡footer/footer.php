<?php

use App\Models\Content\FooterGroup;
use App\Models\Setting\Setting;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function groups(): Collection
    {
        return FooterGroup::query()->where('active', true)
            ->with(['pages' => fn (HasMany $query): HasMany => $query->where('published', true)->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')->orderBy('id')->get();
    }

    #[Computed]
    public function identity(): array
    {
        return Setting::query()->where('key', 'storefront')->first()?->data ?? ['brand_name' => config('app.name')];
    }
};
