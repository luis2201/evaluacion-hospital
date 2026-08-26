# Evaluación del Hospital de Simulación

Sistema web monolítico institucional para gestionar la evaluación de calidad del Hospital de Simulación.

## Base tecnológica

- Laravel 13 y PHP 8.3.
- MySQL 8.
- Blade y Tailwind CSS 4.
- Vite para recursos frontend.
- Apache con `public` como raíz del sitio.
- Documentos en almacenamiento privado.

## Estado actual

La aplicación contiene el dominio MySQL, autenticación, seguridad, administración versionada del instrumento MEC-SIM, creación/configuración de procesos, autoevaluación por dominio, gestión documental privada, revisión con subsanaciones, resultados ponderados, configuración diferenciada por roles, reportes PDF, auditoría y notificaciones internas. Las etapas 1 a 9 están implementadas y documentadas.

El esquema SQL revisado se conserva en `database/schema/evaluacion_hsimulacion_mysql_v2.sql`. Su implementación y forma de validación están documentadas en `docs/ETAPA_1_BASE_DE_DATOS.md`.

## Puesta en marcha local

1. Copiar `.env.example` como `.env` y configurar MySQL.
2. Ejecutar `composer install`.
3. Ejecutar `php artisan key:generate`.
4. Ejecutar `npm install` y `npm run build`.
5. Configurar Apache para servir la carpeta `public`.

Las decisiones de organización están en `docs/ARQUITECTURA.md`.
