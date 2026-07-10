<?php

use App\Actions\Payment\CreatePaymentAction;
use App\Models\Order\Order;
use App\Models\Payment\Payment;

use Livewire\Component;

new class extends Component
{
    public Order $order;

    public ?Payment $payment;

    public $paymentMethod = '';

    public function mount()
    {
        $this->order->load(
            'orderShops.items.productFlat.media',
            'orderShops.shop',
            'orderShops.latestShipment',
            'latestPayment',
        );

        $this->payment = $this->order->latestPayment;

        // Set the payment method if the payment exists and is not expired
        if ($this->payment && $this->payment->expired_at->isFuture()) {
            $this->paymentMethod = $this->payment->channel;
        }
    }

    public function submit(CreatePaymentAction $createPaymentAction)
    {
        $this->validate([
            'paymentMethod' => 'required|in:qris,bca,bni,bri',
        ]);

        try {
            $this->payment = $createPaymentAction->handle($this->order, $this->paymentMethod);
            $this->dispatch('toast',
                type: 'success',
                message: 'Payment method selected successfully.',
            );
        } catch (Exception $e) {
            $this->dispatch('toast',
                type: 'error',
                message: $e->getMessage(),
            );
        }
    }
};
