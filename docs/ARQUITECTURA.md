# Base arquitectónica

Aplicación monolítica Laravel para evaluar la calidad del Hospital de Simulación. Las etapas funcionales se incorporan progresivamente sobre un dominio Eloquent y un esquema MySQL versionado mediante migraciones.

## Organización prevista

- `app/Actions`: operaciones de aplicación de un único propósito.
- `app/Data`: objetos de transferencia de datos entre capas.
- `app/Enums`: estados y valores cerrados del dominio.
- `app/Policies`: autorización por recurso.
- `app/Services`: servicios transversales, como documentos y cálculos.
- `app/Support`: utilidades técnicas sin lógica de negocio.
- `resources/views/components`: componentes Blade reutilizables.
- `resources/views`: páginas Blade de composición.
- `storage/app/private`: documentos institucionales no accesibles directamente desde Apache.
- `database/schema`: scripts SQL de referencia revisados.
- `docs`: instrumento oficial y decisiones técnicas.

## Criterios acordados

- Blade es la opción predeterminada para las vistas.
- Livewire se incorporará solo en módulos cuya interacción lo justifique.
- Los documentos privados se descargarán posteriormente mediante controladores autorizados; no se publicarán con enlaces simbólicos.
- La lógica de negocio no se ubicará en controladores ni componentes visuales.
- La interfaz, mensajes y validaciones visibles estarán en español.

## Despliegue Apache

El `DocumentRoot` del sitio debe apuntar a la carpeta `public`. Apache debe permitir las reglas de `public/.htaccess`, y las carpetas `storage` y `bootstrap/cache` necesitan permisos de escritura para el usuario del servidor web.
