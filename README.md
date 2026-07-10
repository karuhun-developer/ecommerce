# Laravel E-Commerce Application

A modern, robust e-commerce application built with the Laravel ecosystem, utilizing cutting-edge tools to deliver a fast and dynamic user experience.

## Tech Stack & Packages

This project leverages the following technologies and packages:

- **[Laravel](https://laravel.com/)** - The PHP framework for web artisans.
- **[Livewire](https://livewire.laravel.com/)** - Building dynamic interfaces without writing JavaScript.
- **[Laravel Folio](https://laravel.com/docs/folio)** - Page-based routing for Laravel.
- **[Livewire Flux (Flux UI)](https://fluxui.dev/)** - Beautiful, accessible UI components.
- **[Laravel Boost](https://boost.laravel.com/)** - Agentic guidelines and context.
- **[TweakFlux](https://github.com/joshcirre/tweakflux)** - Deep theming for Flux UI (`./vendor/bin/tweakflux apply {theme?}`).
- **[Spatie Permissions](https://spatie.be/docs/laravel-permission)** - Roles and permissions management.
- **[Spatie Media Library](https://spatie.be/docs/laravel-medialibrary)** - Attaching media files to Eloquent models.
- **[Spatie Activity Log](https://spatie.be/docs/laravel-activitylog)** - Logging user activities.
- **Payment Gateway**: Midtrans
- **Shipping & Logistics**: Biteship

## Installation

To get the project up and running locally, follow these steps:

```bash
git clone
cp .env.example .env
composer install
php artisan key:generate
php artisan storage:link
php artisan migrate
php artisan db:seed
yarn
yarn build
php artisan serve
```

## Features Overview (Completed)

- Shopping Cart functionality
- Dynamic Checkout process
- Real-time Shipping Rate calculation via Biteship
- Payment integration via Midtrans (QRIS & Virtual Accounts)
- Order detail and payment instruction pages

## To-Do List / Upcoming Features

- [ ] **Explore Page**: A page to discover various products and stores.
- [ ] **List Transaction Page**: View the master list of all transactions.
- [ ] **User Page**:
    - View personal transaction history
    - Update user profile details
    - Manage shipping addresses and locations
- [ ] **Admin / Shop Owner Order Page**: Interface for store owners to manage incoming orders.
- [x] **Midtrans Webhook**: Listen to real-time payment status updates automatically.
- [ ] **Email Notifications**: Send email notifications for order confirmations, shipping updates, and payment receipts.
- [ ] **Biteship Webhook**: Listen to real-time shipping and tracking updates.
- [ ] **Admin / Shop Owner Dashboard**: Overview of store performance, sales, and analytics.
- [ ] **Shop Detail Page**: View a specific store's profile, product catalog, and information.
