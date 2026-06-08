# Plan CRUD Shop & Location dengan Integrasi Biteship & Leaflet Map

## 1. Konfigurasi Mode (Single vs Multiple)
- Kita butuh penanda mode aplikasi (bisa via file `config/shop.php`, `.env`, atau tabel setting).
- Di **Folio Page** (`resources/views/pages/cms/shop/index.blade.php`), kita akan kasih logic sederhana:
  ```blade
  @if(config('shop.mode') === 'single')
      <livewire:cms.shop.single />
  @else
      <livewire:cms.shop.table />
  @endif
  ```

## 2. Struktur Komponen Volt
Kita akan buat struktur folder Volt component sesuai pola yang sebelumnya:

```text
resources/views/components/cms/shop/
├── ⚡single
│   ├── single.php         (Logic update Shop & Location untuk mode Single)
│   └── single.blade.php   (View form, Leaflet map, Area Search)
├── ⚡table
│   ├── table.php          (Datagrid listing Shop untuk mode Multiple)
│   └── table.blade.php    (Tabel flux dengan aksi Update/Delete)
└── ⚡create-update
    ├── create-update.php       (Logic create/update Shop & Location)
    └── create-update.blade.php (Modal form, Leaflet map, Area Search)
```

## 3. Integrasi Leaflet Map
- Akan ditambahkan div container `<div id="map" wire:ignore></div>`.
- Inisialisasi peta menggunakan script block Livewire v4 (`@script` atau di Alpine / Livewire hooks terbaru).
- Menggunakan **`$wire.entangle('latitude')`** dan **`$wire.entangle('longitude')`** di Alpine.js/JavaScript untuk menyelaraskan marker map secara dua arah ke properti Livewire, sehingga tidak perlu manual memanggil `$wire.set()`.
- Saat halaman diload, Leaflet akan otomatis membaca nilai `entangle` tersebut untuk menaruh marker di posisi yang tersimpan.

## 4. Pencarian Area Biteship (Manual Search)
- Input field untuk mencari area dan sebuah **Tombol Search**.
- *Tidak menggunakan* debounce live update untuk menghindari tagihan API biteship membengkak.
- User mengetik nama area, lalu menekan tombol "Search Area".
- Tombol memicu action Livewire, me-request ke `BiteshipService->getMapsAreas(['input' => $search])`.
- Menampilkan hasil list area (provinsi, kota, kecamatan, kodepos) via dropdown/radio list.
- Saat area dipilih, sistem akan mengisi property `biteship_area_id`, string alamat, dan `postal_code` yang dibutuhkan untuk request `location` Biteship.

## 5. Flow Action Classes (Shop & Location)

Kita akan membuat Action classes terpisah untuk menjaga kebersihan kode controller/komponen:

### `app/Actions/Cms/Shop/StoreShopAction.php`
1. Membuat record di tabel `shops` menggunakan form input Shop (name, description, dll).
2. Membentuk payload untuk API Biteship Location:
   ```php
   $payload = [
       "name" => $data['location_name'],
       "contact_name" => $data['contact_name'],
       "contact_phone" => $data['contact_phone'],
       "address" => $data['address'],
       "note" => $data['note'],
       "postal_code" => $data['postal_code'],
       "latitude" => $data['latitude'],
       "longitude" => $data['longitude'],
       "type" => "origin"
   ];
   ```
3. Hit `BiteshipService->createLocation($payload)`.
4. Mendapatkan `id` dari response (contoh: `61d565c...`).
5. Membuat record di tabel `locations` dan menyimpannya beserta referensi `shop_id` dan `biteship_location_id`.

### `app/Actions/Cms/Shop/UpdateShopAction.php`
1. Update tabel `shops`.
2. Hit `BiteshipService->updateLocation($location->biteship_location_id, $payload)`.
3. Update record `locations` di database kita dengan parameter yang baru (latitude, longitude, nama, dll).

### `app/Actions/Cms/Shop/DeleteShopAction.php`
1. Mencari lokasi dari relasi shop.
2. Jika ada `biteship_location_id`, hit `BiteshipService->deleteLocation($location->biteship_location_id)`.
3. Delete record `Shop` (karena cascade/delete location juga berjalan).

## 6. Persiapan Database
- Pastikan migration `shops` dan `locations` sudah benar. Khusus tabel `locations`, pastikan kolom latitude, longitude, area, dsb sudah nullable/lengkap.
- Pastikan relasi model `Shop` `hasMany` (atau `hasOne` untuk default shop location) ke `Location` sudah diset up dengan benar.
