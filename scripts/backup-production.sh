#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

APP_DIR="${APP_DIR:-/var/www/evaluacion-hospital}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/evaluacion-hospital}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
TARGET="$BACKUP_DIR/$STAMP"

cd "$APP_DIR"
test -f .env || { echo "Falta $APP_DIR/.env" >&2; exit 1; }
mkdir -p "$TARGET"

read_env() { sed -n "s/^$1=//p" .env | tail -n1 | sed 's/^"//;s/"$//'; }
DB_HOST_VALUE="$(read_env DB_HOST)"
DB_PORT_VALUE="$(read_env DB_PORT)"
DB_NAME_VALUE="$(read_env DB_DATABASE)"
DB_USER_VALUE="$(read_env DB_USERNAME)"
export MYSQL_PWD="$(read_env DB_PASSWORD)"

mysqldump --single-transaction --routines --triggers --events --hex-blob \
    -h "$DB_HOST_VALUE" -P "${DB_PORT_VALUE:-3306}" -u "$DB_USER_VALUE" "$DB_NAME_VALUE" \
    | gzip -9 > "$TARGET/database.sql.gz"
tar -C "$APP_DIR/storage/app" -czf "$TARGET/private-documents.tar.gz" private
sha256sum "$TARGET/database.sql.gz" "$TARGET/private-documents.tar.gz" > "$TARGET/SHA256SUMS"
unset MYSQL_PWD

find "$BACKUP_DIR" -mindepth 1 -maxdepth 1 -type d -mtime "+$RETENTION_DAYS" -print -exec rm -rf -- {} +
echo "Respaldo creado en $TARGET"
