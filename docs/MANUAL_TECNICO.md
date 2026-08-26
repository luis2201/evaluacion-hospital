# Manual técnico

## Componentes

- Laravel 13 y PHP 8.3.
- MySQL 8 con vistas de cálculo, restricciones y triggers de integridad.
- Blade/Vite para interfaz; documentos en `storage/app/private`.
- Apache con raíz pública limitada a `public`.
- Scheduler para transiciones por cronograma y cola para trabajos asincrónicos.

## Operación diaria

```bash
php artisan about
php artisan schedule:list
php artisan queue:monitor default:100
php artisan migrate:status
php artisan production:check
```

Revisar logs de Apache, `storage/logs`, espacio en disco, estado de MySQL, scheduler, cola y antigüedad del último respaldo.

## Actualizaciones

Toda modificación debe llegar mediante PR y pruebas automáticas. Producción despliega exclusivamente `main` usando `scripts/deploy-production.sh`; no se editan archivos del repositorio directamente en el servidor.

## Datos protegidos

- `.env`, respaldos y documentos privados no pertenecen al repositorio.
- Los archivos solo se sirven mediante controladores autorizados.
- Las descargas de evidencias y reportes quedan registradas.
- Las evaluaciones cerradas no admiten cambios de calificación o evidencias.

## Incidentes

1. Activar mantenimiento: `php artisan down --retry=60`.
2. Preservar logs y registrar hora/usuarios afectados.
3. Verificar integridad de base de datos y documentos.
4. Restaurar únicamente desde un respaldo validado si es necesario.
5. Ejecutar `production:check`, pruebas funcionales y `health-check.sh`.
6. Reabrir con `php artisan up` y documentar el incidente.

Consultar [DESPLIEGUE_PRODUCCION.md](DESPLIEGUE_PRODUCCION.md) para instalación, Apache, respaldos y recuperación.
