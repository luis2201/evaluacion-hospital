# Etapa 5: autoevaluación por dominio

## Funcionalidad entregada

- Selección y consulta de la autoevaluación de cada dominio.
- Registro exclusivo por el responsable asignado al dominio.
- Guardado como borrador mientras la evaluación se encuentra en `CARGA_EVIDENCIAS`.
- Contador visual y validación de un máximo de 250 palabras.
- Envío definitivo con confirmación explícita.
- Bloqueo de cualquier modificación posterior al envío.
- Consulta de autoevaluaciones enviadas por administradores, evaluadores y auditores autorizados para ver el proceso.
- Registro de fecha de envío, usuario autor y auditoría de cada guardado y envío.

## Reglas

- La autoevaluación pertenece a una única instancia de dominio dentro de una evaluación.
- Solo el responsable exacto de ese dominio puede crearla o modificar su borrador.
- No se aceptan operaciones cruzadas entre evaluaciones o dominios.
- Solo puede trabajarse durante el estado `CARGA_EVIDENCIAS` de la evaluación.
- El contenido no puede superar 250 palabras; la validación se realiza antes de persistir.
- El envío cambia el dominio a `ENVIADO` y registra `enviado_at`.
- Una autoevaluación enviada es inmutable.

## Alcance

La gestión documental no forma parte de esta etapa y se encuentra implementada separadamente en la etapa 6.

## Pruebas

La suite verifica borradores, conteo de palabras, envío definitivo, bloqueo posterior, estado del dominio, auditoría, separación entre responsables, restricción por fase y consulta de evaluadores.
