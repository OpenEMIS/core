<?php
/**
 * POCOR-9694 — Async Catch-Up middleware configuration.
 *
 * Knobs that govern when and how aggressively the AsyncCatchUp
 * middleware fires. Defaults are tuned for a deployment that already
 * has the canonical {{php artisan schedule:run}} cron in place: the
 * middleware is a SAFETY NET, not the primary scheduler.
 *
 * @see api/app/Http/Middleware/AsyncCatchUp.php
 * @see api/storage/release-docs/POCOR-9509-README.md ("Single-cron
 *      alternative" section)
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch. When false, the middleware is a no-op. Useful for
    | environments that prefer cron-only behaviour, or for emergency
    | shut-off without removing the middleware from the Kernel stack.
    |
    */
    'enabled' => env('ASYNC_CATCHUP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Stale Threshold (minutes)
    |--------------------------------------------------------------------------
    |
    | How long since the most recent system_processes row before the
    | middleware decides cron has stalled and intervention is warranted.
    | Set well above the cron interval so a slow-but-running cron does not
    | trigger duplicate work.
    |
    */
    'stale_threshold_minutes' => (int) env('ASYNC_CATCHUP_STALE_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Hard Budget (milliseconds)
    |--------------------------------------------------------------------------
    |
    | Upper bound on time spent in the middleware. Once this budget is
    | exhausted the catch-up exits even if more work remains. Keeps web
    | request latency predictable.
    |
    */
    'hard_budget_ms' => (int) env('ASYNC_CATCHUP_BUDGET_MS', 200),

    /*
    |--------------------------------------------------------------------------
    | Throttle (seconds)
    |--------------------------------------------------------------------------
    |
    | Minimum seconds between two catch-up runs across the whole app, no
    | matter how many requests fire in between. Prevents thundering-herd
    | when traffic spikes and cron is genuinely down.
    |
    */
    'throttle_seconds' => (int) env('ASYNC_CATCHUP_THROTTLE_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | Cache Key
    |--------------------------------------------------------------------------
    |
    | Key under which the throttle timestamp is stored. Defaults to a
    | namespaced key so it never collides with application caches.
    |
    */
    'throttle_cache_key' => 'pocor9694:async_catchup:last_run',

];
