<?php

use App\Models\Content\Banner;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function banners(): Collection
    {
        return Banner::query()->visible()->with('media')->orderBy('sort_order')->orderBy('id')->get();
    }
};
