# Etapa 6: gestión documental de evidencias

## Experiencia de uso

Cada evaluación contiene dos áreas internas para evitar presentar toda la información simultáneamente:

- **Ingreso por dominio:** muestra únicamente los dominios asignados al responsable autenticado. Cada dominio presenta primero su autoevaluación y después sus criterios y descriptores para cargar evidencias.
- **Consulta general:** permite a los usuarios autorizados recorrer todos los dominios, descriptores, archivos y enlaces del proceso sin habilitar acciones de edición.

En ambas áreas se selecciona un solo dominio a la vez. Los descriptores están agrupados por criterio y permanecen contraídos hasta que el usuario decide abrirlos.

## Gestión de archivos

- Carga de hasta 10 archivos por envío y 10 MB por archivo.
- Formatos permitidos: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT y RTF.
- Almacenamiento exclusivo en `storage/app/private`, sin enlaces públicos.
- Nombres internos UUID no predecibles y conservación del nombre original como metadato.
- Cálculo SHA-256 y prevención de duplicados por descriptor.
- Restauración controlada cuando vuelve a cargarse un archivo retirado previamente.
- Eliminación lógica durante la fase de carga, sin borrar físicamente el documento.
- Visualización en navegador para PDF, JPEG y PNG.
- Descarga autorizada para otros formatos.
- Registro de usuario, IP, agente y fecha de cada descarga.
- Auditoría de cargas, restauraciones y retiros.

## Enlaces de evidencia

- Registro complementario de direcciones HTTP o HTTPS.
- Descripción opcional y prevención de duplicados activos por descriptor.
- Eliminación lógica y auditoría.

## Autorización y estados

- El responsable solo puede gestionar evidencias de los descriptores pertenecientes a sus dominios asignados.
- El administrador dispone únicamente de Consulta general y control de las transiciones del proceso; no carga, elimina ni califica evidencias.
- Evaluadores y auditores tienen acceso de consulta, visualización y descarga, pero no de escritura.
- Las cargas, restauraciones y eliminaciones se permiten en `CARGA_EVIDENCIAS`. Durante `EN_EVALUACION` se habilitan excepcionalmente para el responsable cuando un descriptor tiene una observación abierta, como parte de la subsanación de la etapa 7.
- Todas las rutas comprueban la pertenencia evaluación → descriptor → archivo o enlace.

## Pruebas

Las pruebas funcionales cubren navegación por secciones, aislamiento entre dominios, carga privada múltiple, permisos, duplicados, borrado lógico, restauración, visualización, descarga, trazabilidad, restricciones de fase, enlaces y rechazo de extensiones ejecutables.
