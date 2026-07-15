<?php

use App\Actions\Order\ShipOrderAction;
use App\Models\Order\OrderShop;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public $status = 'semua'; // semua, menunggu-pembayaran, proses, dikirim, sampai, gagal

    public function setStatus($status)
    {
        $this->status = $status;
        $this->resetPage();
    }

    #[Computed]
    public function orders()
    {
        $query = OrderShop::with(['order.latestPayment', 'order.user', 'shop', 'items'])
            ->latest();

        if (! isSingleShop() && auth()->user()->hasRole('shopowner')) {
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
            })->whereNull('waybill_number')->where('shipping_status', false);
        } elseif ($this->status === 'dikirim') {
            $query->whereHas('order', function ($q) {
                $q->where('status', true);
            })->whereNotNull('waybill_number')->where('shipping_status', false);
        } elseif ($this->status === 'sampai') {
            $query->whereHas('order', function ($q) {
                $q->where('status', true);
            })->whereNotNull('waybill_number')->where('shipping_status', true);
        } elseif ($this->status === 'gagal') {
            $query->whereHas('order', function ($q) {
                $q->where('status', false)
                    ->whereHas('latestPayment', function ($sq) {
                        $sq->where('expired_at', '<=', now());
                    });
            });
        }

        return $query->paginate(10);
    }

    #[On('kirimPesanan')]
    public function kirimPesanan($id, ShipOrderAction $action)
    {
        try {
            $orderShop = OrderShop::findOrFail($id);
            $action->execute($orderShop);

            $this->dispatch('toast',
                type: 'success',
                message: 'Pesanan berhasil dikirim melalui kurir Biteship.'
            );
        } catch (Exception $e) {
            $this->dispatch('toast',
                type: 'error',
                message: 'Gagal mengirim pesanan: '.$e->getMessage()
            );
        }
    }
};
