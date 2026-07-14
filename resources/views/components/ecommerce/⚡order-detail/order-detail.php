<?php

use App\Models\Order\Order;
use App\Models\Payment\Payment;
use Livewire\Component;

new class extends Component
{
    public Order $order;

    public ?Payment $payment;

    public bool $isPaid = false;

    public function mount()
    {
        $this->order->load(
            'user',
            'location',
            'orderShops.items.productFlat.media',
            'orderShops.shop',
            'orderShops.latestShipment',
            'orderShops.shipments',
            'latestPayment',
        );

        $this->payment = $this->order->latestPayment;
        $this->isPaid = $this->payment?->paid_at !== null;
    }
};
