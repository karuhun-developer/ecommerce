#!/usr/bin/env sh
set -eu

php artisan migrate --force --no-interaction

if [ ! -f storage/.docker-initialized ]; then
    php artisan db:seed --force --no-interaction
    touch storage/.docker-initialized
fi
