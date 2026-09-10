<?php

use App\Models\Order\Order;
use Livewire\Component;

new class extends Component
{
    public string $reference = '';

    public function check(): void
    {
        $this->validate([
            'reference' => 'required|string',
        ]);

        $orderQuery = Order::where('reference', $this->reference);
        if (auth()->check()) {
            $orderQuery->where('user_id', auth()->id());
        } else {
            $orderQuery->whereNull('user_id');
        }

        $order = $orderQuery->first();

        if (! $order) {
            $this->addError('reference', 'Transaksi dengan nomor referensi tersebut tidak ditemukan.');

            return;
        }

        $this->redirectRoute('orders.detail', [
            'reference' => $this->reference,
            ...$order->guestRouteParameters(),
        ], navigate: true);
    }
};
