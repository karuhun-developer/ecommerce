<?php

use App\Models\Order\OrderShop;
use Livewire\Component;

new class extends Component
{
    public $orderShopId;

    public function mount($orderShopId)
    {
        $this->orderShopId = $orderShopId;
    }

    public function getOrderShopProperty()
    {
        return OrderShop::with([
            'order.latestPayment',
            'order.user',
            'order.location',
            'shop',
            'items.productFlat.media',
        ])->findOrFail($this->orderShopId);
    }

    public function kirimPesanan(\App\Actions\Order\ShipOrderAction $action)
    {
        try {
            $action->execute($this->orderShop);

            $this->dispatch('toast',
                type: 'success',
                message: 'Pesanan berhasil dikirim melalui kurir Biteship.'
            );
        } catch (\Exception $e) {
            $this->dispatch('toast',
                type: 'error',
                message: 'Gagal mengirim pesanan: ' . $e->getMessage()
            );
        }
    }
};
