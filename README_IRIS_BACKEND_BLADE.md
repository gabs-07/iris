# IRIS backend Laravel + Blade

## Instalación en Laragon

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Usuario inicial:

- Email: `admin@iris.test`
- Contraseña: `password`

> Cambia la contraseña del administrador antes de usar el sistema en producción.

## Correo electrónico

Para desarrollo local, deja el correo en modo log para evitar el error `Connection could not be established with host 127.0.0.1:2525`:

```env
MAIL_MAILER=log
```

Con esa configuración, los enlaces de verificación y recuperación se guardan en `storage/logs/laravel.log`.

Para correos reales, configura SMTP real en `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.tu-proveedor.com
MAIL_PORT=587
MAIL_USERNAME=usuario
MAIL_PASSWORD=contraseña
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@iris.test
MAIL_FROM_NAME="IRIS"
```

Sin SMTP real, el sistema no puede enviar correos reales. El código ya evita que el registro truene si SMTP está mal configurado, pero el usuario deberá verificar su correo desde el log o configurar SMTP.

## PayPal

Crea una app en PayPal Developer y agrega tus credenciales:

```env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=tu_client_id
PAYPAL_SECRET=tu_secret
PAYPAL_CURRENCY=MXN
```

En producción cambia `PAYPAL_MODE=live` y usa credenciales live.

## Flujos implementados

- Registro mínimo por rol.
- Verificación de correo obligatoria.
- Recuperación de contraseña por correo.
- Completar perfil paciente.
- Completar perfil profesional y enviar solicitud al administrador.
- Aprobación/rechazo de psicólogos y psiquiatras por admin.
- Suscripción profesional con PayPal.
- Agendar cita con PayPal.
- Solicitudes de cita respondidas por profesional.
- Reembolso PayPal cuando el profesional rechaza una cita pagada.
- Comunidad con publicaciones, comentarios, edición, eliminación, reacciones y reportes.
- Notificaciones por correo y base de datos.
- Seguridad clínica con validación por rol, acceso por relación paciente-profesional, auditoría y cifrado de campos clínicos sensibles.
- Frontend Blade alineado con los assets nativos de `view(6).zip`, sin datos de ejemplo en módulos dinámicos.
- Suite de pruebas alineada con las rutas reales del proyecto.

## Validación profesional

El sistema recibe cédula y documentos, envía el perfil al administrador y bloquea el acceso clínico hasta que el administrador apruebe. La validación documental final queda a cargo del administrador, porque la consulta automática a una fuente oficial de cédulas requiere integrar un proveedor externo o servicio oficial disponible para el despliegue.

## No implementado por indicación

- Chat entre usuarios.

## Cuentas iniciales incluidas en el seeder

Todas usan la contraseña: `password`.

- Admin: `admin@iris.test`
- Paciente: `paciente@iris.test`
- Paciente 2: `paciente2@iris.test`
- Psicólogo aprobado: `psicologo@iris.test`
- Psicóloga aprobada: `psicologa2@iris.test`
- Psiquiatra aprobado: `psiquiatra@iris.test`
- Psicólogo pendiente de aprobación admin: `psicologo.pendiente@iris.test`

Los profesionales aprobados ya tienen perfil completo y suscripción activa para probar directorio, comunidad y agenda. El profesional pendiente sirve para probar el flujo de aprobación/rechazo desde administración.

## Correcciones visuales finales

- El menú lateral usa el logo real de IRIS del frontend original.
- Diario guarda siempre la fecha actual y el buscador filtra entradas en vivo.
- Citas muestra tarjetas de resumen y estado con textos legibles.
- Comunidad usa tarjetas mejoradas, avatares circulares, comentarios estilizados, reacciones, reportes y edición de publicaciones.
- Las notificaciones se abren en modal desde la campana; no llevan a una vista independiente al hacer clic en la campana.
