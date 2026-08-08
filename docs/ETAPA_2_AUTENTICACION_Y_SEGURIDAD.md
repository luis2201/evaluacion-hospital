# Etapa 2: autenticación, usuarios y seguridad

## Funcionalidad entregada

- Inicio y cierre de sesión con regeneración del identificador de sesión.
- Límite de cinco intentos de acceso por combinación de correo e IP.
- Bloqueo de acceso para cuentas inactivas.
- Recuperación de contraseña con respuesta neutra para evitar enumeración de cuentas.
- Cambio de contraseña autenticado.
- Contraseñas de mínimo 12 caracteres, mayúsculas, minúsculas, números y símbolos.
- Administración de usuarios, estado y múltiples roles.
- Protección contra la desactivación del usuario administrador actual y del último administrador activo.
- Consulta y revocación de sesiones almacenadas en MySQL.
- Auditoría de accesos, fallos, cambios de contraseña y administración de usuarios.
- Policies, middleware por rol y cabeceras HTTP de seguridad.
- Mensajes de validación en español.

## Administrador inicial

No existe una contraseña predeterminada ni se almacena una en los seeders. Cuando no existen usuarios, el acceso al login redirige automáticamente al asistente web de configuración inicial. Después de crear la primera cuenta, el asistente se deshabilita.

Como alternativa administrativa también está disponible el comando interactivo:

```bash
php artisan app:create-admin
```

El comando solicita nombre, correo y contraseña sin mostrar esta última en pantalla.

## Correo

La recuperación utiliza el mailer configurado en `.env`. En desarrollo puede utilizarse `MAIL_MAILER=log`; en producción debe configurarse SMTP o el proveedor institucional antes de habilitar la recuperación para usuarios finales.

## Producción

- Usar HTTPS.
- Configurar `APP_DEBUG=false`.
- Configurar `SESSION_SECURE_COOKIE=true`.
- Conservar `SESSION_ENCRYPT=true`.
- Proteger las credenciales de MySQL y correo fuera del repositorio.
- Programar limpieza de sesiones expiradas y copias de seguridad.
