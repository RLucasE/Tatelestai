#!/bin/bash

set -e

echo "Starting PHP-FPM entrypoint script..."

# Check if Laravel is installed (vendor directory exists)
if [ ! -d "/var/www/vendor" ]; then
    echo "Laravel dependencies not found. Please run 'composer install' first."
fi

# Check if .env file exists
if [ ! -f "/var/www/.env" ]; then
    echo "Warning: .env file not found. Please copy .env.example to .env and configure it."
fi

# Wait for database to be ready (optional but recommended)
if [ -n "$DB_HOST" ] && [ -n "$DB_PORT" ]; then
    echo "Waiting for database at $DB_HOST:$DB_PORT..."
    timeout=30
    while ! nc -z "$DB_HOST" "$DB_PORT"; do
        timeout=$((timeout - 1))
        if [ $timeout -le 0 ]; then
            echo "Timeout waiting for database"
            break
        fi
        sleep 1
    done
    echo "Database is ready!"
fi

# Set proper permissions for Laravel directories
echo "Setting up Laravel storage and cache permissions..."
if [ -d "/var/www/storage" ]; then
    find /var/www/storage -type d -exec chmod 775 {} \;
    find /var/www/storage -type f -exec chmod 664 {} \;
fi

if [ -d "/var/www/bootstrap/cache" ]; then
    find /var/www/bootstrap/cache -type d -exec chmod 775 {} \;
    find /var/www/bootstrap/cache -type f -exec chmod 664 {} \;
fi

echo "Starting PHP-FPM..."
# Execute the CMD from Dockerfile (php-fpm)
exec "$@"
