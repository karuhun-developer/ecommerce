<h1 align="center">🛒 Ecommerce Platform</h1>

<p align="center">
  Platform e-commerce multi-toko (multi-shop) berfitur lengkap yang dibangun dengan Laravel 13, Livewire 4, dan Flux UI.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.4-blue?logo=php" />
  <img src="https://img.shields.io/badge/Laravel-13-red?logo=laravel" />
  <img src="https://img.shields.io/badge/Livewire-4-purple" />
  <img src="https://img.shields.io/badge/TailwindCSS-v4-teal?logo=tailwindcss" />
  <img src="https://img.shields.io/badge/License-MIT-green" />
</p>

---

## ✨ Fitur

### 🛍️ Storefront (Pelanggan)

| Fitur                                                | Status  |
| ---------------------------------------------------- | ------- |
| Beranda dengan produk & kategori unggulan            | ✅ Selesai |
| Katalog produk dengan filter kategori                | ✅ Selesai |
| Halaman detail produk dengan varian & atribut        | ✅ Selesai |
| Keranjang belanja (session/auth)                     | ✅ Selesai |
| Checkout dengan pilihan alamat & pengiriman          | ✅ Selesai |
| Pembayaran via **Midtrans** (Snap)                   | ✅ Selesai |
| Konfirmasi pesanan & notifikasi email                | ✅ Selesai |
| Lacak pesanan guest via kode referensi               | ✅ Selesai |
| Riwayat pesanan (pengguna login)                     | ✅ Selesai |
| Halaman detail pesanan                               | ✅ Selesai |
| Dynamic SEO (OpenGraph, TwitterCard, JSON-LD)        | ✅ Selesai |

### 🏪 CMS / Admin Dashboard

| Fitur                                                   | Status  |
| ------------------------------------------------------- | ------- |
| Dashboard overview                                      | ✅ Selesai |
| Manajemen produk (CRUD + varian + atribut)              | ✅ Selesai |
| Manajemen kategori produk                               | ✅ Selesai |
| Manajemen atribut & grup atribut                        | ✅ Selesai |
| Manajemen toko (Shop management)                        | ✅ Selesai |
| Manajemen pengguna (User management)                    | ✅ Selesai |
| Manajemen peran & hak akses (Role & permission)         | ✅ Selesai |
| Navigation menu builder                                 | ✅ Selesai |
| Activity log viewer                                     | ✅ Selesai |
| Laravel Pulse monitoring dashboard                      | ✅ Selesai |
| Log viewer (Opcodes Log Viewer)                         | ✅ Selesai |
| Validasi & ulasan produk / toko (Review product & shop) | ✅ Selesai |
| Dashboard Analitik Pengguna (User/Analytics)            | ✅ Selesai |

### 🚧 Dalam Proses / Roadmap

| Fitur                                           | Status     |
| ----------------------------------------------- | ---------- |
| Halaman detail toko (public storefront per shop)| 🔄 Berjalan |
| Pendaftaran pemilik toko (multi-shop mode)      | 🔄 Berjalan |
| Manajemen banner (CMS)                          | 🔄 Berjalan |

---

## 🔗 Integrations & Webhooks Setup

Untuk menggunakan fungsionalitas penuh pembayaran dan pengiriman, Anda harus menyiapkan akun Midtrans dan Biteship, lalu mengkonfigurasi API key dan Webhook-nya.

1. **Midtrans (Pembayaran)**
    - Daftar/Masuk: [https://dashboard.midtrans.com/login](https://dashboard.midtrans.com/login)
    - Konfigurasi file `.env` Anda dengan `MIDTRANS_MERCHANT_ID`, `MIDTRANS_SERVER_KEY`, dan `MIDTRANS_CLIENT_KEY`.
    - Atur URL webhook/notification di dashboard Midtrans ke: `https://domain-anda.com/api/v1/callback/midtrans`

2. **Biteship (Pengiriman & Pelacakan)**
    - Daftar/Masuk: [https://biteship.com/en](https://biteship.com/en)
    - Konfigurasi file `.env` Anda dengan `BITESHIP_API_KEY`.
    - Atur URL webhook di dashboard Biteship ke: `https://domain-anda.com/api/v1/callback/biteship`

> **Catatan untuk Setup Webhook Biteship:**
> Saat pertama kali Anda menambahkan URL webhook di dashboard Biteship, mereka akan mengirimkan test payload untuk memverifikasi endpoint tersebut. Untuk memverifikasi dengan sukses, Anda **harus sementara** mengubah method `handle` di `app/Http/Controllers/Api/V1/Callback/BiteshipController.php` agar langsung me-return response 200.
>
> ```php
> public function handle(array $payload)
> {
>     Log::info('Biteship Callback Received', [
>         'request' => $payload,
>     ]);
>
>     // Return 200 sementara untuk verifikasi webhook Biteship
>     return response()->json([], 200);
>
>     // ... logika asli
> }
> ```
>
> Setelah terverifikasi, Anda dapat menghapus atau memindahkan `return` sementara tersebut agar webhook asli dapat diproses dengan benar.

---

---

## 🧰 Tech Stack

### Backend

| Package                  | Version | Kegunaan                     |
| ------------------------ | ------- | ---------------------------- |
| **PHP**                  | 8.4     | Runtime                      |
| **Laravel**              | 13      | Core framework               |
| **Laravel Folio**        | v1      | File-based page routing      |
| **Laravel Fortify**      | v1      | Authentication backend       |
| **Laravel Sanctum**      | v4      | API token authentication     |
| **Laravel Pulse**        | v1      | Application monitoring       |
| **Livewire**             | v4      | Reactive UI components       |
| **Spatie Permission**    | v6      | Role & permission management |
| **Spatie Media Library** | v11     | File & image management      |
| **Spatie Activity Log**  | v4      | User activity logging        |
| **Spatie Sluggable**     | v4      | Slug generation              |
| **Sqids**                | v0.5    | Short unique ID generation   |
| **Artesaos SEOTools**    | v1      | SEO meta, OpenGraph, JSON-LD |
| **Predis**               | v3      | Redis client                 |

### Frontend

| Package               | Version | Kegunaan                      |
| --------------------- | ------- | ----------------------------- |
| **Flux UI**           | v2      | Livewire UI component library |
| **Livewire Blaze**    | v1      | Blade component optimization  |
| **TailwindCSS**       | v4      | Utility-first CSS framework   |
| **TweakFlux**         | v1      | Flux UI deep theming          |
| **Jodit Text Editor** | v1      | Rich text editor (Livewire)   |

### Dev Tools

| Package           | Kegunaan                       |
| ----------------- | ------------------------------ |
| **Laravel Pint**  | Code style fixer               |
| **Pest PHP v4**   | Testing framework              |
| **Laravel Pail**  | Real-time log tailing          |
| **Laravel Sail**  | Docker development environment |
| **Debugbar**      | Request profiling              |
| **Laravel Boost** | AI-assisted development MCP    |

### Third-party Integrations

| Layanan      | Kegunaan                            |
| ------------ | ----------------------------------- |
| **Midtrans** | Payment gateway (Snap)              |
| **Biteship** | Shipping rates & real-time tracking |

---

## 📁 Project Structure

```text
├── app/
│   ├── Actions/
│   │   ├── Cms/            # CMS-related actions (CRUD untuk produk, toko, pengguna, dll.)
│   │   └── Ecommerce/      # Storefront actions (checkout, pengiriman, pembayaran, lokasi)
│   ├── Models/
│   │   ├── Product/        # Product, ProductFlat, ProductCategory, ProductAttribute, dll.
│   │   ├── Order/          # Order, OrderShop, OrderShopItem, OrderShopShipment
│   │   ├── Shop/           # Model Shop (toko)
│   │   ├── Payment/        # Model Payment (pembayaran)
│   │   ├── Location/       # Alamat pelanggan yang tersimpan
│   │   ├── Attribute/      # Grup atribut & nilai atribut
│   │   └── Menu/           # CMS navigation menus
│   └── Mail/
│       └── OrderPlaced.php # Email transaksional konfirmasi pesanan
├── resources/views/
│   ├── pages/              # Folio file-based routes
│   │   ├── index.blade.php         # Beranda
│   │   ├── explore/                # Katalog produk & halaman kategori
│   │   ├── product/                # Halaman detail produk
│   │   ├── cart.blade.php
│   │   ├── checkout.blade.php
│   │   ├── orders/                 # Riwayat pesanan, detail, pengecekan guest
│   │   ├── payment/                # Halaman pembayaran
│   │   ├── settings/               # Pengaturan akun (profil, password, 2FA, tampilan)
│   │   └── cms/                    # Halaman CMS / panel admin
│   └── components/
│       ├── ecommerce/      # Livewire ecommerce components (awalan ⚡)
│       └── layouts/        # Layout aplikasi (ecommerce publik, CMS)
└── database/
    ├── migrations/
    └── seeders/
```

---

## 🚀 Instalasi

### Persyaratan Sistem

- PHP >= 8.4
- Composer
- Node.js >= 20 + npm/yarn
- SQLite (bawaan) atau MySQL/PostgreSQL
- Redis (opsional, untuk optimasi cache/queue)

### Setup Cepat

```bash
# 1. Clone repositori
git clone https://github.com/karuhun-developer/ecommerce.git
cd ecommerce

# 2. Jalankan script all-in-one setup
composer run setup

# 3. Konfigurasi .env Anda (lihat bagian Konfigurasi Environment di bawah)
cp .env.example .env

# 4. Hubungkan storage (storage:link)
php artisan storage:link

# 5. Jalankan development server
composer run dev
```

> `composer run setup` akan secara otomatis menjalankan: `composer install`, menyalin `.env`, men-generate app key, menjalankan migrasi, `npm install`, dan `npm run build`.

### Setup Manual

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan storage:link
```

---

## ⚙️ Konfigurasi Environment

Variabel penting yang perlu disesuaikan di file `.env`:

```env
APP_NAME=NamaTokoAnda
APP_URL=http://localhost
APP_TIMEZONE=UTC
APP_DISPLAY_TIMEZONE=Asia/Jakarta

# Database (Default SQLite, ganti ke MySQL jika diperlukan)
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=ecommerce
# DB_USERNAME=root
# DB_PASSWORD=

# Queue — wajib untuk kirim email & background jobs
QUEUE_CONNECTION=database

# Payment Gateway — Midtrans
MIDTRANS_MERCHANT_ID=
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false

# Shipping — Biteship
BITESHIP_API_KEY=

# Mode toko tunggal (true = 1 toko, false = multi-shop / marketplace)
SINGLE_SHOP=true

# Mail
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=hello@yourstore.com
MAIL_FROM_NAME="${APP_NAME}"

# Redis (opsional)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

---

## 🌐 Halaman & Rute

### Storefront

| URL                    | Keterangan                       |
| ---------------------- | -------------------------------- |
| `/`                    | Beranda (Homepage)               |
| `/explore`             | Semua katalog produk             |
| `/explore/{category}`  | Produk dengan filter kategori    |
| `/product/{slug}`      | Detail produk                    |
| `/cart`                | Keranjang belanja                |
| `/checkout`            | Checkout                         |
| `/payment/{reference}` | Halaman pembayaran (noindex)     |
| `/orders`              | Pesanan saya (wajib login)       |
| `/orders/check`        | Cek pesanan guest via kode       |
| `/orders/{reference}`  | Detail pesanan (noindex)         |

### Pengaturan Akun

| URL                    | Keterangan                      |
| ---------------------- | ------------------------------- |
| `/settings/profile`    | Pengaturan profil               |
| `/settings/password`   | Ganti kata sandi                |
| `/settings/two-factor` | Autentikasi dua faktor (2FA)    |
| `/settings/appearance` | Pengaturan tampilan             |

### CMS / Admin

| URL                          | Keterangan                             |
| ---------------------------- | -------------------------------------- |
| `/cms/dashboard`             | Dashboard CMS                          |
| `/cms/product`               | Manajemen produk                       |
| `/cms/product/category`      | Manajemen kategori produk              |
| `/cms/attribute/group`       | Manajemen grup atribut                 |
| `/cms/attribute/attribute`   | Manajemen atribut                      |
| `/cms/shop`                  | Manajemen toko                         |
| `/cms/management/user`       | Manajemen pengguna                     |
| `/cms/management/role`       | Manajemen peran (role)                 |
| `/cms/management/permission` | Manajemen hak akses (permission)       |
| `/cms/management/menu`       | Navigation menu builder                |
| `/pulse`                     | Monitoring aplikasi (Laravel Pulse)    |

---

## 🧪 Testing

```bash
# Jalankan semua pengujian (tests)
php artisan test

# Jalankan test atau filter spesifik
php artisan test --filter=CheckoutTest

# Lakukan linting dengan Pint kemudian jalankan pengujian
composer run test
```

---

## 🛠️ Perintah Pengembangan (Dev Commands)

```bash
# Jalankan semua service dev secara bersamaan (server + queue + logs + vite)
composer run dev

# Format kode PHP menggunakan Pint
vendor/bin/pint

# Format hanya file yang berubah saja (dirty)
vendor/bin/pint --dirty

# Menampilkan log aplikasi secara real-time
php artisan pail

# Tampilkan semua route Folio (berbasis file)
php artisan folio:list

# Tampilkan semua route API/non-Folio yang terdaftar
php artisan route:list --except-vendor
```

---

## ❤️ Dukung Project Ini

Jika project ini bermanfaat, dukung pengembangan lebih lanjut:
- **Saweria**: [https://saweria.co/warukunai](https://saweria.co/warukunai)

Untuk custom fitur atau pembuatan project lainnya, silakan hubungi:
- **Telegram**: [https://t.me/bayurifkialgh](https://t.me/bayurifkialgh)

---

## 📄 License

Proyek ini adalah perangkat lunak open-source yang dilisensikan di bawah [lisensi MIT](LICENSE).
