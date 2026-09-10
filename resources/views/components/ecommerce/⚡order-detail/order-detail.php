<?php

use App\Models\Order\Order;
use App\Models\Payment\Payment;
use Livewire\Component;

new class extends Component
{
    public Order $order;

    public ?Payment $payment;

    public bool $isPaid = false;

    public function mount(): void
    {
        $token = request()->query('token');

        if ($this->order->user_id !== null) {
            abort_unless(auth()->id() === $this->order->user_id, 404);
        } else {
            abort_unless(
                is_string($token)
                && filled($this->order->access_token)
                && hash_equals($this->order->access_token, $token),
                404,
            );
        }

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
