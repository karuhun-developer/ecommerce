
<div>
    <flux:modal
        name="shippingFormModal" 
        class="max-w-3xl md:min-w-3xl"
        flyout
    >
        <form wire:submit.prevent="submit" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $id ? 'Edit Alamat' : 'Tambah Alamat Baru' }}
                </flux:heading>
                <flux:text class="mt-1">Lengkapi data alamat pengiriman kamu.</flux:text>
            </div>

            <flux:field>
                <flux:label badge="Required">Nama Lokasi</flux:label>
                <flux:text>Nama lokasi ini untuk memudahkan kamu mengingat alamat ini, misal: Rumah, Kantor, dll.</flux:text>
                <flux:input wire:model="location_name" type="text" placeholder="e.g. Rumah" />
                <flux:error name="location_name" />
            </flux:field>

            <!-- Contact -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label badge="Wajib">Nama Penerima</flux:label>
                    <flux:input wire:model="contact_name" type="text" placeholder="Nama lengkap penerima" />
                    <flux:error name="contact_name" />
                </flux:field>
                <flux:field>
                    <flux:label badge="Wajib">Nomor HP</flux:label>
                    <flux:input wire:model="contact_phone" type="text" placeholder="08xxxxxxxxxx" />
                    <flux:error name="contact_phone" />
                </flux:field>
            </div>

            <!-- Address -->
            <flux:field>
                <flux:label badge="Wajib">Alamat Lengkap</flux:label>
                <flux:textarea wire:model="address" placeholder="Jl., Blok, No. Rumah, RT/RW" rows="3" />
                <flux:error name="address" />
            </flux:field>

            <!-- Note -->
            <flux:field>
                <flux:label>Catatan / Patokan</flux:label>
                <flux:input wire:model="note" type="text" placeholder="Contoh: Dekat Indomaret, rumah cat kuning" />
                <flux:error name="note" />
            </flux:field>

            <hr class="border-gray-100">

            <!-- Biteship Area Search -->
            <div class="space-y-3">
                <flux:heading size="sm">Area & Kode Pos (Biteship)</flux:heading>
                <flux:field>
                    <flux:label badge="Wajib">Cari Area / Kecamatan</flux:label>
                    <div class="flex gap-2">
                        <flux:input wire:model="searchArea" type="text" placeholder="contoh: Gambir" class="flex-1" />
                        <flux:button wire:click="searchBiteshipArea" type="button" icon="magnifying-glass">Cari</flux:button>
                    </div>
                    <flux:error name="searchArea" />
                </flux:field>

                @if(count($areas) > 0)
                    <div class="border rounded-xl bg-gray-50 shadow-sm max-h-52 overflow-y-auto">
                        <ul class="divide-y divide-gray-200">
                            @foreach($areas as $area)
                                <li>
                                    <button
                                        type="button"
                                        wire:click="selectArea('{{ $area['id'] }}', '{{ $area['name'] }}', '{{ $area['postal_code'] }}')"
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

                @if($area_string)
                    <div class="flex items-center gap-2 p-3 bg-gray-50 text-gray-800 rounded-xl text-sm border border-gray-200">
                        <flux:icon.check-circle class="w-4 h-4 text-gray-600 shrink-0" />
                        <span><span class="font-bold">Area terpilih:</span> {{ $area_string }} ({{ $postal_code }})</span>
                    </div>
                @endif

                <flux:error name="biteship_area_id" />
            </div>

            <hr class="border-gray-100">

            <!-- Leaflet Map -->
            <div
                x-data="{
                    lat: $wire.entangle('latitude'),
                    lng: $wire.entangle('longitude'),
                    map: null,
                    marker: null,
                    loadLeaflet() {
                        return new Promise((resolve, reject) => {
                            if (typeof window.L !== 'undefined') { resolve(); return; }
                            if (!document.getElementById('leaflet-css')) {
                                let link = document.createElement('link');
                                link.id = 'leaflet-css';
                                link.rel = 'stylesheet';
                                link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                                document.head.appendChild(link);
                            }
                            if (!document.getElementById('leaflet-js')) {
                                let script = document.createElement('script');
                                script.id = 'leaflet-js';
                                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                                script.onload = () => resolve();
                                script.onerror = () => reject('Failed to load Leaflet');
                                document.head.appendChild(script);
                            } else {
                                let check = setInterval(() => { if (typeof window.L !== 'undefined') { clearInterval(check); resolve(); } }, 100);
                            }
                        });
                    },
                    initMap() {
                        this.loadLeaflet().then(() => {
                            if (!this.map) {
                                this.map = L.map($refs.mapContainer).setView([this.lat || -6.2, this.lng || 106.816666], 13);
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    maxZoom: 19, attribution: '© OpenStreetMap'
                                }).addTo(this.map);
                                this.marker = L.marker([this.lat || -6.2, this.lng || 106.816666], { draggable: true }).addTo(this.map);
                                this.marker.on('dragend', (e) => { let p = this.marker.getLatLng(); this.lat = p.lat; this.lng = p.lng; });
                                this.map.on('click', (e) => { let p = e.latlng; this.marker.setLatLng(p); this.lat = p.lat; this.lng = p.lng; });
                                if (!this.lat || !this.lng) {
                                    navigator.geolocation && navigator.geolocation.getCurrentPosition((pos) => {
                                        this.lat = pos.coords.latitude;
                                        this.lng = pos.coords.longitude;
                                        let np = [this.lat, this.lng];
                                        this.marker.setLatLng(np);
                                        this.map.panTo(np);
                                    }, () => {});
                                }
                            } else {
                                let np = [this.lat || -6.2, this.lng || 106.816666];
                                this.marker.setLatLng(np);
                                this.map.panTo(np);
                            }
                            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 100);
                        }).catch(e => console.error(e));
                    }
                }"
                x-init="
                    let obs = new IntersectionObserver((entries) => { entries.forEach(e => { if (e.isIntersecting) initMap(); }); });
                    obs.observe($refs.mapContainer);
                    $watch('lat', v => { if (marker && v) { marker.setLatLng([v, lng]); map.panTo([v, lng]); } });
                    $watch('lng', v => { if (marker && v) { marker.setLatLng([lat, v]); map.panTo([lat, v]); } });
                "
            >
                <flux:label badge="Wajib" class="mb-2">Titik Lokasi di Peta</flux:label>
                <flux:text class="mb-2 text-sm">Geser marker atau klik peta untuk menentukan lokasi.</flux:text>
                <div x-ref="mapContainer" wire:ignore class="h-64 w-full rounded-xl shadow-sm border border-gray-200 z-0"></div>
                <div class="mt-2 text-xs text-gray-500 flex justify-between">
                    <span>Lat: <span x-text="lat ?? '-'"></span></span>
                    <span>Lng: <span x-text="lng ?? '-'"></span></span>
                </div>
                <flux:error name="latitude" />
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" @click="$flux.modal('shippingFormModal').close()">Batal</flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $id ? 'Simpan Perubahan' : 'Tambah Alamat' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>