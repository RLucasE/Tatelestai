#!/bin/bash

set -e

echo "Starting Queue Worker entrypoint script ($*)..."

INSTALL_LOCK="/var/www/storage/.composer_installing"

# Wait for Laravel dependencies and setup to be completed by php-fpm
if [ ! -f "/var/www/vendor/autoload.php" ] || [ -f "$INSTALL_LOCK" ]; then
    echo "Waiting for Laravel dependencies to be ready..."
    timeout=180
    while [ ! -f "/var/www/vendor/autoload.php" ] || [ -f "$INSTALL_LOCK" ]; do
        if [ $timeout -le 0 ]; then
            echo "Timed out waiting for Laravel dependencies."
            break
        fi
        sleep 2
        timeout=$((timeout - 2))
    done
    echo "Laravel dependencies are ready."
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

echo "Starting Queue Worker: $@"
exec "$@"

