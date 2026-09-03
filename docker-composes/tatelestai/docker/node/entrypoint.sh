#!/bin/sh

set -e

echo "Starting Vue entrypoint script ($*)..."

cd /app

# Check if node_modules is missing or empty, or if package.json/package-lock.json was modified
if [ ! -d "node_modules" ] || [ ! -d "node_modules/vue" ] || [ ! -f "node_modules/.package-lock.json" ]; then
    echo "Installing Vue dependencies with npm install..."
    npm install
elif [ "package.json" -nt "node_modules" ] || [ "package-lock.json" -nt "node_modules" ]; then
    echo "Changes detected in package manifests. Updating Vue dependencies..."
    npm install
    touch node_modules
else
    echo "Vue dependencies already installed and up to date."
fi

# Ensure permissions on package-lock.json if updated
if [ -f "package-lock.json" ]; then
    chown ${UID:-1000}:${GID:-1000} package-lock.json 2>/dev/null || true
fi

echo "Executing command: $@"
exec "$@"

