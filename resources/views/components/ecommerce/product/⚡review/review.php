<?php

use Livewire\Component;
use App\Models\Product\Product;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public Product $product;
    public $filter = 'all'; // all, with_media

    public function setFilter($filter)
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    #[Computed]
    public function ratingDistribution()
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
                $distribution[(int)$rating->star] = $rating->count;
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
            'total' => $total
        ];
    }

    #[Computed]
    public function reviews()
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
