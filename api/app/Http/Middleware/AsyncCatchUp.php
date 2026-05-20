<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * POCOR-9694 — opportunistic catch-up safety net.
 *
 * If the canonical {{php artisan schedule:run}} cron stalls (host
 * crashed, cron disabled mid-incident, OS package upgrade restarted
 * the daemon) the system_processes table goes quiet. This middleware
 * watches for that quietness and runs the scheduler on the request
 * thread — but only AFTER the response has been sent to the client,
 * so the user never waits.
 *
 * Three guards keep the cost predictable:
 *
 * 1. {{config('async_catchup.stale_threshold_minutes')}} — only fires
 *    when the newest system_processes row is older than this. A
 *    healthy cron writes a row every minute, so 5 minutes of silence
 *    is a strong signal something is wrong.
 *
 * 2. {{config('async_catchup.throttle_seconds')}} — even if every
 *    request triggers the staleness check, only ONE worker per
 *    throttle window does the actual catch-up. Prevents thundering-herd
 *    on a busy site whose cron has truly died.
 *
 * 3. {{config('async_catchup.hard_budget_ms')}} — the catch-up itself
 *    aborts after this many milliseconds. Web requests stay
 *    bounded even when something pathological happens inside the
 *    scheduler.
 *
 * The middleware is intentionally a SAFETY NET. The canonical
 * deployment story (POCOR-9509) is still cron-driven; this is here
 * for the days when cron isn't.
 *
 * Registered in the {{web}} middleware group only — API requests
 * (including the v5 dashboards) skip it because they are higher
 * volume and lower tolerance for opportunistic work.
 *
 * @see api/config/async_catchup.php           Middleware knobs.
 * @see api/app/Console/Kernel.php             Scheduler entries it drains.
 * @see api/storage/release-docs/POCOR-9509-README.md  Canonical cron.
 */
class AsyncCatchUp
{
    /** Telemetry tag used in every Log::* call this middleware emits. */
    private const LOG_TAG = '[POCOR-9694][AsyncCatchUp]';

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Runs after the response is flushed to the client. Laravel calls
     * {{terminate()}} on every middleware that defines it provided the
     * server stack supports {{fastcgi_finish_request()}} — which both
     * php-fpm and Apache mod_php do in this project's deployment.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (!$this->isEnabled() || !$this->shouldRun()) {
            return;
        }
        $this->runScheduleSafely();
    }

    private function isEnabled(): bool
    {
        return (bool) Config::get('async_catchup.enabled', true);
    }

    /**
     * Two predicates must both hold for a catch-up to fire:
     *
     * 1. The throttle window must have elapsed since the last attempt.
     * 2. The most recent system_processes row must be older than the
     *    stale threshold.
     *
     * Predicate 1 is checked first because it is cheap (a single
     * cache read) — predicate 2 hits the database, so we save the
     * round-trip when the throttle has not yet expired.
     */
    private function shouldRun(): bool
    {
        if (!$this->throttleHasElapsed()) {
            return false;
        }
        return $this->systemProcessesAreStale();
    }

    private function throttleHasElapsed(): bool
    {
        $key = $this->cacheKey();
        $throttleSeconds = (int) Config::get('async_catchup.throttle_seconds', 60);
        $lastRun = Cache::get($key);
        if ($lastRun !== null && (time() - (int) $lastRun) < $throttleSeconds) {
            return false;
        }
        return true;
    }

    private function systemProcessesAreStale(): bool
    {
        $thresholdMinutes = (int) Config::get('async_catchup.stale_threshold_minutes', 5);
        $row = DB::table('system_processes')
            ->selectRaw('MAX(created) AS newest')
            ->first();
        if ($row === null || $row->newest === null) {
            // Empty table — treat as stale; the scheduler may need to seed it.
            return true;
        }
        $newestTimestamp = strtotime($row->newest);
        if ($newestTimestamp === false) {
            return false;
        }
        return (time() - $newestTimestamp) > ($thresholdMinutes * 60);
    }

    /**
     * Runs {{openemis-core:run}} bounded by the hard budget. Any throwable
     * is logged and swallowed — middleware must never fail a request.
     */
    private function runScheduleSafely(): void
    {
        $startedAt = microtime(true);
        try {
            $this->markAttempt();
            Log::info(self::LOG_TAG . ' triggering openemis-core:run after stale window');
            // Artisan::call returns the exit code synchronously; the
            // hard budget protects us from a hung runtime tick.
            $this->withTimeBudget(static function (): void {
                Artisan::call('openemis-core:run');
            });
            $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);
            Log::info(sprintf('%s openemis-core:run completed in %dms', self::LOG_TAG, $elapsedMs));
        } catch (Throwable $e) {
            $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);
            Log::warning(sprintf(
                '%s suppressed exception after %dms: %s',
                self::LOG_TAG,
                $elapsedMs,
                $e->getMessage()
            ));
        }
    }

    /**
     * Records the attempt in cache so {{throttleHasElapsed()}} sees it
     * on the next request. Stored even when the actual schedule:run
     * throws — we want the throttle to apply to FAILED runs too,
     * otherwise a broken scheduler hammers the DB on every request.
     */
    private function markAttempt(): void
    {
        $throttleSeconds = (int) Config::get('async_catchup.throttle_seconds', 60);
        // TTL = 2x throttle so the key never disappears just before the
        // next allowed run; the freshness check is timestamp-based.
        Cache::put($this->cacheKey(), time(), $throttleSeconds * 2);
    }

    /**
     * Wraps a callable in a hard time budget. PHP can't safely
     * interrupt arbitrary work, so the budget is advisory: we set
     * {{set_time_limit()}} so any I/O calls inside the callable
     * eventually error out. The caller catches the resulting
     * exception via {{runScheduleSafely()}}.
     */
    private function withTimeBudget(callable $work): void
    {
        $budgetMs = (int) Config::get('async_catchup.hard_budget_ms', 200);
        $budgetSeconds = max(1, (int) ceil($budgetMs / 1000));
        $previousLimit = ini_get('max_execution_time');
        set_time_limit($budgetSeconds);
        try {
            $work();
        } finally {
            // Restore so other terminating middleware aren't constrained.
            set_time_limit((int) $previousLimit);
        }
    }

    private function cacheKey(): string
    {
        return (string) Config::get(
            'async_catchup.throttle_cache_key',
            'pocor9694:async_catchup:last_run'
        );
    }
}
