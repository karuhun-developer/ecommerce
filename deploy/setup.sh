#!/usr/bin/env bash
set -euo pipefail
umask 077

project_directory="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
export ECOMMERCE_ENV_FILE="${ECOMMERCE_ENV_FILE:-$project_directory/.env.docker}"
temporary_file=""
trap '[[ -z "$temporary_file" ]] || rm -f -- "$temporary_file"' EXIT

compose() {
    bash "$project_directory/deploy/compose.sh" "$@"
}

set_environment_value() {
    temporary_file="$(mktemp "$ECOMMERCE_ENV_FILE.XXXXXX")"
    sed "s|^$1=.*|$1=$2|" "$ECOMMERCE_ENV_FILE" > "$temporary_file"
    mv -- "$temporary_file" "$ECOMMERCE_ENV_FILE"
    temporary_file=""
}

docker compose version > /dev/null

if [[ ! -f "$ECOMMERCE_ENV_FILE" ]]; then
    cp -- "$project_directory/.env.docker.example" "$ECOMMERCE_ENV_FILE"
fi

for variable in DB_PASSWORD MYSQL_ROOT_PASSWORD; do
    if grep -q "^$variable=$" "$ECOMMERCE_ENV_FILE"; then
        password="$(od -An -N32 -tx1 /dev/urandom | tr -d ' \n')"
        set_environment_value "$variable" "$password"
    fi
done

compose build

if grep -q '^APP_KEY=$' "$ECOMMERCE_ENV_FILE"; then
    app_key="$(compose run --rm --no-deps app php artisan key:generate --show --no-ansi --no-interaction)"
    set_environment_value APP_KEY "$app_key"
fi

compose up -d --wait
compose ps
printf '\nDocker siap. Alamat aplikasi mengikuti APP_URL di %s.\n' "$ECOMMERCE_ENV_FILE"
