<h1 align="center">🛒 Ecommerce Platform</h1>

<p align="center">
  Platform e-commerce multi-toko (multi-shop) berfitur lengkap yang dibangun dengan Laravel 13, Livewire 4, dan Flux UI.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.4.1-blue?logo=php" />
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
| Bagikan produk via native share, clipboard, atau salin manual | ✅ Selesai |
| Keranjang belanja (session/auth)                     | ✅ Selesai |
| Checkout dengan pilihan alamat & pengiriman          | ✅ Selesai |
| Pembayaran via **Midtrans** (QRIS/VA) atau **Paywuz** | ✅ Selesai |
| Konfirmasi pesanan & email transaksional sesuai tema website | ✅ Selesai |
| Lacak pesanan guest via kode referensi               | ✅ Selesai |
| Riwayat pesanan (pengguna login)                     | ✅ Selesai |
| Halaman detail pesanan                               | ✅ Selesai |
| Banner terjadwal, menu header, dan footer dinamis     | ✅ Selesai |
| Halaman konten publik dengan HTML yang disanitasi     | ✅ Selesai |
| Dynamic SEO (OpenGraph, TwitterCard, JSON-LD)        | ✅ Selesai |

### 🏪 CMS / Admin Dashboard

| Fitur                                                   | Status  |
| ------------------------------------------------------- | ------- |
| Dashboard dengan filter periode/toko, tren omzet, status pesanan, produk terlaris, stok rendah, dan transaksi terbaru | ✅ Selesai |
| Manajemen produk (CRUD + varian + atribut)              | ✅ Selesai |
| Manajemen kategori produk                               | ✅ Selesai |
| Manajemen atribut & grup atribut                        | ✅ Selesai |
| Manajemen toko (Shop management)                        | ✅ Selesai |
| Manajemen pengguna (User management)                    | ✅ Selesai |
| Manajemen peran & hak akses (Role & permission)         | ✅ Selesai |
| Navigation menu builder untuk sidebar CMS               | ✅ Selesai |
| Identitas website dan tautan Instagram, website, WhatsApp | ✅ Selesai |
| Banner homepage dengan layout kartu dan CRUD modal Flux | ✅ Selesai |
| Menu header dengan posisi kiri/kanan dan tujuan halaman/URL | ✅ Selesai |
| Grup footer dan halaman custom dengan editor rich text  | ✅ Selesai |
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

---

## 🔗 Integrasi Pembayaran, Pengiriman, dan Webhook

Pilih gateway aktif melalui `PAYMENT_GATEWAY=midtrans` atau `PAYMENT_GATEWAY=paywuz`. Konfigurasi driver berada di `config/payment.php`; metode pembayaran dan respons provider dinormalisasi melalui `App\Contracts\Payments\PaymentGateway`.

| Integrasi | Konfigurasi `.env` | Endpoint callback (POST) |
| --------- | ------------------ | ------------------------ |
| Midtrans | `MIDTRANS_MERCHANT_ID`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION` | `/api/v1/midtrans/callback` |
| Paywuz | `PAYWUZ_API_KEY`, `PAYWUZ_BASE_URL` | `/api/v1/paywuz/callback` |
| Biteship | `BITESHIP_API_KEY`, `BITESHIP_WEBHOOK_HEADER_KEY`, `BITESHIP_WEBHOOK_HEADER_SECRET` | `/api/v1/biteship/callback` |

Gunakan domain publik aplikasi sebagai awalan endpoint di dashboard provider. Jalur callback terdaftar di `routes/api/v1/callback.php`.

Callback Midtrans memvalidasi signature transaksi. Paywuz memvalidasi signature HMAC dari raw body serta header `X-Paywuz-Signature`, `X-Paywuz-Event`, dan `X-Paywuz-Delivery`. Untuk Biteship, samakan nama header dan secret di dashboard provider dengan dua variabel `BITESHIP_WEBHOOK_*`; konfigurasi atau header yang tidak valid akan mendapat respons 401. Event Biteship selain `order.status` diabaikan setelah autentikasi berhasil.

---

## 🧰 Tech Stack

### Backend

| Package                  | Version | Kegunaan                     |
| ------------------------ | ------- | ---------------------------- |
| **PHP**                  | >=8.4.1 | Runtime untuk dependency terkunci |
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
| **Symfony HTML Sanitizer** | v8    | Sanitasi HTML halaman, produk, dan varian |

### Frontend

| Package               | Version | Kegunaan                      |
| --------------------- | ------- | ----------------------------- |
| **Flux UI**           | v2      | Livewire UI component library |
| **Livewire Blaze**    | v1      | Blade component optimization  |
| **TailwindCSS**       | v4      | Utility-first CSS framework   |
| **TweakFlux**         | v1      | Flux UI deep theming          |
| **Jodit Text Editor** | v1      | Rich text editor (Livewire)   |
| **Vite**              | v8      | Frontend bundling             |

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
| **Midtrans** | Payment gateway (QRIS & virtual account) |
| **Paywuz**   | Payment gateway alternatif          |
| **Biteship** | Shipping rates & real-time tracking |

---

## 📁 Project Structure

```text
├── app/
│   ├── Actions/
│   │   ├── Api/V1/Callback/ # Pemrosesan webhook melalui DTO
│   │   ├── Auth/           # Action autentikasi
│   │   ├── Cms/            # CMS-related actions (CRUD untuk produk, toko, pengguna, dll.)
│   │   └── Ecommerce/      # Storefront actions (checkout, pengiriman, pembayaran, lokasi)
│   ├── Data/               # DTO final readonly untuk input/output action
│   ├── Livewire/Forms/     # State, validasi, dan konversi form ke DTO
│   ├── Http/Controllers/Api/V1/Callback/ # Boundary HTTP webhook
│   ├── Contracts/Payments/ # Kontrak payment gateway
│   ├── Services/
│   │   ├── Content/        # Sanitasi rich text
│   │   └── Payments/       # Driver Midtrans/Paywuz dan perhitungan biaya
│   ├── Models/
│   │   ├── Content/        # Banner, Page, HeaderLink, FooterGroup
│   │   ├── Product/        # Product, ProductFlat, ProductCategory, ProductAttribute, dll.
│   │   ├── Order/          # Order, OrderShop, OrderShopItem, OrderShopShipment
│   │   ├── Shop/           # Model Shop (toko)
│   │   ├── Payment/        # Model Payment (pembayaran)
│   │   ├── Location/       # Alamat pelanggan yang tersimpan
│   │   ├── Attribute/      # Grup atribut & nilai atribut
│   │   ├── Setting/        # Identitas storefront
│   │   └── Menu/           # CMS navigation menus
│   └── Mail/
│       └── ...             # OrderPlaced, OrderPaid, OrderPaymentFailed, OrderDelivered
├── resources/views/
│   ├── pages/              # Folio file-based routes
│   │   ├── index.blade.php         # Beranda
│   │   ├── explore/                # Katalog produk & halaman kategori
│   │   ├── product/                # Halaman detail produk
│   │   ├── cart.blade.php
│   │   ├── checkout.blade.php
│   │   ├── orders/                 # Riwayat pesanan, detail, pengecekan guest
│   │   ├── payment/                # Halaman pembayaran
│   │   ├── pages/[slug].blade.php  # Halaman konten publik
│   │   ├── settings/               # Pengaturan akun (profil, password, 2FA, tampilan)
│   │   └── cms/                    # Halaman CMS / panel admin
│   └── components/
│       ├── cms/            # Komponen admin dengan Flux UI
│       ├── ecommerce/      # Livewire ecommerce components (awalan ⚡)
│       └── layouts/        # Layout aplikasi (ecommerce publik, CMS)
└── database/
    ├── migrations/
    └── seeders/
```

### Konvensi Kode

Alur mutasi: **Controller/komponen Livewire → validasi → DTO → Action `handle()`**. DTO berada di `app/Data` dan didefinisikan sebagai `final readonly`. Action hanya mengekspos public method `handle()` selain constructor, menerima parameter bertipe DTO/model, dan menggunakan actor eksplisit ketika membutuhkan otorisasi. `Request` dan akses `auth()` ditangani di boundary controller/komponen.

Form Livewire yang dipisahkan berada di `app/Livewire/Forms`. Komponen CMS mengikuti pola Product/Attributes: komponen Flux yang tersedia, pencarian di sisi kanan, dan modal create/update untuk pengelolaan konten. Rich text halaman, deskripsi produk, dan varian dibersihkan oleh `App\Services\Content\SanitizeHtml` saat ditulis maupun dibaca dari data lama.

---

## 🚀 Instalasi

### Persyaratan Sistem

- PHP >= 8.4.1 sesuai dependency di `composer.lock`
- Composer
- Node.js 20.19+ atau 22.12+ sesuai kebutuhan Vite 8, dengan npm
- SQLite (bawaan) atau MySQL/PostgreSQL
- Redis (opsional, untuk optimasi cache/queue)

### Setup Cepat

```bash
# 1. Clone repositori
git clone https://github.com/karuhun-developer/ecommerce.git
cd ecommerce

# 2. Buat .env pada instalasi baru, lalu sesuaikan koneksi database
cp .env.example .env

# 3. Siapkan file database jika memakai SQLite
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"

# 4. Install dependency, buat app key, migrasi, dan build frontend
composer run setup

# 5. Isi database baru dengan akun, role, sidebar, toko, dan konten default
php artisan db:seed --no-interaction
php artisan storage:link --no-interaction

# 6. Jalankan development server
composer run dev
```

`composer run setup` menjalankan `composer install`, membuat `.env` jika belum ada, men-generate app key, migrasi, `npm install`, dan `npm run build`. Script ini belum menjalankan seeder atau `storage:link`. Jalankan pada instalasi baru; untuk aplikasi yang sudah berisi data, gunakan langkah pembaruan di bawah.

`composer run dev` menyalakan server Laravel, queue listener, Pail, dan Vite. Akun admin bawaan dari `UserSeeder`: `superadmin@superadmin.com` dengan password `password`. Login melalui `/login`, lalu buka `/cms/dashboard`. Ganti password akun bawaan sebelum aplikasi dipakai publik.

### Setup Manual

```bash
cp .env.example .env
# Sesuaikan .env dan siapkan database sebelum migrasi
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
composer install
php artisan key:generate --no-interaction
php artisan migrate --seed --no-interaction
npm install
npm run build
php artisan storage:link --no-interaction
composer run dev
```

### Pembaruan Instalasi yang Sudah Ada

Setelah mengambil perubahan kode, jalankan:

```bash
composer install
php artisan migrate --no-interaction
php artisan db:seed --class=WebsiteContentSeeder --no-interaction
npm install
npm run build
php artisan optimize:clear --no-interaction
```

Pertahankan `.env` dan `APP_KEY` yang sudah dipakai. Seeder konten dapat dijalankan ulang untuk menambahkan default yang hilang tanpa menimpa konten, status, dan urutan yang sudah diedit admin. Gunakan seeder spesifik ini untuk pembaruan konten; `DatabaseSeeder` juga menjalankan seeder akun, toko, serta sidebar lainnya dan membersihkan cache.

---

## ⚙️ Konfigurasi Environment

Variabel penting yang perlu disesuaikan di file `.env`:

```env
APP_NAME=Ecommerce
APP_URL=http://localhost:8000
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

# Payment Gateway — pilih midtrans atau paywuz
PAYMENT_GATEWAY=midtrans
MIDTRANS_MERCHANT_ID=
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false

PAYWUZ_API_KEY=
PAYWUZ_BASE_URL=https://api.paywuz.id/v1
PAYWUZ_CONNECT_TIMEOUT=3
PAYWUZ_TIMEOUT=10

# Shipping — Biteship
BITESHIP_API_KEY=
BITESHIP_WEBHOOK_HEADER_KEY=
BITESHIP_WEBHOOK_HEADER_SECRET=

# Mode toko tunggal (true = 1 toko, false = multi-shop / marketplace)
SINGLE_SHOP=true

# Mail — log untuk lokal; ganti smtp dan lengkapi kredensial untuk pengiriman
MAIL_MAILER=log
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

Referensi lengkap variabel tersedia di `.env.example`. Aplikasi menggunakan database untuk session, cache, dan queue secara bawaan. Jalankan queue worker/listener untuk memproses email; listener sudah termasuk dalam `composer run dev`.

---

## Kelola Konten Website

Sidebar **Konten Website** memakai urutan berikut. Akses pengelolaannya membutuhkan permission `manageWebsiteContent`; `WebsiteContentSeeder` memberikannya kepada role `superadmin` yang sudah ada.

| Menu | Fungsi |
| ---- | ------ |
| Identitas Website | Nama brand, tagline, dan tautan Instagram/website/WhatsApp untuk storefront |
| Banner Homepage | Gambar, alt text, judul, subtitle, CTA, status aktif, urutan, dan jadwal tampil; create/update memakai modal Flux |
| Menu Header | Label, posisi kiri/kanan, urutan, status aktif, dan tujuan halaman custom atau URL |
| Grup Footer | Nama grup, status aktif, dan urutan kolom footer |
| Menu Footer | Buat/edit halaman custom, isi editor, slug, status publikasi, grup footer, dan urutan tautan |

Halaman custom diakses melalui `/pages/{slug}`. Isi konten bisa dipakai untuk informasi perusahaan, bantuan, promo, atau Hak Kekayaan Intelektual (HAKI). Untuk membuat halaman yang hanya ditautkan di header, kosongkan grup footer lalu pilih halaman tersebut sebagai tujuan Menu Header. Halaman draft tidak muncul pada navigasi publik.

Banner mendukung JPG/JPEG, PNG, dan WebP hingga 5 MB. Pengaturan jadwal dan status aktif menentukan banner yang muncul di homepage. Blok unduh aplikasi `Ecommerce.` dengan badge Play Store/App Store tetap berupa konten statis di footer.

### Default Seeder Konten

```bash
php artisan db:seed --class=WebsiteContentSeeder --no-interaction
```

Seeder ini menjalankan `FooterGroupSeeder`, `PageSeeder`, dan `HeaderLinkSeeder` secara berurutan:

| Default | Isi |
| ------- | --- |
| 3 grup footer | Ecommerce, Beli, Jual |
| 8 menu footer | Tentang Ecommerce, Hak Kekayaan Intelektual, Karir, Blog, Tagihan & Top Up, Tukar Tambah Handphone, Pusat Edukasi Seller, Daftar Official Store |
| 4 menu header | Kiri: Tentang Ecommerce, Mitra Ecommerce. Kanan: Promo, Bantuan |

Total 11 halaman dipublikasikan sebagai default; 8 ditempatkan di footer. Teks awal adalah konten contoh untuk diedit admin. Seeder juga memasang submenu Konten Website pada sidebar superadmin yang sudah ada dan menyegarkan cache menu/permission.

---

## 🌐 Halaman & Rute

### Storefront

| URL                    | Keterangan                       |
| ---------------------- | -------------------------------- |
| `/`                    | Beranda (Homepage)               |
| `/explore`             | Semua katalog produk             |
| `/explore/{category}`  | Produk dengan filter kategori    |
| `/product/{slug}`      | Detail produk                    |
| `/pages/{slug}`        | Halaman konten yang dipublikasikan |
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
| `/cms/order`                 | Manajemen pesanan                      |
| `/cms/review`                | Moderasi ulasan                         |
| `/cms/content/identity`      | Identitas Website                      |
| `/cms/content/banners`       | Banner Homepage                        |
| `/cms/content/header-links`  | Menu Header                            |
| `/cms/content/footer-groups` | Grup Footer                            |
| `/cms/content/pages`         | Menu Footer / halaman custom           |
| `/cms/management/user`       | Manajemen pengguna                     |
| `/cms/management/role`       | Manajemen peran (role)                 |
| `/cms/management/permission` | Manajemen hak akses (permission)       |
| `/cms/management/menu`       | Navigation menu builder                |
| `/pulse`                     | Monitoring aplikasi (Laravel Pulse)    |

---

## 🧪 Testing

```bash
# Jalankan semua pengujian
php artisan test --compact

# Jalankan pengujian yang sesuai dengan perubahan
php artisan test --compact tests/Feature/Content
php artisan test --compact tests/Feature/Architecture/ActionContractTest.php
php artisan test --compact tests/Feature/ProductHtmlSanitizationTest.php
php artisan test --compact tests/Feature/Mail/TransactionalEmailThemeTest.php

# Lakukan linting dengan Pint kemudian jalankan pengujian
composer run test
```

Pest dipakai untuk feature/unit test dan test komponen yang berada bersama file Livewire (`*.test.php`). Test arsitektur menjaga kontrak DTO dan public method action. Test konten mencakup CRUD, otorisasi, publikasi, navigasi, sanitasi HTML, dan seeder yang dapat dijalankan ulang.

Pada verifikasi refactor ini, test fitur terkait lolos. Suite penuh mencatat 397 dari 415 test lolos; 18 error/kegagalan terdapat pada `tests/Feature/PaymentCreationSecurityTest.php` dan `resources/views/components/ecommerce/⚡payment/payment.test.php`, terkait ekspektasi alur payment yang tidak berubah dalam refactor ini.

---

## 🛠️ Perintah Pengembangan (Dev Commands)

```bash
# Jalankan semua service dev secara bersamaan (server + queue + logs + vite)
composer run dev

# Format kode PHP menggunakan Pint
vendor/bin/pint

# Format hanya file yang berubah saja (dirty)
vendor/bin/pint --dirty --format agent

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

Proyek ini menggunakan lisensi MIT sebagaimana tercantum di `composer.json`.
