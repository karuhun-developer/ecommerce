<?php

use App\Models\Order\Order;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url]
    public $status = 'semua'; // semua, berlangsung, berhasil, tidak-berhasil

    public function setStatus($status)
    {
        $this->status = $status;
    }

    #[Computed]
    public function orders()
    {
        $query = Order::where('user_id', auth()->id())
            ->with(['orderShops.shop', 'orderShops.items', 'latestPayment'])
            ->latest();

        if ($this->status === 'berlangsung') {
            $query->where('status', false)
                  ->whereHas('latestPayment', function ($q) {
                      $q->whereNull('expired_at')
                        ->orWhere('expired_at', '>', now());
                  });
        } elseif ($this->status === 'berhasil') {
            $query->where('status', true);
        } elseif ($this->status === 'tidak-berhasil') {
            $query->where('status', false)
                  ->whereHas('latestPayment', function ($q) {
                      $q->where('expired_at', '<=', now());
                  });
        }

        return $query->get();
    }
};
