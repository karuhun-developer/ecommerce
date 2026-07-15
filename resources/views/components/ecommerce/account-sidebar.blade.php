@props(['active' => ''])

<div class="me-10 w-full pb-4 md:w-[220px]">
    <flux:navlist>
        <flux:navlist.item :href="route('orders.index')" :current="$active === 'orders'" wire:navigate>Daftar Transaksi</flux:navlist.item>
        <flux:navlist.item :href="route('account.profile')" :current="$active === 'profile'" wire:navigate>Pengaturan Akun</flux:navlist.item>
        <flux:navlist.item href="/account/reviews" :current="$active === 'reviews'" wire:navigate>Ulasan Saya</flux:navlist.item>
    </flux:navlist>
</div>
