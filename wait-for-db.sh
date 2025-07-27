#!/bin/sh

echo "Waiting for MySQL to be ready..."
until mysqladmin ping -h"${DB_HOST:-db}" --silent; do
    sleep 2
done

echo "Running Laravel migrations..."
php artisan migrate --force

exec php-fpm
