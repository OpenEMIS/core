#!/usr/bin/env bash
#
# openemis-core-cron.sh — OpenEMIS Core runtime tick.
#
# Runs once per cron invocation (every minute). Wraps `php artisan
# openemis-core:run` under flock so overlapping ticks never stack.
# Intentionally minimal — installation, cron hygiene and smoke-testing live
# in the companion script `install-openemis-core-cron.sh`.
#
# Self-locates by walking upward from this file looking for `api/artisan`.
# Override the search via:  export OPENEMIS_LARAVEL_ROOT=/abs/path/to/api
#
# Logs to <laravel>/storage/logs/openemis-core-cron.log

set -euo pipefail

#POCOR-9719: pure-bash path resolution, no interpreter fallback
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"

find_laravel_root() {
    if [[ -n "${OPENEMIS_LARAVEL_ROOT:-}" ]]; then
        echo "$OPENEMIS_LARAVEL_ROOT"
        return 0
    fi
    for c in "$SCRIPT_DIR/api" "$SCRIPT_DIR/core/api" "$SCRIPT_DIR/../api" "$SCRIPT_DIR/../core/api"; do
        if [[ -f "$c/artisan" ]]; then
            (cd "$c" && pwd -P)
            return 0
        fi
    done
    return 1
}

LARAVEL_ROOT="$(find_laravel_root)" || {
    echo "[openemis-core-cron] FATAL: cannot locate Laravel root from $SCRIPT_DIR. Set OPENEMIS_LARAVEL_ROOT=/abs/path or place this script beside api/." >&2
    exit 1
}

LOG_FILE="$LARAVEL_ROOT/storage/logs/openemis-core-cron.log"
LOCK_FILE="$LARAVEL_ROOT/storage/openemis-core-cron.lock"

mkdir -p "$(dirname "$LOG_FILE")"

exec /usr/bin/flock -n "$LOCK_FILE" \
    bash -c "cd '$LARAVEL_ROOT' && /usr/bin/env php artisan openemis-core:run >> '$LOG_FILE' 2>&1"
