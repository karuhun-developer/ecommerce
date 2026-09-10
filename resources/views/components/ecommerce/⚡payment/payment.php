<?php

use App\Actions\Ecommerce\Payment\CreatePaymentAction;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    public Order $order;

    public ?Payment $payment = null;

    public string $paymentMethod = '';

    public bool $isPaid = false;

    #[Locked]
    public string $accessToken = '';

    public function mount(): void
    {
        $token = request()->query('token');
        $this->accessToken = is_string($token) ? $token : '';

        if ($this->order->user_id !== null) {
            abort_unless(auth()->id() === $this->order->user_id, 404);
        } else {
            abort_unless(
                filled($this->order->access_token)
                && filled($this->accessToken)
                && hash_equals($this->order->access_token, $this->accessToken),
                404,
            );
        }

        $this->order->load(
            'orderShops.items.productFlat.media',
            'orderShops.shop',
            'orderShops.latestShipment',
            'latestPayment',
        );

        $this->payment = $this->order->latestPayment;
        $this->isPaid = $this->payment?->paid_at !== null;

        // Set the payment method if the payment exists and is not expired
        if ($this->payment?->expired_at?->isFuture()) {
            $this->paymentMethod = $this->payment->channel;
        }
    }

    public function submit(CreatePaymentAction $createPaymentAction): void
    {
        $this->validate([
            'paymentMethod' => 'required|in:qris,bca,bni,bri',
        ]);

        try {
            $this->payment = $createPaymentAction->handle(
                order: $this->order,
                paymentMethod: $this->paymentMethod,
                actor: auth()->user(),
                guestToken: $this->accessToken,
            );
            $this->isPaid = $this->payment->paid_at !== null;
            $this->dispatch('toast',
                type: 'success',
                message: 'Payment method selected successfully.',
            );
        } catch (Throwable $exception) {
            report($exception);

            $this->dispatch('toast',
                type: 'error',
                message: 'Pembayaran belum dapat diproses. Silakan coba lagi.',
            );
        }
    }
};
