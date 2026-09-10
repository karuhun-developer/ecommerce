<?php

use App\Models\Product\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Locked]
    public Product $product;

    public string $filter = 'all';

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', 'with_media'], true) ? $filter : 'all';
        $this->resetPage();
    }

    #[Computed]
    public function ratingDistribution(): array
    {
        $distribution = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        ];

        $ratings = $this->product->reviews()->where('status', 'approved')
            ->selectRaw('FLOOR(rating) as star, COUNT(*) as count')
            ->groupBy('star')
            ->get();

        foreach ($ratings as $rating) {
            if ($rating->star >= 1 && $rating->star <= 5) {
                $distribution[(int) $rating->star] = $rating->count;
            }
        }

        $total = array_sum($distribution);

        $percentages = [];
        foreach ($distribution as $star => $count) {
            $percentages[$star] = $total > 0 ? ($count / $total) * 100 : 0;
        }

        return [
            'counts' => $distribution,
            'percentages' => $percentages,
            'total' => $total,
        ];
    }

    #[Computed]
    public function reviews(): LengthAwarePaginator
    {
        $query = $this->product->reviews()
            ->with(['user', 'media'])
            ->where('status', 'approved')
            ->latest();

        if ($this->filter === 'with_media') {
            $query->has('media');
        }

        return $query->paginate(5);
    }
};
