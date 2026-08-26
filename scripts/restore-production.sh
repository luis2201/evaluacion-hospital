#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/evaluacion-hospital}"
BACKUP_PATH="${1:-}"
CONFIRMATION="${2:-}"

test -n "$BACKUP_PATH" || { echo "Uso: $0 /ruta/respaldo CONFIRMAR_RESTAURACION" >&2; exit 1; }
BACKUP_PATH="$(realpath "$BACKUP_PATH")"
test "$CONFIRMATION" = "CONFIRMAR_RESTAURACION" || { echo "Confirmación inválida." >&2; exit 1; }
test -f "$BACKUP_PATH/database.sql.gz" -a -f "$BACKUP_PATH/private-documents.tar.gz" -a -f "$BACKUP_PATH/SHA256SUMS" || { echo "Respaldo incompleto." >&2; exit 1; }

cd "$BACKUP_PATH"
sha256sum --check SHA256SUMS
cd "$APP_DIR"
read_env() { sed -n "s/^$1=//p" .env | tail -n1 | sed 's/^"//;s/"$//'; }
export MYSQL_PWD="$(read_env DB_PASSWORD)"

php artisan down --retry=60
trap 'php artisan up' EXIT
gzip -dc "$BACKUP_PATH/database.sql.gz" | mysql -h "$(read_env DB_HOST)" -P "$(read_env DB_PORT)" -u "$(read_env DB_USERNAME)" "$(read_env DB_DATABASE)"

RESTORE_TMP="$(mktemp -d)"
trap 'rm -rf -- "$RESTORE_TMP"; php artisan up' EXIT
tar -C "$RESTORE_TMP" -xzf "$BACKUP_PATH/private-documents.tar.gz"
test -d "$RESTORE_TMP/private" || { echo "Archivo documental inválido." >&2; exit 1; }
mv "$APP_DIR/storage/app/private" "$APP_DIR/storage/app/private.before-restore-$RANDOM"
mv "$RESTORE_TMP/private" "$APP_DIR/storage/app/private"
php artisan optimize:clear
php artisan production:check
php artisan up
unset MYSQL_PWD
rm -rf -- "$RESTORE_TMP"
trap - EXIT
echo "Restauración completada desde $BACKUP_PATH"
