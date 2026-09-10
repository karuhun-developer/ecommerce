<?php

use App\Models\Order\Order;
use Livewire\Component;

new class extends Component
{
    public string $reference = '';

    public string $token = '';

    public function mount(): void
    {
        $this->token = (string) request()->query('token', '');
    }

    public function check()
    {
        $this->validate([
            'reference' => 'required|string',
        ]);

        $orderQuery = Order::where('reference', $this->reference);
        if (auth()->check()) {
            $orderQuery->where('user_id', auth()->id());
        } else {
            $orderQuery->whereNull('user_id')->where('access_token', $this->token);
        }

        if (! $orderQuery->exists()) {
            $this->addError('reference', 'Transaksi dengan nomor referensi tersebut tidak ditemukan.');

            return;
        }

        return $this->redirectRoute('orders.detail', [
            'reference' => $this->reference,
            ...$orderQuery->firstOrFail()->guestRouteParameters(),
        ], navigate: true);
    }
};
