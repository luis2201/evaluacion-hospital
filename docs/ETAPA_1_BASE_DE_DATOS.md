# Etapa 1: base de datos y dominio

Esta etapa implementa el diseño revisado para MySQL 8 mediante migraciones Laravel.

## Componentes entregados

- Seis migraciones ordenadas por dependencias.
- Tres vistas de resultados y cuatro triggers de integridad exclusivos de MySQL.
- Enums PHP para estados, tipos de escenario, roles y calificaciones.
- Modelos Eloquent con nombres de tabla explícitos, casts y relaciones.
- Seeder de roles.
- Seeder versionado del instrumento MEC-SIM v1, cuya fuente oficial es `database/schema/evaluacion_hsimulacion_mysql_v2.sql`.
- Pruebas de cantidades oficiales, relaciones, casts, vistas, triggers y restricciones.

## Reconstrucción local

La base configurada en `.env` debe existir y pertenecer exclusivamente a la aplicación.

```bash
php artisan migrate:fresh --seed
```

Este comando es destructivo para la base seleccionada. No debe ejecutarse sobre producción.

## Pruebas

Las pruebas requieren una base MySQL independiente llamada `evaluacion_hsimulacion_testing`. PHPUnit nunca debe apuntar a una base con información real.

```bash
php artisan test
```

La configuración predeterminada está en `phpunit.xml`; usuario, contraseña, host y puerto pueden sobrescribirse mediante variables de entorno.

## Fuente del instrumento

La ficha operativa contiene 5 dominios, 17 criterios, 44 descriptores y 4 categorías. El seeder detiene la ejecución si estas cantidades o el peso total de 100 % dejan de cumplirse.
