<?php

use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $status = 'semua';

    public function mount(): void
    {
        abort_unless(auth()->user() instanceof User, 403);
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        $query = Order::where('user_id', auth()->id())
            ->with(['orderShops.shop', 'orderShops.items', 'latestPayment'])
            ->latest();

        if ($this->status === 'menunggu-pembayaran') {
            $query->where('status', false)
                ->whereHas('latestPayment', function ($q) {
                    $q->whereNull('expired_at')
                        ->orWhere('expired_at', '>', now());
                });
        } elseif ($this->status === 'proses') {
            $query->where('status', true)
                ->whereHas('orderShops', function ($q) {
                    $q->whereNull('waybill_number')->where('shipping_status', false);
                });
        } elseif ($this->status === 'dikirim') {
            $query->where('status', true)
                ->whereHas('orderShops', function ($q) {
                    $q->whereNotNull('waybill_number')->where('shipping_status', false);
                });
        } elseif ($this->status === 'sampai') {
            $query->where('status', true)
                ->whereHas('orderShops', function ($q) {
                    $q->whereNotNull('waybill_number')->where('shipping_status', true);
                });
        } elseif ($this->status === 'gagal') {
            $query->where('status', false)
                ->whereHas('latestPayment', function ($q) {
                    $q->where('expired_at', '<=', now());
                });
        }

        return $query->paginate(10);
    }
};
