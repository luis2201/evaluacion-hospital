# Etapa 9 — Reportes, auditoría y notificaciones

## Reporte PDF

La pantalla de resultados permite descargar un PDF protegido por la política `viewResults`. El documento contiene:

- Identificación, cronograma y condición provisional u oficial.
- Puntaje general, avance y categoría.
- Resultado y aporte ponderado por dominio.
- Estado y contenido de las cinco autoevaluaciones.
- Detalle de los 44 descriptores, calificaciones, archivos e incumplimientos.
- Observaciones y estado de resolución.
- Usuario y fecha del cierre formal cuando corresponde.

El PDF se genera con `barryvdh/laravel-dompdf` 3.1, compatible con Laravel 13, y no expone rutas privadas de documentos.

## Trazabilidad de descargas

Cada descarga registra evaluación, usuario, tipo, nombre del archivo, IP, agente de usuario y fecha en `reporte_descargas`. También genera el evento `REPORTE_RESULTADOS_DESCARGADO` en la auditoría general.

## Auditoría

Administradores y auditores de lectura acceden a una bitácora con filtros por acción, usuario y rango de fechas. La vista incluye valores anteriores/nuevos y las descargas recientes de reportes. Los demás roles reciben `403`.

## Notificaciones internas

El sistema utiliza el canal `database` de Laravel. Responsables y evaluadores reciben avisos cuando:

- Se habilita la carga de evidencias.
- Inicia la revisión según el cronograma.
- La evaluación queda cerrada y el resultado oficial está disponible.

Cada usuario puede consultar, abrir y marcar como leídas sus notificaciones.

La exportación Excel queda opcional; el formato institucional obligatorio de esta etapa es PDF.
