# Exportación e instalación de IRIS

Este paquete está preparado para mover el proyecto a otra máquina o a producción.

## Base de datos

El proyecto no trae una base de datos física obligatoria dentro del ZIP. La estructura se crea con migraciones de Laravel:

```bash
php artisan migrate --force
```

Para reiniciar la base y cargar datos iniciales:

```bash
php artisan migrate:fresh --seed
```

La estructura está en:

```txt
database/migrations/
```

Los datos iniciales están en:

```txt
database/seeders/
```

Si necesitas mover también datos reales desde otra máquina, exporta la BD con `mysqldump` y guarda el SQL fuera del repositorio o en `database/exports/` temporalmente.

## Configuración de correo

Se dejó la configuración base de correo del sistema IRIS dentro de `.env.example`:

```env
MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

En local esto guarda correos en:

```txt
storage/logs/laravel.log
```

Para producción usa `.env.production.example` y cambia los valores SMTP reales.

## Instalación local en otra máquina

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

En Windows puedes usar:

```bat
copy .env.example .env
```

## Instalación en producción

```bash
composer install --no-dev --optimize-autoloader
cp .env.production.example .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Después configura el servidor web para apuntar a:

```txt
public/
```

## Permisos en Linux/hosting

```bash
chmod -R ug+rwx storage bootstrap/cache
```

## Exportar datos existentes

Ejemplo MySQL:

```bash
mysqldump -u usuario -p irisfepi > database/exports/irisfepi_backup.sql
```

Restaurar:

```bash
mysql -u usuario -p irisfepi < database/exports/irisfepi_backup.sql
```

## Archivos que normalmente NO se suben

- `.env`
- `vendor/`
- `node_modules/`
- `storage/logs/*`
- backups `.sql` con datos reales

## Después de cambiar `.env`

```bash
php artisan config:clear
php artisan cache:clear
```

En producción, vuelve a cachear:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
