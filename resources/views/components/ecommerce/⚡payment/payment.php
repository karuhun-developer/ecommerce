<?php

use App\Actions\Payment\CreatePaymentAction;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use Exception;
use Livewire\Component;

new class extends Component
{
    public Order $order;

    public ?Payment $payment = null;

    public $paymentMethod = '';

    public function mount(Order $order)
    {
        $this->order = $order;
        $this->payment = $order->payments()->latest()->first();
        if ($this->payment && $this->payment->expired_at->isFuture()) {
            $this->paymentMethod = $this->payment->channel;
        }
    }

    public function getPaymentModel()
    {
        return $this->order->payments()->latest()->first();
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
