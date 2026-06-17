@echo off
set DB_NAME=irisfepi
set DB_USER=root
set INPUT=database\exports\irisfepi_backup.sql
mysql -u %DB_USER% -p %DB_NAME% < %INPUT%
echo Base restaurada desde %INPUT%
