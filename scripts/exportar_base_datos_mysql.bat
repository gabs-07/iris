@echo off
set DB_NAME=irisfepi
set DB_USER=root
set OUTPUT=database\exports\irisfepi_backup.sql
if not exist database\exports mkdir database\exports
mysqldump -u %DB_USER% -p %DB_NAME% > %OUTPUT%
echo Backup generado en %OUTPUT%
