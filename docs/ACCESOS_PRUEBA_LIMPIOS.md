# Accesos de prueba limpios - IRIS

Todos usan la contraseña: `password`.

| Rol | Correo | Estado |
|---|---|---|
| Admin | `admin@iris.local` | Administrador activo |
| Paciente | `paciente@iris.local` | Perfil completo |
| Paciente 2 | `paciente2@iris.local` | Perfil completo |
| Psicóloga | `psicologo@iris.local` | Aprobada y con suscripción activa |
| Psicóloga 2 | `psicologa2@iris.local` | Aprobada y con suscripción activa |
| Psiquiatra | `psiquiatra@iris.local` | Aprobado y con suscripción activa |
| Psicólogo pendiente | `psicologo.pendiente@iris.local` | Pendiente de revisión admin |

## Cómo limpiar y sembrar

```bash
php artisan migrate:fresh --seed
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
```

El seeder deja vacíos los registros transaccionales: citas, pagos, tareas, diario, notas, prescripciones, comunidad, reportes, notificaciones y auditoría. Solo conserva usuarios, perfiles mínimos, consentimientos y suscripciones necesarias para probar agenda.
