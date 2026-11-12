<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset=".github/logo-light.png">
    <img src="public/logo.png" alt="Ecommerce" width="72">
  </picture>
</p>

<h1 align="center">Ecommerce</h1>

<p align="center">
  <strong>Buka toko. Terima pembayaran. Kirim pesanan.</strong><br>
  E-commerce multi-toko dengan storefront dan CMS dalam satu aplikasi.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.4.1%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.4.1+">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/Docker-recommended-2496ED?style=flat-square&logo=docker&logoColor=white" alt="Docker recommended">
</p>

<p align="center">
  <a href="#fitur">Fitur</a> · <a href="#docker-recommended">Quick start</a> · <a href="#integrasi">Integrasi</a> · <a href="https://saweria.co/warukunai">Dukung</a>
</p>

## Fitur

- **Storefront** — multi-toko, katalog, keranjang, dan checkout.
- **CMS** — kelola produk, pesanan, dan konten website.
- **Pembayaran** — Midtrans, Paywuz & KaruhunQRIS (QRIS GoPay/ShopeePay).
- **Pengiriman** — JNE, J&T, **Gojek (GoSend) & GrabExpress** via Biteship.

**UI:** Livewire 4 · Flux UI 2 · Tailwind CSS 4.

## Docker (Recommended)

Docker Engine/Desktop + Compose v2 · Bash (Linux/macOS/WSL).

```bash
git clone https://github.com/karuhun-developer/ecommerce.git
cd ecommerce
bash deploy/setup.sh
```

**Buka:** `http://localhost:8080` · Login: `/login` · CMS: `/cms/dashboard`.

**Admin awal:** `superadmin@superadmin.com` / `password` — ganti untuk production.

Nginx · PHP-FPM · MySQL · worker · scheduler. APP_KEY, migrasi, dan seed awal otomatis.

<details>
<summary><strong>Konfigurasi, update & backup</strong></summary>

Konfigurasi: `.env.docker` dari [.env.docker.example](.env.docker.example). Port/bind: `APP_PORT` / `APP_BIND_ADDRESS`.

Production: HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` sesuai domain.

```bash
bash deploy/compose.sh logs -f
bash deploy/compose.sh down

# Update
git pull
bash deploy/compose.sh build
bash deploy/compose.sh up -d --force-recreate --wait
bash deploy/compose.sh exec app php artisan db:seed --class=WebsiteContentSeeder --force --no-interaction
```

Backup: `.env.docker`, volume `mysql_data`, dan `app_storage`. Pertahankan APP_KEY saat update.

</details>

<details>
<summary><strong>Instalasi manual (baru)</strong></summary>

PHP 8.4.1+, Composer, Node.js 20.19+ / 22.12+, SQLite/MySQL.

```bash
cp .env.example .env
touch database/database.sqlite
composer run setup
php artisan db:seed --no-interaction
php artisan storage:link --no-interaction
composer run dev
```

Konfigurasi: [.env.example](.env.example). Default: SQLite.

</details>

## Integrasi

`PAYMENT_GATEWAY`: `midtrans` / `paywuz` / `karuhunqris`. Email: `MAIL_*`. Mode toko: `SINGLE_SHOP`.

| Provider | Environment                              | Webhook / verifikasi              |
| -------- | ---------------------------------------- | --------------------------- |
| Midtrans | `MIDTRANS_*`                             | `/api/v1/midtrans/callback` |
| Paywuz   | `PAYWUZ_*`                               | `/api/v1/paywuz/callback`   |
| KaruhunQRIS | `DYNAMIC_QRIS_*`                       | Polling backend setiap 15 detik; scheduler tiap menit |
| Biteship | `BITESHIP_API_KEY`, `BITESHIP_WEBHOOK_*` | `/api/v1/biteship/callback` |

### KaruhunQRIS

Daftar dan dapatkan API key merchant di [KaruhunQRIS](https://qris.karuhundeveloper.com). Integrasi memakai package [Dynamic QRIS PHP Client](https://github.com/bayurifkialghifari/dynamic-qris-php-client) yang sudah terpasang melalui Composer.

```env
PAYMENT_GATEWAY=karuhunqris
DYNAMIC_QRIS_API_KEY=your-merchant-api-key
DYNAMIC_QRIS_BASE_URL=https://qris.karuhundeveloper.com
DYNAMIC_QRIS_TIMEOUT=10
DYNAMIC_QRIS_CONNECT_TIMEOUT=3
```

Jalankan `php artisan migrate --no-interaction` setelah update. Pilih QRIS GoPay atau QRIS ShopeePay di halaman pembayaran; akun provider harus sudah dikonfigurasi di KaruhunQRIS. Bayar **nominal final yang ditampilkan**, termasuk kode unik. Biaya pembuatan QRIS dipotong dari saldo merchant.

Pembayaran disimpan di database dengan token terenkripsi dan idempotency key tetap untuk retry. Halaman mengecek status setiap **15 detik** selama pending. Batas pembayaran maksimal **15 menit** sejak percobaan dibuat, atau lebih singkat sesuai expiry provider. Status sukses mengonfirmasi pesanan dan mengirim notifikasi sekali.

Scheduler menjalankan `payments:reconcile-qris` setiap menit, termasuk pemeriksaan terakhir saat kedaluwarsa. Gangguan API mempertahankan status terakhir dan dicoba kembali; tidak dianggap sebagai pembayaran gagal. Docker sudah menjalankan scheduler. Untuk instalasi manual, jalankan `php artisan schedule:work` saat development atau pasang cron `* * * * * cd /path/to/ecommerce && php artisan schedule:run >> /dev/null 2>&1` di production.

## Development

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

---

## ❤️ Dukung Project Ini

Jika project ini bermanfaat, dukung pengembangan lebih lanjut:

Saweria: [https://saweria.co/warukunai](https://saweria.co/warukunai)

Mau custom project? Hubungi [Telegram](https://t.me/bayurifkialgh).
