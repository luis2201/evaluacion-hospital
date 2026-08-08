# Etapa 8 — Resultados y cierre

## Alcance implementado

- Resultado provisional y oficial calculado desde las vistas MySQL `vw_resultados_criterios`, `vw_resultados_dominios` y `vw_resultados_generales`.
- Puntaje, cumplimiento y avance por criterio.
- Cumplimiento y aporte ponderado por dominio.
- Puntaje general sobre 100 y categoría final del instrumento.
- Resumen ejecutivo con barras de progreso accesibles.
- Validación de todas las autoevaluaciones, calificaciones y observaciones antes del cierre.
- Cierre formal exclusivo del administrador, con fecha, usuario y auditoría.
- Bloqueo posterior de calificaciones y evidencias mediante reglas de aplicación y triggers MySQL.

## Regla de cierre

Una evaluación solamente puede pasar de `EN_EVALUACION` a `CERRADA` cuando:

1. Todos sus dominios tienen una autoevaluación `ENVIADA`.
2. Todos los descriptores están calificados.
3. No existen observaciones `ABIERTA` o `RESPONDIDA`.
4. El resultado general está completo y pertenece a una categoría configurada.

El cierre se ejecuta dentro de una transacción, registra `fecha_cierre`, `cerrada_at`, `cerrada_por`, cierra los dominios y genera el evento de auditoría `EVALUACION_CERRADA`.

La generación de archivos PDF/Excel y los reportes descargables corresponden a la etapa 9.
