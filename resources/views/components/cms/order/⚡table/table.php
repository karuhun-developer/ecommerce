<?php

use App\Models\Order\OrderShop;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url]
    public $status = 'semua'; // semua, menunggu-pembayaran, proses, dikirim, sampai, gagal

    public function setStatus($status)
    {
        $this->status = $status;
    }

    #[Computed]
    public function orders()
    {
        $query = OrderShop::with(['order.latestPayment', 'order.user', 'shop', 'items'])
            ->latest();

        if (!isSingleShop() && auth()->user()->hasRole('shopowner')) {
            $query->whereHas('shop', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        if ($this->status === 'menunggu-pembayaran') {
            $query->whereHas('order', function ($q) {
                $q->where('status', false)
                  ->whereHas('latestPayment', function ($sq) {
                      $sq->whereNull('expired_at')
                        ->orWhere('expired_at', '>', now());
                  });
            });
        } elseif ($this->status === 'proses') {
            $query->whereHas('order', function ($q) {
                $q->where('status', true);
            })->where('shipping_status', false);
        } elseif ($this->status === 'dikirim') {
            $query->whereHas('order', function ($q) {
                $q->where('status', true);
            })->where('shipping_status', true);
        } elseif ($this->status === 'sampai') {
            // For now, no action. Assuming shipping_status boolean isn't enough to distinguish dikirim/sampai yet.
            $query->whereId(0);
        } elseif ($this->status === 'gagal') {
            $query->whereHas('order', function ($q) {
                $q->where('status', false)
                  ->whereHas('latestPayment', function ($sq) {
                      $sq->where('expired_at', '<=', now());
                  });
            });
        }

        return $query->get();
    }

    public function kirimPesanan($id)
    {
        // Placeholder untuk logic kirim pesanan
        // Nanti akan diisi oleh user
        // $orderShop = OrderShop::find($id);
        // ...
        
        // $this->dispatch('pesanan-dikirim');
    }
};
