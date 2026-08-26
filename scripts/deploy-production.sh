#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/evaluacion-hospital}"
PHP_BIN="${PHP_BIN:-/usr/bin/php}"
COMPOSER_BIN="${COMPOSER_BIN:-/usr/local/bin/composer}"
NPM_BIN="${NPM_BIN:-/usr/bin/npm}"

cd "$APP_DIR"
test -f .env || { echo "Falta $APP_DIR/.env" >&2; exit 1; }
test "$(git status --porcelain)" = "" || { echo "El checkout contiene cambios locales; despliegue cancelado." >&2; exit 1; }

git fetch --prune origin
git checkout main
git pull --ff-only origin main

"$COMPOSER_BIN" install --no-dev --prefer-dist --no-interaction --optimize-autoloader
"$NPM_BIN" ci --ignore-scripts
"$NPM_BIN" run build

"$PHP_BIN" artisan down --retry=60 || true
trap '"$PHP_BIN" artisan up' EXIT
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan production:check
"$PHP_BIN" artisan queue:restart
"$PHP_BIN" artisan up
trap - EXIT

echo "Despliegue completado: $(git rev-parse --short HEAD)"
