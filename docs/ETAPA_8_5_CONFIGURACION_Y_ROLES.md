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
