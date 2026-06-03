#!/usr/bin/env bash
#
# openemis-core-cron.sh — OpenEMIS Core runtime tick.
# Lives in the Laravel root, next to artisan. No path detection needed.
# Wrap-and-forget: cron calls this, flock prevents overlapping ticks.
#
#POCOR-9719: simplified from POCOR-9694 — dropped find_laravel_root,
#env-var overrides, and python fallback now that the script is colocated with artisan.

set -euo pipefail
cd "$(dirname "$(readlink -f "$0")")"

exec /usr/bin/flock -n storage/openemis-core-cron.lock \
    /usr/bin/env php artisan openemis-core:run >> storage/logs/openemis-core-cron.log 2>&1
