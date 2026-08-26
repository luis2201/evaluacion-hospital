# Despliegue en producción desde GitHub

## Requisitos

- Ubuntu/Debian actualizado con Apache 2.4, `mod_rewrite`, `mod_headers` y `mod_ssl`.
- PHP 8.3 con `cli`, `fpm` o módulo Apache, `mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd` y `fileinfo`.
- MySQL 8, Composer 2, Node.js 22/npm y Git.
- Dominio DNS apuntando al servidor y certificado TLS válido.
- Usuario de base de datos exclusivo con contraseña segura.

## Instalación inicial

```bash
sudo mkdir -p /var/www
sudo chown "$USER":www-data /var/www
cd /var/www
git clone https://github.com/luis2201/evaluacion-hospital.git
cd evaluacion-hospital
cp .env.production.example .env
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci --ignore-scripts
npm run build
php artisan key:generate
```

Editar `.env` con dominio, base de datos, correo y secretos reales. Nunca se debe versionar este archivo.

```bash
sudo chown -R "$USER":www-data /var/www/evaluacion-hospital
sudo chmod -R ug+rwX storage bootstrap/cache
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan production:check
```

## Apache y HTTPS

Copiar y adaptar `deploy/apache/evaluacion-hospital.conf.example`. El `DocumentRoot` debe terminar exactamente en `/public`.

Copiar también `deploy/php/evaluacion-hospital.ini.example` a la configuración de PHP para Apache y CLI, y reiniciar Apache. Los límites PHP y `LimitRequestBody` deben ser iguales o superiores a los límites documentales configurados por el administrador en la aplicación.

```bash
sudo a2enmod rewrite headers ssl
sudo cp deploy/apache/evaluacion-hospital.conf.example /etc/apache2/sites-available/evaluacion-hospital.conf
sudo a2ensite evaluacion-hospital
sudo apachectl configtest
sudo systemctl reload apache2
```

Emitir el certificado —por ejemplo con Certbot— antes de habilitar el bloque HTTPS. Verificar que `storage/app/private` nunca sea accesible por URL.

## Scheduler y cola

Copiar las unidades de `deploy/systemd`, ajustar rutas si es necesario y activar:

```bash
sudo cp deploy/systemd/*.service /etc/systemd/system/
sudo cp deploy/systemd/*.timer /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now evaluacion-hospital-scheduler evaluacion-hospital-queue
sudo systemctl enable --now evaluacion-hospital-backup.timer
```

## Despliegues posteriores

El script solo acepta un checkout limpio y realiza `git pull --ff-only` desde `main`:

```bash
sudo -u www-data APP_DIR=/var/www/evaluacion-hospital bash scripts/deploy-production.sh
APP_URL=https://evaluacion.example.edu.ec bash scripts/health-check.sh
```

## MySQL y triggers

Antes de migrar, el administrador de MySQL debe establecer de forma persistente:

```sql
SET PERSIST log_bin_trust_function_creators = 1;
```

Esto permite instalar los triggers versionados sin conceder privilegios `SUPER` al usuario de la aplicación.

## Respaldo y recuperación

El temporizador systemd ejecuta diariamente `scripts/backup-production.sh` fuera del árbol público. Ajustar `BACKUP_DIR` en la unidad si corresponde, copiar los respaldos a una ubicación externa cifrada y probar restauración periódicamente.

```bash
sudo BACKUP_DIR=/var/backups/evaluacion-hospital bash scripts/backup-production.sh
sudo bash scripts/restore-production.sh /var/backups/evaluacion-hospital/AAAAMMDDTHHMMSSZ CONFIRMAR_RESTAURACION
```

La restauración verifica SHA-256, conserva temporalmente la carpeta documental anterior y ejecuta `production:check` antes de volver a poner la aplicación en línea.

## Verificación final

1. `php artisan production:check` finaliza con código `0`.
2. `/up` responde correctamente por HTTPS.
3. Inicio de sesión, carga privada y descarga PDF funcionan.
4. Scheduler y cola aparecen activos en `systemctl`.
5. Apache no permite listar directorios ni acceder a `.env`, `storage` o `vendor`.
6. Existe un respaldo reciente verificado y almacenado fuera del servidor.
