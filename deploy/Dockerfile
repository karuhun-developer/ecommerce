ARG PHP_BASE_IMAGE=php:8.4-fpm-bookworm
FROM composer:2 AS composer
FROM ${PHP_BASE_IMAGE} AS php-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip libfreetype6-dev libjpeg62-turbo-dev libpng-dev libzip-dev libicu-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 pdo_mysql mbstring gd intl zip bcmath pcntl opcache exif \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY deploy/php.ini /usr/local/etc/php/conf.d/ecommerce.ini
WORKDIR /var/www/html

FROM php-base AS vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-autoloader
COPY . .
RUN mkdir -p storage/app/public storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --no-dev --optimize --no-interaction

FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
COPY --from=vendor /var/www/html/vendor ./vendor
RUN npm run build

FROM php-base AS app
COPY --from=vendor /var/www/html /var/www/html
COPY --from=assets /app/public/build ./public/build
RUN ln -s /var/www/html/storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
CMD ["php-fpm", "-F"]

FROM nginx:stable-alpine AS web
COPY deploy/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
RUN mkdir -p /var/www/html/storage/app/public
