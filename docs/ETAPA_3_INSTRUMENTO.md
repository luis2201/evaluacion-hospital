# Etapa 3: administración del instrumento

## Funcionalidad entregada

- Listado de versiones del instrumento y sus estados.
- Consulta jerárquica dominio → criterio → descriptor.
- Consulta de pesos, escala de calificación y categorías de resultado.
- Creación de versiones vacías como borrador.
- Clonación completa de versiones publicadas o archivadas.
- Trazabilidad de la versión de origen mediante `modelo_origen_id`.
- Administración de dominios, criterios, descriptores y categorías en borradores.
- Validación estructural antes de publicar.
- Publicación irreversible de la estructura de una versión.
- Archivo de versiones publicadas sin eliminar información.
- Auditoría de las operaciones administrativas.

## Reglas de integridad

- Solo los administradores pueden crear, modificar, clonar, publicar o archivar.
- Los usuarios autenticados pueden consultar versiones publicadas o archivadas.
- Los borradores son visibles únicamente para administradores.
- Solo se puede editar un borrador que no haya sido utilizado por evaluaciones.
- Una versión publicada queda bloqueada; para cambiarla debe clonarse.
- Los pesos de los dominios deben sumar exactamente 100 % antes de publicar.
- Cada dominio debe contener criterios y cada criterio debe contener descriptores.
- Las categorías deben cubrir todo el intervalo de 0 a 100 sin vacíos ni superposiciones.
- La escala de cada descriptor permanece fija en 0, 1 y 2.

## Estados

- `BORRADOR`: estructura editable, todavía no disponible para evaluaciones.
- `PUBLICADO`: estructura oficial e inmutable.
- `ARCHIVADO`: versión histórica conservada para consulta y trazabilidad.

El modelo oficial MEC-SIM v1 sembrado permanece publicado. Toda adaptación debe comenzar con la acción **Duplicar**.
