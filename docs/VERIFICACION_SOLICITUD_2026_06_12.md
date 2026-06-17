# Verificación e implementación solicitada - IRIS

## Laravel puro
- El proyecto se mantiene como Laravel + Blade + controladores Laravel.
- No se agregó React, Vue, Inertia ni frontend externo.
- Los CSS se referencian con `asset('css/...')` y existen dentro de `public/css`.

## Perfiles agregados en `DatabaseSeeder`
Todos usan contraseña: `password`.

1. Invitado: `invitado@iris.test`
   - Rol `invitado`.
   - También existe el flujo público `/auxilio-invitado` para pedir auxilio sin iniciar sesión ni crear cuenta.
2. Paciente: `paciente@iris.test`
3. Psicóloga Auxilio: `psicologo.auxilio@iris.test`
   - Rol `psicologo`.
   - Modo Escucha activo.
   - Recibe solicitudes del botón de Auxilio.
4. Psicólogo Gestor: `psicologo.gestor@iris.test`
   - Rol `psicologo`.
   - Tiene relación clínica demo con el paciente.
   - Puede ver el diario autorizado por el paciente.
5. Psiquiatra: `psiquiatra@iris.test`
6. Admin: `admin@iris.test`

## Auxilio para invitados
Archivos principales:
- `app/Http/Controllers/GuestAuxilioController.php`
- `app/Services/AuxilioSessionService.php`
- `resources/views/guest/auxilio.blade.php`
- `resources/views/partials/floating-auxilio.blade.php`
- `routes/web.php`

Funcionamiento:
- Ruta pública: `/auxilio-invitado`.
- El invitado puede solicitar auxilio sin cuenta.
- El sistema crea un usuario temporal con rol `invitado` y una cita de auxilio con `requested_by = auxilio_invitado`.
- Busca un psicólogo/psiquiatra aprobado, con suscripción activa y Modo Escucha activo.
- Al terminar, se muestra invitación para registrarse.

## Chat independiente entre psicólogos y psiquiatras
Archivos principales:
- `app/Http/Controllers/ProfessionalChatController.php`
- `app/Models/ProfessionalChatMessage.php`
- `resources/views/psicologo/chat-profesional.blade.php`
- `database/migrations/2026_06_12_000800_add_guest_diary_and_professional_chat.php`

Funcionamiento:
- Ruta: `/psicologo/chat-profesional`.
- Solo entra `psicologo` o `psiquiatra` con perfil aprobado y suscripción activa.
- Frontend con tags: `#interconsulta`, `#seguimiento`, `#riesgo`, `#medicacion`, `#agenda`, `#derivacion`, `#caso-clinico`, `#general`.

## Diario agrupado por día y autorización al profesional
Archivos principales:
- `app/Http/Controllers/Paciente/PacienteController.php`
- `app/Models/DiaryEntry.php`
- `resources/views/paciente/diario-paciente.blade.php`
- `resources/views/paciente/diario-todas.blade.php`
- `resources/views/psicologo/diarios-autorizados.blade.php`

Funcionamiento:
- El paciente puede escribir varias notas durante el día.
- Todas las notas del mismo día se guardan en un solo registro de `diary_entries`.
- Cada nota conserva su hora (`HH:mm`), título, contenido, estado de ánimo y emoji.
- Al pasar las 12:00 AM se crea automáticamente un nuevo registro diario por fecha.
- El paciente puede autorizar a un profesional con relación clínica para ver el registro.
- El profesional lo consulta en `/psicologo/diarios-autorizados`.

## Logs de errores/estatus importantes
Archivos principales:
- `app/Http/Middleware/HttpStatusLogger.php`
- `bootstrap/app.php`

Se registran en `storage/logs/laravel.log` respuestas o excepciones HTTP importantes:
- 3xx: 300, 301, 302, 303, 307, 308
- 4xx: 400, 401, 403, 404, 419, 422, 429
- 5xx: 500, 502, 503, 504 y cualquier estatus >= 500

Cada log guarda método, URL, usuario, IP, user-agent y ruta cuando aplica.

## Validaciones realizadas
- `php -l` ejecutado sobre archivos PHP de `app`, `bootstrap`, `database` y `routes` sin errores de sintaxis.
- Se verificó que los CSS usados en vistas existan en `public`.

Nota: el ZIP no incluye carpeta `vendor`, por eso no se ejecutó `php artisan route:list` ni migraciones desde esta revisión. En tu máquina ejecuta:

```bash
composer install
php artisan migrate:fresh --seed
php artisan serve
```
