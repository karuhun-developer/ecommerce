<?php

use App\Models\Order\Order;
use App\Models\Payment\Payment;
use Livewire\Component;

new class extends Component
{
    public Order $order;
    public ?Payment $payment = null;
    public bool $isPaid = false;

    public function mount(Order $order)
    {
        $this->order = $order;
        $this->payment = $order->payments()->latest()->first();
        $this->isPaid = $this->payment?->paid_at !== null;
    }
};
