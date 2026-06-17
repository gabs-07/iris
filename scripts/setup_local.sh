#!/usr/bin/env bash
set -e
composer install
[ -f .env ] || cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
