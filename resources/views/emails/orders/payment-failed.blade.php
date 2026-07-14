<x-mail::message>
# Pembayaran Gagal / Kedaluwarsa

Halo, kami ingin menginformasikan bahwa pembayaran untuk pesanan Anda berikut ini telah gagal atau waktu pembayaran telah habis (kedaluwarsa).

<div style="text-align: center; margin: 30px 0; padding: 20px; background-color: #fef2f2; border-radius: 8px;">
    <p style="margin: 0; font-size: 14px; color: #991b1b; text-transform: uppercase; font-weight: 600;">Kode Transaksi Anda</p>
    <h1 style="margin: 5px 0 0 0; font-size: 32px; font-weight: 900; color: #dc2626;">{{ $order->reference }}</h1>
</div>

**Ringkasan Transaksi:**
- Total Pembayaran: **Rp{{ number_format($order->total, 0, ',', '.') }}**

Jika Anda masih ingin melakukan pembelian, silakan buat pesanan baru.

<x-mail::button :url="route('orders.detail', ['reference' => $order->reference])" color="primary">
Cek Detail Pesanan
</x-mail::button>

Terima kasih,<br>
Tim {{ config('app.name') }}
</x-mail::message>
