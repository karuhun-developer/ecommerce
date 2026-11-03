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
- **Pembayaran** — Midtrans & Paywuz.
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

`PAYMENT_GATEWAY`: `midtrans` / `paywuz`. Email: `MAIL_*`. Mode toko: `SINGLE_SHOP`.

| Provider | Environment                              | Webhook (POST)              |
| -------- | ---------------------------------------- | --------------------------- |
| Midtrans | `MIDTRANS_*`                             | `/api/v1/midtrans/callback` |
| Paywuz   | `PAYWUZ_*`                               | `/api/v1/paywuz/callback`   |
| Biteship | `BITESHIP_API_KEY`, `BITESHIP_WEBHOOK_*` | `/api/v1/biteship/callback` |

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
