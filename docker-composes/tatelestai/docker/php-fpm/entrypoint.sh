#!/bin/bash

set -e

echo "Starting PHP entrypoint script ($*)..."

INSTALL_LOCK="/var/www/storage/.composer_installing"

# Check if .env file exists; if not, copy from .env.example
if [ ! -f "/var/www/.env" ]; then
    if [ -f "/var/www/.env.example" ]; then
        echo "Creating .env from .env.example..."
        cp /var/www/.env.example /var/www/.env
    else
        echo "Warning: Neither .env nor .env.example found."
    fi
fi

# Clean up any stale lock file from an unexpected container shutdown
rm -f "$INSTALL_LOCK"

# Install Composer dependencies if vendor/autoload.php is missing or manifests are newer
if [ ! -f "/var/www/vendor/autoload.php" ] || [ "/var/www/composer.lock" -nt "/var/www/vendor/autoload.php" ] || [ "/var/www/composer.json" -nt "/var/www/vendor/autoload.php" ]; then
    echo "Installing/updating Composer dependencies..."
    mkdir -p /var/www/storage
    touch "$INSTALL_LOCK"
    composer install --no-interaction --prefer-dist --optimize-autoloader
    rm -f "$INSTALL_LOCK"
else
    echo "Composer dependencies already installed and up to date."
fi

# Generate APP_KEY if empty or missing
if [ -f "/var/www/.env" ]; then
    if ! grep -E -q "^APP_KEY=[^[:space:]]+" /var/www/.env; then
        echo "APP_KEY is missing or empty. Generating application key..."
        php artisan key:generate --force
    else
        echo "APP_KEY is already set."
    fi
fi

# Configure storage symlink if not configured or broken
if [ ! -e "/var/www/public/storage" ]; then
    echo "Configuring storage link (php artisan storage:link)..."
    rm -f /var/www/public/storage
    mkdir -p /var/www/storage/app/public
    php artisan storage:link --relative || php artisan storage:link || true
else
    echo "Storage link is already configured and valid."
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
mkdir -p /var/www/storage/framework/cache /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/logs /var/www/bootstrap/cache

if [ -d "/var/www/storage" ]; then
    find /var/www/storage -type d -exec chmod 775 {} \; 2>/dev/null || true
    find /var/www/storage -type f -exec chmod 664 {} \; 2>/dev/null || true
fi

if [ -d "/var/www/bootstrap/cache" ]; then
    find /var/www/bootstrap/cache -type d -exec chmod 775 {} \; 2>/dev/null || true
    find /var/www/bootstrap/cache -type f -exec chmod 664 {} \; 2>/dev/null || true
fi

echo "Executing command: $@"
# Execute the CMD from Dockerfile or command from docker-compose
exec "$@"
