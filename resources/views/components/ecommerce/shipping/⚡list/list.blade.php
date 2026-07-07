
<div class="bg-white border rounded-2xl shadow-sm">
    @auth
        <div class="flex justify-between items-center px-6 pt-5 pb-4 border-b">
            <h2 class="font-bold text-gray-900">Alamat Pengiriman</h2>
            <flux:button size="sm" variant="primary" color="green" icon="plus" wire:click="openCreate">
                Tambah Alamat
            </flux:button>
        </div>

        <div class="p-6 space-y-3">
            @forelse ($this->addresses as $location)
                <div
                    wire:key="addr-{{ $location->id }}"
                    wire:click="selectAddress({{ $location->id }})"
                    class="border rounded-xl p-4 cursor-pointer transition {{ $selectedLocationId === $location->id ? 'border-green-500 bg-green-50' : 'hover:border-gray-300' }}"
                >
                    <div class="flex items-start justify-between gap-3">
                        <!-- Radio indicator -->
                        <div class="mt-0.5 shrink-0">
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center {{ $selectedLocationId === $location->id ? 'border-green-500' : 'border-gray-300' }}">
                                @if ($selectedLocationId === $location->id)
                                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                @endif
                            </div>
                        </div>

                        <!-- Address info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="font-bold text-gray-900 text-sm">{{ $location->name }}</span>
                            </div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="font-bold text-gray-900 text-sm">{{ $location->contact_name }}</span>
                                <span class="text-xs text-gray-500">{{ $location->contact_phone }}</span>
                            </div>
                            <p class="text-sm text-gray-600 line-clamp-2">{{ $location->address }}</p>
                            @if ($location->note)
                                <p class="text-xs text-gray-400 mt-0.5">Patokan: {{ $location->note }}</p>
                            @endif
                            @if ($location->area_string)
                                <div class="flex items-center gap-1 mt-1">
                                    <flux:icon.map-pin class="w-3 h-3 text-gray-400" />
                                    <span class="text-xs text-gray-500">{{ $location->area_string }}, {{ $location->postal_code }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1 shrink-0" x-on:click.stop>
                            <flux:button size="xs" variant="ghost" wire:click="openEdit({{ $location->id }})">
                                Edit
                            </flux:button>
                            <flux:button
                                size="xs"
                                variant="ghost"
                                class="text-red-500 hover:text-red-600"
                                @click="$wire.dispatch('confirm', {
                                    function: 'deleteAddress',
                                    id: '{{ $location->id }}',
                                })"
                            >
                                Hapus
                            </flux:button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <flux:icon.map-pin class="w-10 h-10 text-gray-200 mx-auto mb-3" />
                    <p class="text-sm text-gray-500">Belum ada alamat tersimpan.</p>
                    <flux:button size="sm" variant="primary" color="green" class="mt-3" wire:click="openCreate">
                        + Tambah Alamat
                    </flux:button>
                </div>
            @endforelse
        </div>

        <livewire:ecommerce.shipping.create-update lazy />
    @else
        <div
            class="px-6 pt-5 pb-4 border-b"
            x-data="{
                lsKey: 'checkout_guest_address',

                save() {
                    localStorage.setItem(this.lsKey, JSON.stringify({
                        contact_name: $wire.guest_contact_name,
                        contact_phone: $wire.guest_contact_phone,
                        email: $wire.guest_email,
                        address: $wire.guest_address,
                        note: $wire.guest_note,
                        postal_code: $wire.guest_postal_code,
                        area_string: $wire.guest_area_string,
                        biteship_area_id: $wire.guest_biteship_area_id,
                        latitude: $wire.guest_latitude,
                        longitude: $wire.guest_longitude,
                    }));
                },

                async restore() {
                    try {
                        const raw = localStorage.getItem(this.lsKey);
                        if (!raw) return;
                        const data = JSON.parse(raw);
                        if (data && data.contact_name) {
                            await $wire.restoreGuestData(data);
                        }
                    } catch(e) {}
                }
            }"
            x-init="
                restore();
                $watch('$wire.guest_contact_name', () => save());
                $watch('$wire.guest_contact_phone', () => save());
                $watch('$wire.guest_email', () => save());
                $watch('$wire.guest_address', () => save());
                $watch('$wire.guest_note', () => save());
                $watch('$wire.guest_biteship_area_id', () => save());
                $watch('$wire.guest_latitude', () => save());
                $watch('$wire.guest_longitude', () => save());
            "
        >
            <h2 class="font-bold text-gray-900">Alamat Pengiriman</h2>
            <div class="mt-2 flex items-center gap-3 p-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                <flux:icon.information-circle class="w-5 h-5 text-amber-500 shrink-0" />
                <span>
                    <a href="{{ route('login') }}" class="font-bold underline" wire:navigate>Masuk</a>
                    atau
                    <a href="{{ route('register') }}" class="font-bold underline" wire:navigate>Daftar</a>
                    untuk menyimpan & menggunakan alamat tersimpan.
                </span>
            </div>
        </div>

        <div class="p-6 space-y-4">
            <!-- Contact -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label badge="Wajib">Nama Penerima</flux:label>
                    <flux:input wire:model="guest_contact_name" type="text" placeholder="Nama lengkap" />
                    <flux:error name="guest_contact_name" />
                </flux:field>
                <flux:field>
                    <flux:label badge="Wajib">Nomor HP</flux:label>
                    <flux:input wire:model="guest_contact_phone" type="text" placeholder="08xxxxxxxxxx" />
                    <flux:error name="guest_contact_phone" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input wire:model="guest_email" type="email" placeholder="Email" />
                <flux:error name="guest_email" />
            </flux:field>

            <flux:field>
                <flux:label badge="Wajib">Alamat Lengkap</flux:label>
                <flux:textarea wire:model="guest_address" placeholder="Jl., Blok, No. Rumah, RT/RW" rows="3" />
                <flux:error name="guest_address" />
            </flux:field>

            <flux:field>
                <flux:label>Catatan / Patokan</flux:label>
                <flux:input wire:model="guest_note" type="text" placeholder="Dekat Indomaret, rumah cat kuning" />
            </flux:field>

            <!-- Biteship area search -->
            <flux:field>
                <flux:label badge="Wajib">Cari Area / Kecamatan</flux:label>
                <div class="flex gap-2">
                    <flux:input wire:model="guest_searchArea" type="text" placeholder="contoh: Gambir" class="flex-1" />
                    <flux:button wire:click="searchGuestArea" type="button" icon="magnifying-glass">Cari</flux:button>
                </div>
                <flux:error name="guest_searchArea" />
            </flux:field>

            @if(count($guest_areas) > 0)
                <div class="border rounded-xl bg-gray-50 max-h-52 overflow-y-auto">
                    <ul class="divide-y divide-gray-200">
                        @foreach($guest_areas as $area)
                            <li>
                                <button
                                    type="button"
                                    wire:click="selectGuestArea('{{ $area['id'] }}', '{{ $area['name'] }}', '{{ $area['postal_code'] }}')"
                                    class="w-full text-left px-4 py-3 hover:bg-gray-100 transition"
                                >
                                    <div class="font-medium text-sm text-gray-900">{{ $area['name'] }}</div>
                                    <div class="text-xs text-gray-500">Kode Pos: {{ $area['postal_code'] }} | {{ $area['administrative_division_level_1_name'] }}</div>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($guest_area_string)
                <div class="flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-800">
                    <flux:icon.check-circle class="w-4 h-4 text-green-600 shrink-0" />
                    <span><span class="font-bold">Area terpilih:</span> {{ $guest_area_string }} ({{ $guest_postal_code }})</span>
                </div>
            @endif

            <!-- Leaflet Map for guest -->
            <div
                x-data="{
                    lat: $wire.entangle('guest_latitude'),
                    lng: $wire.entangle('guest_longitude'),
                    map: null,
                    marker: null,
                    loadLeaflet() {
                        return new Promise((resolve, reject) => {
                            if (typeof window.L !== 'undefined') { resolve(); return; }
                            if (!document.getElementById('leaflet-css')) {
                                let l = document.createElement('link'); l.id='leaflet-css'; l.rel='stylesheet';
                                l.href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                                document.head.appendChild(l);
                            }
                            if (!document.getElementById('leaflet-js')) {
                                let s = document.createElement('script'); s.id='leaflet-js';
                                s.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                                s.onload = () => resolve(); s.onerror = () => reject();
                                document.head.appendChild(s);
                            } else {
                                let c = setInterval(() => { if (window.L) { clearInterval(c); resolve(); } }, 100);
                            }
                        });
                    },
                    initMap() {
                        this.loadLeaflet().then(() => {
                            if (!this.map) {
                                this.map = L.map($refs.guestMap).setView([this.lat||-6.2, this.lng||106.816666], 13);
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom:19, attribution:'© OpenStreetMap' }).addTo(this.map);
                                this.marker = L.marker([this.lat||-6.2, this.lng||106.816666], { draggable:true }).addTo(this.map);
                                this.marker.on('dragend', e => { let p=this.marker.getLatLng(); this.lat=p.lat; this.lng=p.lng; });
                                this.map.on('click', e => { this.marker.setLatLng(e.latlng); this.lat=e.latlng.lat; this.lng=e.latlng.lng; });
                                if (!this.lat || !this.lng) {
                                    navigator.geolocation && navigator.geolocation.getCurrentPosition(p => {
                                        this.lat=p.coords.latitude; this.lng=p.coords.longitude;
                                        let np=[this.lat,this.lng]; this.marker.setLatLng(np); this.map.panTo(np);
                                    }, ()=>{});
                                }
                            }
                            setTimeout(() => this.map && this.map.invalidateSize(), 100);
                        });
                    }
                }"
                x-init="
                    let o = new IntersectionObserver(es => es.forEach(e => { if(e.isIntersecting) initMap(); }));
                    o.observe($refs.guestMap);
                    $watch('lat', v => { if(marker && v) { marker.setLatLng([v,lng]); map.panTo([v,lng]); } });
                    $watch('lng', v => { if(marker && v) { marker.setLatLng([lat,v]); map.panTo([lat,v]); } });
                "
            >
                <flux:label class="mb-2">Titik Lokasi di Peta</flux:label>
                <flux:text class="mb-2 text-sm">Geser marker atau klik peta untuk menentukan lokasi.</flux:text>
                <div x-ref="guestMap" wire:ignore class="h-56 w-full rounded-xl border border-gray-200 shadow-sm z-0"></div>
                <div class="mt-2 text-xs text-gray-500 flex justify-between">
                    <span>Lat: <span x-text="lat ?? '-'"></span></span>
                    <span>Lng: <span x-text="lng ?? '-'"></span></span>
                </div>
            </div>
        </div>
    @endauth
</div>