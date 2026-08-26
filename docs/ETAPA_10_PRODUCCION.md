# Etapa 10 — Fortalecimiento y producción

La aplicación se despliega a partir de la clonación del repositorio GitHub. La etapa incorpora:

- Configuración de producción sin secretos versionados.
- VirtualHost Apache con HTTPS y raíz en `public`.
- CSP, HSTS, protección contra marcos, MIME sniffing y caché de vistas sensibles.
- Comando `production:check` con salida apta para automatización.
- Despliegue idempotente desde `main` y checkout limpio.
- Servicios systemd para scheduler y cola.
- Respaldo de MySQL, triggers y documentos privados con SHA-256.
- Restauración confirmada, verificable y recuperable.
- Health check HTTP/HTTPS.
- CI sobre PHP 8.3, MySQL 8.4 y Node.js 22.
- Manual técnico, despliegue y manual de usuario.

Las rutas, dominio, certificados y credenciales de producción se completan exclusivamente en el servidor.
