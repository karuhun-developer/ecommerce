<?php

use App\Actions\Order\ShipOrderAction;
use App\Models\Order\OrderShop;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $status = 'semua';

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        $query = OrderShop::query()
            ->accessibleTo($this->currentUser())
            ->with(['order.latestPayment', 'order.user', 'shop', 'items'])
            ->latest();

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
    public function kirimPesanan(int $id, ShipOrderAction $action): void
    {
        try {
            $user = $this->currentUser();
            $orderShop = OrderShop::query()
                ->accessibleTo($user)
                ->findOrFail($id);

            $action->execute($orderShop, $user);

            unset($this->orders);

            $this->dispatch('toast',
                type: 'success',
                message: 'Pesanan berhasil dikirim melalui kurir Biteship.'
            );
        } catch (Throwable $exception) {
            report($exception);

            $this->dispatch('toast',
                type: 'error',
                message: 'Gagal mengirim pesanan. Silakan coba lagi.'
            );
        }
    }

    private function currentUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
};
