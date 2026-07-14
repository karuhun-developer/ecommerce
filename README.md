<h1 align="center">🛒 Ecommerce Platform</h1>

<p align="center">
  A full-featured multi-shop ecommerce platform built with Laravel 13, Livewire 4, and Flux UI.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.4-blue?logo=php" />
  <img src="https://img.shields.io/badge/Laravel-13-red?logo=laravel" />
  <img src="https://img.shields.io/badge/Livewire-4-purple" />
  <img src="https://img.shields.io/badge/TailwindCSS-v4-teal?logo=tailwindcss" />
  <img src="https://img.shields.io/badge/License-MIT-green" />
</p>

---

## ✨ Features

### 🛍️ Storefront (Customer)

| Feature | Status |
|---|---|
| Homepage with featured products & categories | ✅ Done |
| Product catalog with category filtering | ✅ Done |
| Product detail page with variants & attributes | ✅ Done |
| Shopping cart (session/auth) | ✅ Done |
| Checkout with address & shipping selection | ✅ Done |
| Payment via **Midtrans** (Snap) | ✅ Done |
| Order confirmation & email notification | ✅ Done |
| Guest order tracking by reference code | ✅ Done |
| Order history (authenticated users) | ✅ Done |
| Order detail page | ✅ Done |
| Dynamic SEO (OpenGraph, TwitterCard, JSON-LD) | ✅ Done |

### 🏪 CMS / Admin Dashboard

| Feature | Status |
|---|---|
| Dashboard overview | ✅ Done |
| Product management (CRUD + variants + attributes) | ✅ Done |
| Product category management | ✅ Done |
| Attribute & attribute group management | ✅ Done |
| Shop management | ✅ Done |
| User management | ✅ Done |
| Role & permission management (Spatie) | ✅ Done |
| Navigation menu builder | ✅ Done |
| Activity log viewer | ✅ Done |
| Laravel Pulse monitoring dashboard | ✅ Done |
| Log viewer (Opcodes Log Viewer) | ✅ Done |

### 🚧 In Progress / Roadmap

| Feature | Status |
|---|---|
| Biteship webhook integration (shipment tracking) | 🔄 Ongoing |
| Admin / Shop Owner Dashboard (role-specific view) | 🔄 Ongoing |
| Shop detail page (public storefront per shop) | 🔄 Ongoing |
| User profile page | 🔄 Ongoing |
| Transaction list page (admin view) | 🔄 Ongoing |

---

## 🧰 Tech Stack

### Backend

| Package | Version | Purpose |
|---|---|---|
| **PHP** | 8.4 | Runtime |
| **Laravel** | 13 | Core framework |
| **Laravel Folio** | v1 | File-based page routing |
| **Laravel Fortify** | v1 | Authentication backend |
| **Laravel Sanctum** | v4 | API token authentication |
| **Laravel Pulse** | v1 | Application monitoring |
| **Livewire** | v4 | Reactive UI components |
| **Spatie Permission** | v6 | Role & permission management |
| **Spatie Media Library** | v11 | File & image management |
| **Spatie Activity Log** | v4 | User activity logging |
| **Spatie Sluggable** | v4 | Slug generation |
| **Sqids** | v0.5 | Short unique ID generation |
| **Artesaos SEOTools** | v1 | SEO meta, OpenGraph, JSON-LD |
| **Predis** | v3 | Redis client |

### Frontend

| Package | Version | Purpose |
|---|---|---|
| **Flux UI** | v2 | Livewire UI component library |
| **Livewire Blaze** | v1 | Blade component optimization |
| **TailwindCSS** | v4 | Utility-first CSS framework |
| **TweakFlux** | v1 | Flux UI deep theming |
| **Jodit Text Editor** | v1 | Rich text editor (Livewire) |

### Dev Tools

| Package | Purpose |
|---|---|
| **Laravel Pint** | Code style fixer |
| **Pest PHP v4** | Testing framework |
| **Laravel Pail** | Real-time log tailing |
| **Laravel Sail** | Docker development environment |
| **Debugbar** | Request profiling |
| **Laravel Boost** | AI-assisted development MCP |

### Third-party Integrations

| Service | Purpose |
|---|---|
| **Midtrans** | Payment gateway (Snap) |
| **Biteship** | Shipping rates & real-time tracking |

---

## 📁 Project Structure

```
├── app/
│   ├── Actions/
│   │   ├── Cms/            # CMS-related actions (CRUD for products, shops, users, etc.)
│   │   └── Ecommerce/      # Storefront actions (checkout, shipping, payment, location)
│   ├── Models/
│   │   ├── Product/        # Product, ProductFlat, ProductCategory, ProductAttribute, etc.
│   │   ├── Order/          # Order, OrderShop, OrderShopItem, OrderShopShipment
│   │   ├── Shop/           # Shop model
│   │   ├── Payment/        # Payment model
│   │   ├── Location/       # Saved customer addresses
│   │   ├── Attribute/      # Attribute groups & values
│   │   └── Menu/           # CMS navigation menus
│   └── Mail/
│       └── OrderPlaced.php # Transactional order confirmation email
├── resources/views/
│   ├── pages/              # Folio file-based routes
│   │   ├── index.blade.php         # Homepage
│   │   ├── explore/                # Product catalog & category pages
│   │   ├── product/                # Product detail page
│   │   ├── cart.blade.php
│   │   ├── checkout.blade.php
│   │   ├── orders/                 # Order history, detail, guest check
│   │   ├── payment/                # Payment page
│   │   ├── settings/               # User settings (profile, password, 2FA, appearance)
│   │   └── cms/                    # CMS / admin pages
│   └── components/
│       ├── ecommerce/      # Livewire ecommerce components (⚡ prefix)
│       └── layouts/        # App layouts (public ecommerce, CMS)
└── database/
    ├── migrations/
    └── seeders/
```

---

## 🚀 Installation

### Requirements

- PHP >= 8.4
- Composer
- Node.js >= 20 + npm/yarn
- SQLite (default) or MySQL/PostgreSQL
- Redis (optional, for cache/queue optimization)

### Quick Setup

```bash
# 1. Clone the repository
git clone https://github.com/karuhun-developer/ecommerce.git
cd ecommerce

# 2. Run the all-in-one setup script
composer run setup

# 3. Configure your .env (see environment section below)
cp .env.example .env

# 4. Link storage
php artisan storage:link

# 5. Start the development server
composer run dev
```

> `composer run setup` will automatically run: `composer install`, copy `.env`, generate app key, run migrations, `npm install`, and `npm run build`.

### Manual Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan storage:link
```

---

## ⚙️ Environment Configuration

Key variables to configure in your `.env`:

```env
APP_NAME=YourStoreName
APP_URL=http://localhost
APP_TIMEZONE=UTC
APP_DISPLAY_TIMEZONE=Asia/Jakarta

# Database (SQLite by default, swap for MySQL if needed)
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=ecommerce
# DB_USERNAME=root
# DB_PASSWORD=

# Queue — required for emails & background jobs
QUEUE_CONNECTION=database

# Payment Gateway — Midtrans
MIDTRANS_MERCHANT_ID=
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false

# Shipping — Biteship
BITESHIP_API_KEY=

# Single-shop mode (true = one shop, false = multi-shop)
SINGLE_SHOP=true

# Mail
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=hello@yourstore.com
MAIL_FROM_NAME="${APP_NAME}"

# Redis (optional)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

---

## 🌐 Pages & Routes

### Storefront

| URL | Description |
|---|---|
| `/` | Homepage |
| `/explore` | All products catalog |
| `/explore/{category}` | Products filtered by category |
| `/product/{slug}` | Product detail |
| `/cart` | Shopping cart |
| `/checkout` | Checkout |
| `/payment/{reference}` | Payment page (noindex) |
| `/orders` | My orders (auth required) |
| `/orders/check` | Guest order lookup by reference |
| `/orders/{reference}` | Order detail (noindex) |

### Settings

| URL | Description |
|---|---|
| `/settings/profile` | Profile settings |
| `/settings/password` | Change password |
| `/settings/two-factor` | Two-factor authentication (2FA) |
| `/settings/appearance` | Appearance preferences |

### CMS / Admin

| URL | Description |
|---|---|
| `/cms/dashboard` | CMS dashboard |
| `/cms/product` | Product management |
| `/cms/product/category` | Product category management |
| `/cms/attribute/group` | Attribute group management |
| `/cms/attribute/attribute` | Attribute management |
| `/cms/shop` | Shop management |
| `/cms/management/user` | User management |
| `/cms/management/role` | Role management |
| `/cms/management/permission` | Permission management |
| `/cms/management/menu` | Navigation menu builder |
| `/pulse` | Application monitoring (Laravel Pulse) |

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run a specific test file or filter
php artisan test --filter=CheckoutTest

# Lint with Pint then run tests
composer run test
```

---

## 🛠️ Development Commands

```bash
# Start all dev services concurrently (server + queue + logs + vite)
composer run dev

# Format PHP code with Pint
vendor/bin/pint

# Format only changed files
vendor/bin/pint --dirty

# Tail application logs in real-time
php artisan pail

# List all registered routes
php artisan route:list --except-vendor
```

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).
