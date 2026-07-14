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
            'items.productFlat.media'
        ])->findOrFail($this->orderShopId);
    }

    public function kirimPesanan()
    {
        // Placeholder untuk logic kirim pesanan
        // $this->orderShop->update(['shipping_status' => true]);
        // $this->dispatch('pesanan-dikirim');
    }
};
