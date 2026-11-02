#!/usr/bin/env bash
set -euo pipefail

project_directory="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
export ECOMMERCE_ENV_FILE="${ECOMMERCE_ENV_FILE:-$project_directory/.env.docker}"

if [[ ! -f "$ECOMMERCE_ENV_FILE" ]]; then
    printf 'File environment belum ada. Jalankan bash deploy/setup.sh terlebih dahulu.\n' >&2
    exit 1
fi

exec docker compose --project-directory "$project_directory" --env-file "$ECOMMERCE_ENV_FILE" -f "$project_directory/deploy/compose.yml" "$@"
