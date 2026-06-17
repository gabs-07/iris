# IRIS con Laravel Breeze

Esta versión usa la estructura de autenticación de Laravel Breeze.

## Qué cambió

- Las rutas de autenticación viven en `routes/auth.php`.
- `routes/web.php` carga `require __DIR__.'/auth.php';`.
- Los controladores de autenticación quedan separados al estilo Breeze:
  - `AuthenticatedSessionController`
  - `RegisteredUserController`
  - `PasswordResetLinkController`
  - `NewPasswordController`
  - `EmailVerificationPromptController`
  - `VerifyEmailController`
  - `EmailVerificationNotificationController`
- El formulario de login usa `LoginRequest`, con rate limiting como Breeze.
- Se conservan las vistas visuales de IRIS:
  - `resources/views/login.blade.php`
  - `resources/views/registro.blade.php`
  - `resources/views/recuperar.blade.php`
  - `resources/views/auth/reset-password.blade.php`
  - `resources/views/auth/verify-email.blade.php`

## URLs conservadas

Para no romper el frontend, se mantienen las URLs en español:

- `/login`
- `/registro`
- `/recuperar`
- `/reset-password/{token}`
- `/email/verify`

También se conservan aliases compatibles:

- `/register` redirige a `/registro`
- `/forgot-password` redirige a `/recuperar`

## Redirección por rol

Después del login, Breeze autentica al usuario y el sistema redirige según rol:

- Admin: `/admin`
- Paciente: `/paciente/dashboard-paciente`
- Psicólogo/psiquiatra aprobado: `/psicologo/dashboard-psicologo`
- Psicólogo/psiquiatra incompleto/pendiente/rechazado: `/psicologo/perfil-psicologo`

## Instalación

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Para compilar assets si se usan recursos Vite/Tailwind:

```bash
npm install
npm run build
```
