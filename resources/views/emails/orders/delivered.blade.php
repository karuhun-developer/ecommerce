<x-mail::message>
# Pesanan Anda Telah Sampai!

Halo, pesanan Anda dari toko **{{ $orderShop->shop->name ?? 'Toko' }}** telah berhasil dikirim dan sampai ke tujuan.

<div style="text-align: center; margin: 30px 0; padding: 20px; background-color: #f3f4f6; border-radius: 8px;">
    <p style="margin: 0; font-size: 14px; color: #4b5563; text-transform: uppercase; font-weight: 600;">Kode Transaksi Anda</p>
    <h1 style="margin: 5px 0 0 0; font-size: 32px; font-weight: 900; color: #16a34a;">{{ $orderShop->order->reference }}</h1>
</div>

### Rincian Produk:
<x-mail::panel>
@foreach($orderShop->items as $item)
- **{{ $item->product_data['name'] ?? 'Produk' }}**
  <br> {{ $item->quantity }} x Rp{{ number_format($item->price, 0, ',', '.') }} = **Rp{{ number_format($item->total, 0, ',', '.') }}**
@endforeach
</x-mail::panel>

Terima kasih telah berbelanja menggunakan layanan kami! Jangan lupa berikan ulasan untuk produk dan toko ya!

<x-mail::button :url="route('orders.detail', ['reference' => $orderShop->order->reference])" color="success">
Cek Detail Pesanan
</x-mail::button>

Terima kasih,<br>
Tim {{ config('app.name') }}
</x-mail::message>
