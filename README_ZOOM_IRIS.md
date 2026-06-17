# Integración Zoom para sesiones IRIS

Esta versión incluye generación automática de reuniones de Zoom para las citas con modalidad de videollamada.

## Variables obligatorias

Agrega las credenciales de tu app **Server-to-Server OAuth** en el archivo `.env`:

```env
ZOOM_ACCOUNT_ID=tu_account_id
ZOOM_CLIENT_ID=tu_client_id
ZOOM_CLIENT_SECRET=tu_client_secret
ZOOM_BASE_URL=https://api.zoom.us/v2
ZOOM_TIMEZONE=America/Mexico_City
ZOOM_DEFAULT_DURATION=50
```

Por seguridad, las credenciales reales no deben quedar hardcodeadas en el código fuente.

## Migración

Ejecuta:

```bash
php artisan migrate
php artisan config:clear
```

La migración agrega a `appointments` los campos:

- `zoom_meeting_id`
- `zoom_join_url`
- `zoom_start_url`
- `zoom_password`
- `zoom_created_at`
- `zoom_payload`

## Flujo implementado

1. El paciente solicita una cita y paga.
2. El profesional acepta la solicitud.
3. Si la modalidad es videollamada y no se capturó un enlace manual, Laravel crea la reunión en Zoom.
4. IRIS guarda el enlace de paciente (`join_url`) y el enlace de anfitrión (`start_url`).
5. El paciente ve el botón para entrar a la videollamada.
6. El profesional ve el botón para iniciar la videollamada.

También se genera reunión cuando:

- El profesional agenda una sesión directa aceptada.
- El paciente acepta una solicitud creada por el profesional.
- El paciente acepta una propuesta de reagenda y la cita no tenía enlace previo.

## Archivos principales modificados

- `app/Services/ZoomClient.php`
- `app/Services/AppointmentVideoService.php`
- `config/services.php`
- `app/Models/Appointment.php`
- `app/Http/Controllers/Paciente/PacienteController.php`
- `app/Http/Controllers/Profesional/ProfesionalController.php`
- `resources/views/paciente/gestion-citas.blade.php`
- `resources/views/paciente/sesion.blade.php`
- `resources/views/psicologo/agenda-psicologo.blade.php`
- `resources/views/psicologo/dashboard-psicologo.blade.php`
- `resources/views/psicologo/sesion.blade.php`
