
<div>
    <footer class="bg-white border-t mt-16 pb-16 md:pb-8">
        <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <flux:heading size="lg" class="mb-4">
                    {{ config('app.name') }}
                </flux:heading>
                <ul class="space-y-3 text-sm text-gray-500">
                    <li>
                        <a href="#" class="hover:text-green-600" wire:navigate>
                            Tentang {{ config('app.name') }}
                        </a>
                    </li>
                    <li>
                        <a href="#" class="hover:text-green-600" wire:navigate>
                            Hak Kekayaan Intelektual
                        </a>
                    </li>
                    <li>
                        <a href="#" class="hover:text-green-600" wire:navigate>
                            Karir
                        </a>
                    </li>
                    <li>
                        <a href="#" class="hover:text-green-600" wire:navigate>
                            Blog
                        </a>
                    </li>
                </ul>
            </div>
            <div>
                <flux:heading size="lg" class="mb-4">
                    Beli
                </flux:heading>
                <ul class="space-y-3 text-sm text-gray-500">
                    <li>
                        <a href="#" class="hover:text-green-600" wire:navigate>
                            Tagihan & Top Up
                        </a>
                    </li>
                    <li>
                        <a href="#" class="hover:text-green-600" wire:navigate>
                            Tukar Tambah Handphone
                        </a>
                    </li>
                </ul>
            </div>
            <div>
                <flux:heading size="lg" class="mb-4">
                    Jual
                </flux:heading>
                <ul class="space-y-3 text-sm text-gray-500">
                    <li>
                        <a href="#" class="hover:text-green-600">
                            Pusat Edukasi Seller
                        </a>
                    </li>
                    <li>
                        <a href="#" class="hover:text-green-600">
                            Daftar Official Store
                        </a>
                    </li>
                </ul>
            </div>
            <div>
                <a href="/" class="text-3xl font-black text-green-600 tracking-tight block mb-4">
                    {{ config('app.name') }}<span class="text-gray-800">.</span>
                </a>
                <flux:text class="mb-4">
                    Download aplikasi {{ config('app.name') }} sekarang.
                </flux:text>
                <div class="flex gap-4">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Play Store" class="h-10">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store" class="h-10">
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-6 pt-8 border-t flex flex-col md:flex-row items-center justify-between gap-4">
            <flux:text>© 2026 Nexa. All rights reserved.</flux:text>
            <div class="flex gap-4 text-gray-400">
                <flux:icon.chat-bubble-oval-left class="w-5 h-5 hover:text-gray-600 cursor-pointer" />
                <flux:icon.camera class="w-5 h-5 hover:text-gray-600 cursor-pointer" />
                <flux:icon.globe-alt class="w-5 h-5 hover:text-gray-600 cursor-pointer" />
            </div>
        </div>
    </footer>
</div>