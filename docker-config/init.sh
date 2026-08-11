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
    #POCOR-9734: key:generate/jwt:secret must run ONLY on first boot — regenerating
    # them on every restart would invalidate every previously-issued JWT and any
    # data encrypted under the old APP_KEY. Keep them inside this one-time branch.
    php artisan key:generate
    php artisan jwt:secret
    cp "$API_PATH/.env" "$PERSIST_CONFIG_PATH/.env"
fi

#POCOR-9734: refresh the compiled config cache on EVERY boot, not just the very
# first one. The old code ran config:clear/config:cache only inside the
# "no persisted .env yet" branch above — every later restart took the other
# branch, copied the persisted .env back in, and left whatever got cached on
# that first boot untouched. That is exactly how a fixed .env can look
# "correctly configured" forever while the app still dials the value baked in
# on day one (e.g. .env.example's MAIL_HOST=mailhog, since only DB_* is patched
# before that first cache run): the sysAdmin corrects .env, the container
# restarts, and the years-old cache keeps winning. Running this unconditionally,
# after the branch above, guarantees every boot re-caches from whatever .env is
# in place at that moment — first boot or the hundredth restart alike.
cd "$API_PATH"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache

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
        #POCOR-9734: re-cache immediately on a live .env edit too, not just at boot —
        # otherwise an admin who edits .env on a running container (no restart) still
        # hits the same stale-cache trap this whole change exists to close. Apache and
        # the per-minute cron tick each boot a fresh PHP process per request/run, so
        # the very next request/tick after this re-cache sees the corrected value.
        # `|| true`: a save-in-progress (partial write) must not kill the watch loop —
        # worst case that one edit is picked up on the following write event instead.
        (cd "$API_PATH" && php artisan config:clear && php artisan config:cache) || true
    fi
done