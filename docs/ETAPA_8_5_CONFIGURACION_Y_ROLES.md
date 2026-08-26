# Etapa 8.5 — Configuración y experiencia por roles

## Objetivo

Separar la navegación, capacidades y configuración de acuerdo con las responsabilidades institucionales antes de incorporar reportes, auditoría y despliegue.

## Matriz aplicada

| Rol | Alcance |
|---|---|
| Administrador | Usuarios, instrumentos, evaluaciones, resultados, cierre y parámetros del sistema. |
| Responsable de dominio | Instrumento publicado, evaluaciones asignadas, autoevaluación, evidencias y respuestas a observaciones. |
| Evaluador externo | Evaluaciones asignadas, revisión, observaciones y calificaciones. No accede al módulo de instrumentos. |
| Auditor de lectura | Instrumentos, evaluaciones y resultados en modo de consulta, sin operaciones de escritura. |

La interfaz oculta las opciones ajenas al rol, pero la seguridad también se aplica mediante políticas y middleware en el servidor.

## Configuración personal

Todos los usuarios autenticados pueden consultar:

- Estado de su cuenta.
- Roles activos y capacidades asociadas.
- Acceso al cambio personal de contraseña.

## Configuración administrativa

Solo el administrador puede modificar parámetros persistidos en `configuraciones_sistema`:

- Nombre institucional, nombre corto y correo de soporte.
- Cantidad máxima de archivos por selección.
- Tamaño máximo permitido por archivo.
- Tiempo de inactividad de sesión.
- Longitud mínima de contraseña.
- Intentos permitidos y duración del bloqueo de inicio de sesión.

Los límites documentales y de seguridad son consumidos por las validaciones de Laravel. Cada modificación genera el evento de auditoría `CONFIGURACION_SISTEMA_ACTUALIZADA`.

## Estado y cronograma

El administrador dispone de una vista técnica de versiones de PHP, Laravel y MySQL, disponibilidad del almacenamiento privado y accesos contextuales al cronograma de las evaluaciones. Las fechas permanecen asociadas a cada evaluación para preservar la trazabilidad de cada proceso.

El cronograma puede editarse desde Configuración mientras la evaluación no esté cerrada ni cancelada. Se valida el orden inicio de carga → límite de carga → inicio de revisión → cierre previsto, se impide reabrir una fase ya finalizada y cada ajuste genera `EVALUACION_CRONOGRAMA_ACTUALIZADO` antes de sincronizar inmediatamente el estado.

La carga puede realizarse en un único día, pero las fases no se superponen: `fecha_limite_carga < fecha_inicio_evaluacion < fecha_cierre_prevista`. Esta regla está implementada en las solicitudes Laravel y en la restricción MySQL `chk_evaluacion_cronograma`.

Al comenzar la revisión, cada descriptor que no tenga un archivo activo recibe automáticamente calificación `0`, estado `EVALUADO` y motivo `ARCHIVO_NO_CARGADO`. El evaluador lo identifica mediante una leyenda visible y el evento consolidado `DESCRIPTORES_SIN_ARCHIVO_CALIFICADOS` conserva la cantidad afectada. MySQL permite un descriptor sin archivo únicamente cuando se trata de este cero automático; una calificación manual sin evidencia continúa bloqueada.

Los resultados interpretan las leyendas según el cronograma. Durante la carga muestran pendientes de evidencia; vencido el plazo muestran descriptores incumplidos (calificación `0`) y separan los archivos pendientes de revisión. Las alertas distinguen evaluación programada, carga abierta, revisión en curso, cierre previsto vencido y cierre formal.

Las autoevaluaciones tampoco pueden enviarse después del límite de carga. Al iniciar la revisión, cada dominio sin envío definitivo queda clasificado como `INCUMPLIDA`, con la observación “Autoevaluación no enviada dentro del plazo de carga establecido”. En resultados se separan enviadas, incumplidas y realmente pendientes. Una autoevaluación incumplida es un resultado final desfavorable y no bloquea el cierre; únicamente lo bloquea una autoevaluación que todavía no haya sido enviada ni clasificada.

La lista de evaluaciones presenta el estado derivado del cronograma: programada, carga de evidencias, carga finalizada, en evaluación, cierre previsto vencido, cerrada o cancelada. El estado persistido continúa sincronizándose para aplicar las reglas de escritura y seguridad.
