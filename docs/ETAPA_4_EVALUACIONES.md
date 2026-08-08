# Etapa 4: creación y configuración de evaluaciones

## Funcionalidad entregada

- Listado de evaluaciones según permisos y asignaciones.
- Creación desde una versión publicada del instrumento.
- Datos generales, tipo de escenario y cronograma.
- Fecha prevista de cierre separada de la fecha efectiva.
- Asignación de un responsable por cada dominio.
- Asignación de uno o varios evaluadores; el primero se registra como principal.
- Generación transaccional de dominios y descriptores del proceso.
- Reconfiguración de responsables y evaluadores mientras permanezca en borrador.
- Habilitación de la fase de carga de evidencias.
- Cancelación controlada del proceso.
- Dashboard con cantidades y evaluaciones recientes visibles para cada usuario.
- Auditoría de creación, configuración y cambios de estado.

## Permisos

- El administrador crea, configura, habilita o cancela evaluaciones.
- El Responsable de dominio consulta evaluaciones donde tenga una asignación.
- El Evaluador externo consulta evaluaciones donde esté asignado.
- El Auditor de lectura consulta todas las evaluaciones.
- Un usuario sin relación con el proceso no puede consultarlo.

## Reglas

- Solo pueden seleccionarse instrumentos publicados.
- Cada dominio debe tener un responsable activo con el rol correspondiente.
- Debe existir al menos un evaluador externo activo.
- El instrumento, las asignaciones y los ítems se crean dentro de una única transacción.
- Una evaluación nueva queda en `BORRADOR`.
- Al habilitarla pasa a `CARGA_EVIDENCIAS` y sus dominios a `EN_CARGA`.
- Las fechas configuran el cronograma previsto y deben conservar el orden inicio → límite de carga → inicio de evaluación → cierre previsto.
- El vencimiento de una fecha no cambia el estado automáticamente; las transiciones son acciones administrativas explícitas y conservan sus validaciones funcionales.
- Solo los borradores pueden reconfigurarse.
- Las evaluaciones cerradas o ya canceladas no pueden cancelarse nuevamente.

La autoevaluación se implementa en la etapa 5. La gestión documental permanece reservada para la etapa 6.
