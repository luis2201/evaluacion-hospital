# Etapa 7: evaluación, observaciones y subsanación

## Inicio de la revisión

- El administrador inicia formalmente la revisión desde una evaluación en `CARGA_EVIDENCIAS`.
- Los cinco dominios deben tener su autoevaluación enviada.
- La evaluación cambia a `EN_EVALUACION` y los descriptores pendientes pasan a `EN_EVALUACION`.

## Bandeja del evaluador

- Los evaluadores asignados acceden directamente a la sección **Revisión del evaluador**.
- La navegación conserva el selector de dominio, agrupación por criterio y descriptores desplegables.
- Cada descriptor presenta sus archivos privados, enlaces, observaciones e historial.
- Los evaluadores no asignados, administradores y auditores de lectura no pueden calificar ni crear observaciones. El administrador conserva Consulta general y las acciones de control del proceso.

## Calificación

- La escala permitida es `0` (no cumple), `1` (cumple parcialmente) y `2` (cumple).
- Un descriptor no puede calificarse sin al menos un archivo activo.
- No puede calificarse mientras tenga observaciones abiertas o respondidas.
- Cada cambio conserva calificación y comentario anterior, valor nuevo, evaluador y fecha.
- Un descriptor calificado cambia a `EVALUADO`.
- Las evaluaciones cerradas o canceladas no admiten modificaciones.

## Observaciones y subsanación

- El evaluador puede abrir una observación sobre un descriptor todavía no calificado.
- Solo puede existir una observación pendiente por descriptor.
- El descriptor cambia a `OBSERVADO`.
- Mientras la observación permanece abierta, el responsable exacto del dominio puede complementar o retirar evidencias y registrar enlaces.
- El responsable envía una respuesta narrativa; la observación cambia a `RESPONDIDA` y se bloquean nuevas cargas.
- El evaluador revisa la subsanación y cierra la observación.
- Al cerrar, el descriptor vuelve a `EN_EVALUACION` y puede calificarse.

## Trazabilidad

- Historial explícito en `evaluacion_descriptor_calificaciones`.
- Auditoría de inicio de revisión, calificaciones, observaciones, respuestas y cierres.
- Conservación de autores y fechas en observaciones y respuestas.
- Descargas de evidencias continúan registrando usuario, IP, agente y fecha.

## Pruebas

La suite cubre requisitos para iniciar la revisión, permisos de evaluadores, prohibición de calificar sin archivos, historial, aislamiento entre dominios y el ciclo completo observación → evidencia → respuesta → cierre → calificación.
