# Ecommerce

E-commerce multi-toko: katalog, checkout, pembayaran, pengiriman, CMS, dan konten website.

**Stack:** PHP 8.4.1+, Laravel 13, Livewire 4, Flux UI 2, Tailwind CSS 4.

## Docker (Recommended)

Docker Engine/Desktop + Compose v2+. Jalankan lewat Bash (Linux/macOS/WSL).

```bash
git clone https://github.com/karuhun-developer/ecommerce.git
cd ecommerce
bash deploy/setup.sh
```

Aplikasi: `http://localhost:8080` · Login: `/login` · Admin: `/cms/dashboard`.

Akun awal: `superadmin@superadmin.com` / `password`. Ganti password untuk production.

Stack: Nginx, PHP-FPM, MySQL, worker, scheduler. APP_KEY, migrasi, dan seed awal otomatis.

Konfigurasi: `.env.docker`, dibuat dari [.env.docker.example](.env.docker.example). Port/bind: `APP_PORT` / `APP_BIND_ADDRESS`.

Production: HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` sesuai domain.

### Kelola / Update

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

## Manual (Instalasi Baru)

PHP 8.4.1+, Composer, Node.js 20.19+ / 22.12+, SQLite/MySQL.

```bash
cp .env.example .env
touch database/database.sqlite
composer run setup
php artisan db:seed --no-interaction
php artisan storage:link --no-interaction
composer run dev
```

Konfigurasi: [.env.example](.env.example). Default database: SQLite.

## Integrasi

`PAYMENT_GATEWAY`: `midtrans` / `paywuz`. Email: `MAIL_*`. Mode toko: `SINGLE_SHOP`.

| Provider | Environment | Webhook (POST) |
| --- | --- | --- |
| Midtrans | `MIDTRANS_*` | `/api/v1/midtrans/callback` |
| Paywuz | `PAYWUZ_*` | `/api/v1/paywuz/callback` |
| Biteship | `BITESHIP_API_KEY`, `BITESHIP_WEBHOOK_*` | `/api/v1/biteship/callback` |

## Development

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

## Dukungan

[Saweria](https://saweria.co/warukunai) · [Telegram](https://t.me/bayurifkialgh)

Lisensi: MIT.
