# Exportación de base de datos

Este proyecto usa migraciones. Si solo quieres instalarlo limpio, no necesitas SQL: ejecuta `php artisan migrate:fresh --seed`.

Si quieres mover datos reales existentes, exporta desde MySQL:

```bash
mysqldump -u usuario -p irisfepi > database/exports/irisfepi_backup.sql
```

Restaura en otra máquina:

```bash
mysql -u usuario -p irisfepi < database/exports/irisfepi_backup.sql
```

No subas backups con datos sensibles a repositorios públicos.
