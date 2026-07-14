<x-mail::message>
# Pembayaran Berhasil Diterima!

Halo, kami telah menerima pembayaran Anda untuk pesanan berikut. Pesanan Anda akan segera diproses.

<div style="text-align: center; margin: 30px 0; padding: 20px; background-color: #f3f4f6; border-radius: 8px;">
    <p style="margin: 0; font-size: 14px; color: #4b5563; text-transform: uppercase; font-weight: 600;">Kode Transaksi Anda</p>
    <h1 style="margin: 5px 0 0 0; font-size: 32px; font-weight: 900; color: #16a34a;">{{ $order->reference }}</h1>
</div>

**Ringkasan Transaksi:**
- Total Pembayaran: **Rp{{ number_format($order->total, 0, ',', '.') }}**

Untuk melihat status terbaru dan detail pesanan, silakan klik tombol di bawah ini:

<x-mail::button :url="route('orders.detail', ['reference' => $order->reference])" color="success">
Cek Detail Pesanan
</x-mail::button>

Terima kasih,<br>
Tim {{ config('app.name') }}
</x-mail::message>
