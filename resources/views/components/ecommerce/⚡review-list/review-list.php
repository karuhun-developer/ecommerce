<?php

use App\Models\Order\OrderReview;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_unless(auth()->user() instanceof User, 403);
    }

    #[Computed]
    public function reviews(): LengthAwarePaginator
    {
        return OrderReview::with(['reviewable', 'media'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);
    }
};
