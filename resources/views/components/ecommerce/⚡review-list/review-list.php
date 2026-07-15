<?php

use App\Models\Order\OrderReview;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Computed]
    public function reviews()
    {
        return OrderReview::with(['reviewable', 'media'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);
    }
};
