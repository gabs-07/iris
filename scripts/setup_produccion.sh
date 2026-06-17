#!/usr/bin/env bash
set -e
composer install --no-dev --optimize-autoloader
[ -f .env ] || cp .env.production.example .env
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
