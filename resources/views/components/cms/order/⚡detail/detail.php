<?php

use App\Actions\Order\ShipOrderAction;
use App\Models\Order\OrderShop;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public int $orderShopId;

    public function mount(int $orderShopId): void
    {
        $this->orderShopId = $orderShopId;
    }

    #[Computed]
    public function orderShop(): OrderShop
    {
        return OrderShop::query()
            ->accessibleTo($this->currentUser())
            ->with([
                'order.latestPayment',
                'order.user',
                'order.location',
                'shop',
                'items.productFlat.media',
            ])
            ->findOrFail($this->orderShopId);
    }

    #[On('kirimPesanan')]
    public function kirimPesanan(int $id, ShipOrderAction $action): void
    {
        try {
            abort_unless($id === $this->orderShopId, 404);

            $user = $this->currentUser();
            $action->execute($this->orderShop, $user);

            unset($this->orderShop);

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
