<div>
    <form class="space-y-6 p-6" wire:submit.prevent="submit">
        <div>
            <flux:heading size="lg">Shop Detail</flux:heading>
            <flux:text class="mt-2">Manage your shop details and location.</flux:text>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Shop Details -->
            <div class="space-y-4">
                <flux:heading size="md">General Information</flux:heading>
                
                <flux:field>
                    <flux:label badge="Required">Shop Name</flux:label>
                    <flux:input wire:model="name" type="text" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="description" />
                    <flux:error name="description" />
                </flux:field>
            </div>

            <!-- Location Details -->
            <div class="space-y-4">
                <flux:heading size="md">Location Information</flux:heading>
                
                <flux:field>
                    <flux:label badge="Required">Location / Branch Name</flux:label>
                    <flux:input wire:model="location_name" type="text" placeholder="e.g. Apotek Gambir" />
                    <flux:error name="location_name" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label badge="Required">Contact Name</flux:label>
                        <flux:input wire:model="contact_name" type="text" />
                        <flux:error name="contact_name" />
                    </flux:field>
                    <flux:field>
                        <flux:label badge="Required">Contact Phone</flux:label>
                        <flux:input wire:model="contact_phone" type="text" />
                        <flux:error name="contact_phone" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label badge="Required">Address</flux:label>
                    <flux:textarea wire:model="address" />
                    <flux:error name="address" />
                </flux:field>

                <flux:field>
                    <flux:label>Note (Patokan)</flux:label>
                    <flux:input wire:model="note" type="text" />
                    <flux:error name="note" />
                </flux:field>
            </div>
        </div>

        <hr class="border-gray-200">

        <!-- Area & Map -->
        <div class="space-y-4">
            <flux:heading size="md">Biteship Area & Map Location</flux:heading>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Area Search -->
                <div class="space-y-4">
                    <flux:field>
                        <flux:label badge="Required">Search Area</flux:label>
                        <div class="flex gap-2">
                            <flux:input wire:model="searchArea" type="text" placeholder="e.g. Gambir" class="flex-1" />
                            <flux:button wire:click="searchBiteshipArea" type="button" icon="magnifying-glass">Search</flux:button>
                        </div>
                        <flux:error name="searchArea" />
                    </flux:field>

                    @if(count($areas) > 0)
                        <div class="border rounded-lg bg-gray-50 shadow-sm max-h-60 overflow-y-auto mt-2">
                            <ul class="divide-y divide-gray-200">
                                @foreach($areas as $area)
                                    <li>
                                        <button 
                                            type="button"
                                            wire:click="selectArea('{{ $area['id'] }}', '{{ $area['name'] }}', '{{ $area['postal_code'] ?? '' }}')"
                                            class="w-full text-left px-4 py-3 hover:bg-gray-100 transition"
                                        >
                                            <div class="font-medium text-sm text-gray-900">{{ $area['name'] }}</div>
                                            <div class="text-xs text-gray-500">Postal: {{ $area['postal_code'] ?? 'N/A' }} | {{ $area['administrative_division_level_1_name'] }}</div>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($area_string)
                        <div class="p-4 bg-green-50 text-green-800 rounded-md text-sm border border-green-200 mt-2">
                            <span class="font-bold">Selected Area:</span> {{ $area_string }} ({{ $postal_code }})
                        </div>
                    @endif
                    <flux:error name="biteship_area_id" />
                </div>

                <!-- Leaflet Map -->
                <div x-data="{
                    lat: $wire.entangle('latitude'),
                    lng: $wire.entangle('longitude'),
                    map: null,
                    marker: null,
                    loadLeaflet() {
                        return new Promise((resolve, reject) => {
                            if (typeof window.L !== 'undefined') {
                                resolve();
                                return;
                            }
                            
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
                                script.onerror = () => reject('Failed to load Leaflet script');
                                document.head.appendChild(script);
                            } else {
                                let checkInterval = setInterval(() => {
                                    if (typeof window.L !== 'undefined') {
                                        clearInterval(checkInterval);
                                        resolve();
                                    }
                                }, 100);
                            }
                        });
                    },
                    initMap() {
                        this.loadLeaflet().then(() => {
                            if (!this.map) {
                                this.map = L.map($refs.mapContainer).setView([this.lat || -6.200000, this.lng || 106.816666], 13);
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    maxZoom: 19,
                                    attribution: '© OpenStreetMap'
                                }).addTo(this.map);

                                this.marker = L.marker([this.lat || -6.200000, this.lng || 106.816666], {
                                    draggable: true
                                }).addTo(this.map);

                                this.marker.on('dragend', (e) => {
                                    let position = this.marker.getLatLng();
                                    this.lat = position.lat;
                                    this.lng = position.lng;
                                });

                                this.map.on('click', (e) => {
                                    let position = e.latlng;
                                    this.marker.setLatLng(position);
                                    this.lat = position.lat;
                                    this.lng = position.lng;
                                });

                                if (!this.lat || !this.lng) {
                                    if (navigator.geolocation) {
                                        navigator.geolocation.getCurrentPosition((position) => {
                                            this.lat = position.coords.latitude;
                                            this.lng = position.coords.longitude;
                                            let newPos = [this.lat, this.lng];
                                            this.marker.setLatLng(newPos);
                                            this.map.panTo(newPos);
                                        }, () => {
                                            console.warn('Geolocation blocked or unavailable.');
                                        });
                                    }
                                }
                            }
                        }).catch(err => console.error(err));
                    }
                }"
                x-init="
                    initMap();
                    $watch('lat', value => {
                        if (marker && value) {
                            marker.setLatLng([value, lng]);
                            map.panTo([value, lng]);
                        }
                    });
                    $watch('lng', value => {
                        if (marker && value) {
                            marker.setLatLng([lat, value]);
                            map.panTo([lat, value]);
                        }
                    });
                ">
                    <flux:label badge="Required" class="mb-2">Pinpoint Location</flux:label>
                    <div x-ref="mapContainer" wire:ignore class="h-64 w-full rounded-lg shadow-sm border border-gray-300 z-0"></div>
                    <div class="mt-2 text-sm text-gray-500 flex justify-between">
                        <span>Lat: <span x-text="lat"></span></span>
                        <span>Lng: <span x-text="lng"></span></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-gray-200">
            <flux:button type="submit" variant="primary">Save Changes</flux:button>
        </div>
    </form>
</div>