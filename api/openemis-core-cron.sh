#!/usr/bin/env bash
#
# openemis-core-cron.sh — OpenEMIS Core runtime tick.
# Lives in the Laravel root, next to artisan. Cron calls this once a minute;
# the tick drains the async/alert queue (openemis-core:run), so messaging
# alerts and SMS go out automatically instead of needing a manual trigger.
#
#POCOR-9734: hardened to be system-aware instead of Linux/bash-specific —
# portable directory resolution, tool discovery, root-drops-to-runtime-user
# (so a root test-run never leaves root-owned lock/log files that block the
# www-data cron), and a flock-less fallback for hosts without util-linux.

set -euo pipefail

#POCOR-9734: portable script-dir resolution. The old `readlink -f` is GNU-only
# and fails on macOS/BSD; this POSIX form works everywhere.
SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd -P)
cd "$SCRIPT_DIR"

LOCK_FILE="storage/openemis-core-cron.lock"
LOG_FILE="storage/logs/openemis-core-cron.log"

#POCOR-9734: determine the runtime user that *should* own the artifacts.
# Override with OPENEMIS_RUN_USER; otherwise take the owner of the app root,
# then fall back to the first web user that exists on this host.
detect_run_user() {
    if [ -n "${OPENEMIS_RUN_USER:-}" ]; then
        printf '%s' "$OPENEMIS_RUN_USER"; return
    fi
    local owner
    owner=$(stat -c '%U' . 2>/dev/null || stat -f '%Su' . 2>/dev/null || true)
    if [ -n "$owner" ] && [ "$owner" != "root" ]; then
        printf '%s' "$owner"; return
    fi
    local u
    for u in www-data apache nginx http; do
        if id "$u" >/dev/null 2>&1; then printf '%s' "$u"; return; fi
    done
    printf '%s' "www-data"
}

#POCOR-9734: if invoked as root (manual test-run, or a root crontab), re-exec
# as the runtime user so every file created below — lock and log — is owned by
# that user, never root. This is the proper fix for "make the lock file owner
# www-data": drop privileges up front rather than chown after the fact.
if [ "$(id -u)" = "0" ]; then
    RUN_USER=$(detect_run_user)
    if [ "$RUN_USER" != "root" ] && id "$RUN_USER" >/dev/null 2>&1; then
        if command -v runuser >/dev/null 2>&1; then
            exec runuser -u "$RUN_USER" -- "$0" "$@"
        elif command -v setpriv >/dev/null 2>&1; then
            exec setpriv --reuid "$RUN_USER" --regid "$RUN_USER" --init-groups "$0" "$@"
        elif command -v su >/dev/null 2>&1; then
            exec su -s /bin/bash "$RUN_USER" "$SCRIPT_DIR/$(basename "$0")"
        fi
        # No privilege-drop tool available: fall through and self-heal ownership
        # of the artifacts after the run so the next www-data tick isn't blocked.
    fi
fi

#POCOR-9734: discover the interpreter instead of hardcoding an absolute path.
PHP_BIN="${PHP_BIN:-$(command -v php || true)}"
[ -n "$PHP_BIN" ] || { echo "openemis-core-cron: php not found in PATH" >&2; exit 127; }
mkdir -p "$(dirname "$LOG_FILE")"

run_tick() {
    "$PHP_BIN" artisan openemis-core:run >> "$LOG_FILE" 2>&1
}

#POCOR-9734: prefer flock for overlap protection; fall back to an atomic mkdir
# mutex on hosts that don't ship flock (e.g. macOS dev boxes) so ticks still
# never pile up.
if command -v flock >/dev/null 2>&1; then
    flock -n "$LOCK_FILE" "$PHP_BIN" artisan openemis-core:run >> "$LOG_FILE" 2>&1 || true
else
    LOCK_DIR="${LOCK_FILE}.d"
    if mkdir "$LOCK_DIR" 2>/dev/null; then
        trap 'rmdir "$LOCK_DIR" 2>/dev/null || true' EXIT
        run_tick
    fi
fi

#POCOR-9734: if we could not drop from root above, make sure the artifacts we
# just created are owned by the runtime user, so the regular www-data cron run
# can keep writing to them.
if [ "$(id -u)" = "0" ]; then
    RUN_USER=$(detect_run_user)
    if [ "$RUN_USER" != "root" ] && id "$RUN_USER" >/dev/null 2>&1; then
        chown "$RUN_USER" "$LOCK_FILE" "$LOG_FILE" "${LOCK_FILE}.d" 2>/dev/null || true
    fi
fi
