#!/bin/bash

set -e

echo "Starting OpenEMIS Core Container...."

WWW_PATH="/var/www/html/core"
PERSIST_CONFIG_PATH="/config"
API_PATH="$WWW_PATH/api"
CONFIG_PATH="$WWW_PATH/config"

if [ -f "$PERSIST_CONFIG_PATH/.env" ]; then
    cp "$PERSIST_CONFIG_PATH/.env" "$API_PATH/.env"
else
    cp "$API_PATH/.env.example" "$API_PATH/.env"
    sed -i "s|DB_HOST=.*|DB_HOST=openemis-core-database|g" "$API_PATH/.env"
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=root|g" "$API_PATH/.env"
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=root|g" "$API_PATH/.env"
    cd "$API_PATH"
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    php artisan key:generate
    php artisan jwt:secret
    php artisan config:cache
    cp "$API_PATH/.env" "$PERSIST_CONFIG_PATH/.env" 
fi

if [ -f "$PERSIST_CONFIG_PATH/app_local.php" ]; then
    cp "$PERSIST_CONFIG_PATH/app_local.php" "$CONFIG_PATH/app_local.php"
fi

# POCOR-9694: ensure OpenEMIS Runtime log file exists and is writable by www-data
RUNTIME_LOG="$API_PATH/storage/logs/openemis-core-run.log"
mkdir -p "$(dirname "$RUNTIME_LOG")"
touch "$RUNTIME_LOG"
chown www-data:www-data "$RUNTIME_LOG"
chmod 664 "$RUNTIME_LOG"

# POCOR-9694: start cron daemon for openemis-core:run single-cron entry-point
service cron start || /usr/sbin/cron

apache2-foreground &

# === Watch for app_local.php creation ===

inotifywait -m -e create -e close_write -e modify "$CONFIG_PATH" |
while read -r directory event filename; do
    if [ "$filename" = "app_local.php" ]; then
        cp "$CONFIG_PATH/app_local.php" "$PERSIST_CONFIG_PATH/app_local.php"
        # break  # stop after first save
    fi
done


inotifywait -m -e modify -e close_write "$API_PATH" |
while read -r directory event filename; do
    if [ "$filename" = ".env" ]; then
        cp "$API_PATH/.env" "$PERSIST_CONFIG_PATH/.env"
    fi
done