<?php
declare(strict_types=1);

namespace System\Model\Table;

use ArrayObject;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;

/**
 * POCOR-9694 — Async Services overview dashboard.
 *
 * Landing page for the {{Administration → Async Services}} group. Renders
 * five KPI tiles plus a recent-activity table:
 *
 *   1. Failed Jobs        — {{failed_jobs}} count
 *   2. Stuck Processes    — {{system_processes}} status IN (1,2) older than 1h
 *   3. Webhook Failures   — {{webhook_queue}} status = -1
 *   4. Queue Backlog      — {{alert_queue}} status = 0
 *   5. Heartbeat          — time since the last runtime_heartbeat tick ({{tasks}})
 *
 * Each tile links to its dedicated detail screen. The activity table
 * underneath shows the last system_processes rows so operators can see
 * what the runtime is actually doing.
 *
 * @see plugins/System/templates/Element/async_overview.php
 */
class AsyncServicesOverviewTable extends AsyncServicesAdminTable
{
    use AsyncTabsTrait; //POCOR-9719: horizontal tab bar shared by all 6 screens

    /** Mirrors the StuckProcesses screen so the count matches what the user clicks through to. */
    private const STUCK_THRESHOLD_HOURS = 1;
    private const ACTIVE_STATUSES = [1, 2];

    //POCOR-9734: heartbeat freshness tiers. The tick runs every minute, so a gap
    // beyond BEHIND means it skipped at least one minute and we explain why.
    private const HEARTBEAT_BEHIND_MINUTES = 2;
    private const HEARTBEAT_STALE_MINUTES = 5;

    public function initialize(array $config): void
    {
        $this->setTable('system_processes');
        parent::initialize($config);
    }

    protected function pageTitle(): string
    {
        return 'Overview';
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        $this->setupAsyncTabs(); //POCOR-9719: horizontal tab bar

        // Hide the noisier columns; the dashboard's table is a "recent activity"
        // strip, not a workbench.
        foreach (['callable_event', 'executed_count', 'params', 'process_id',
                  'created_user_id', 'modified_user_id', 'modified', 'end_date'] as $hide) {
            $this->field($hide, ['visible' => false]);
        }
        $this->setFieldOrder(['name', 'status', 'start_date', 'model', 'created']);

        $extra['elements']['control'] = [
            'name' => 'System.async_overview',
            'data' => $this->buildKpiData(),
            'options' => [],
            'order' => 1,
        ];
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): void
    {
        $query->order([$this->aliasField('created') => 'DESC']);
    }

    /**
     * Status rendering matches SystemProcesses so the activity strip reads
     * the same as the dedicated screen.
     */
    public function onGetStatus(EventInterface $event, Entity $entity)
    {
        $labels = [1 => 'New', 2 => 'Running', 3 => 'Completed', -1 => 'Abort', -2 => 'Error'];
        return $labels[$entity->status] ?? (string) $entity->status;
    }

    /**
     * Single-pass KPI fetch. Five cheap aggregate queries; each is a count
     * over a small indexed predicate, so the entire batch returns in a
     * handful of milliseconds even on a busy database.
     */
    private function buildKpiData(): array
    {
        $conn = ConnectionManager::get('default');

        $failedJobsCount    = (int) $this->scalar($conn, 'SELECT COUNT(*) FROM failed_jobs');
        $stuckProcessCount  = (int) $this->scalar(
            $conn,
            sprintf(
                'SELECT COUNT(*) FROM system_processes WHERE status IN (%s)'
                . ' AND created < NOW() - INTERVAL %d HOUR',
                implode(',', self::ACTIVE_STATUSES),
                self::STUCK_THRESHOLD_HOURS
            )
        );
        $webhookFailureCount = (int) $this->scalar($conn, 'SELECT COUNT(*) FROM webhook_queue WHERE status = -1');
        $queueBacklogCount   = (int) $this->scalar($conn, 'SELECT COUNT(*) FROM alert_queue WHERE status = 0');
        //POCOR-9734: read the real heartbeat — the dedicated runtime_heartbeat row that
        // openemis-core:run rewrites every tick (api OpenemisCoreRunCommand::recordHeartbeat).
        // The old MAX(created) FROM system_processes only saw generic job rows, so it kept
        // reporting "tick not running" even while the cron beat every minute.
        $latestHeartbeat     = $this->scalar(
            $conn,
            "SELECT MAX(COALESCE(completed_at, started_at)) FROM tasks WHERE task_type = 'runtime_heartbeat'"
        );
        //POCOR-9734: jobs the runtime is actively working right now — a tick that
        // overruns a minute is usually busy clearing one of these.
        $activeProcessCount  = (int) $this->scalar(
            $conn,
            sprintf('SELECT COUNT(*) FROM system_processes WHERE status IN (%s)', implode(',', self::ACTIVE_STATUSES))
        );

        return [
            'tiles' => [
                $this->tile('Failed Jobs',     $failedJobsCount,    ['action' => 'FailedJobs']),
                $this->tile('Frozen Jobs',     $stuckProcessCount,  ['action' => 'StuckProcesses']),
                $this->tile('Failed Webhooks', $webhookFailureCount,['action' => 'WebhookFailures']),
                $this->tile('Waiting Jobs',    $queueBacklogCount,  ['action' => 'QueueBacklog']),
            ],
            //POCOR-9734: heartbeat now explains *why* it is behind, not just when it last beat.
            'heartbeat' => $this->describeHeartbeat($latestHeartbeat, $activeProcessCount, $queueBacklogCount),
        ];
    }

    private function tile(string $label, int $count, array $urlExtras): array
    {
        return [
            'label' => $label,
            'count' => $count,
            'url'   => array_merge(['plugin' => 'System', 'controller' => 'Systems'], $urlExtras),
            'severity' => $count > 0 ? 'attention' : 'ok',
        ];
    }

    /**
     * Returns a tuple {{ ['text' => ..., 'severity' => ok|attention|stale] }}
     * describing how fresh the last runtime_heartbeat tick is — i.e. whether the
     * openemis-core runtime is actually ticking.
     *
     * POCOR-9734: when the heartbeat is behind (the tick should fire every
     * minute), a bare timestamp is unhelpful. We now say *why* it is behind:
     *   - a previous tick is still running (lock held) — overlap was prevented;
     *   - a large batch is being processed, so a tick runs longer than a minute;
     *   - the tick is not running at all (cron stopped / not installed).
     */
    private function describeHeartbeat(?string $latestHeartbeat, int $activeProcessCount, int $queueBacklogCount): array
    {
        if ($latestHeartbeat === null) {
            return [
                'text' => __('No heartbeat recorded yet — the runtime tick has never run. Check that the openemis-core cron is installed.'),
                'severity' => 'stale',
            ];
        }

        $time = FrozenTime::parse($latestHeartbeat);
        $minutesAgo = (int) $time->diffInMinutes(FrozenTime::now());
        $base = __('Last heartbeat: ') . $this->formatDateTime($time) . ' (' . $time->timeAgoInWords(['accuracy' => 'minute']) . ')';

        //POCOR-9734: mail-config drift is checked BEFORE the on-schedule early-return
        // below, deliberately — the scheduler can be ticking perfectly on time every
        // minute (this is exactly what happened on TO MET TST) while every email still
        // fails, because Laravel is dialing a host baked into a compiled config cache
        // that no longer matches api/.env. A heartbeat that only measures "did the tick
        // fire" would report "ok" throughout that entire outage.
        if (($drift = $this->staleMailConfig()) !== null) {
            return [
                'text' => $base . ' — ' . __('the compiled Laravel config cache is stale: ')
                    . $this->describeMailDrift($drift) . ' '
                    . __('Run `php artisan config:clear` on the api/ deployment (only re-run `config:cache` afterwards if you intend to keep caching config).'),
                'severity' => 'attention',
            ];
        }

        // Beating on schedule — nothing to explain.
        if ($minutesAgo < self::HEARTBEAT_BEHIND_MINUTES) {
            return ['text' => $base, 'severity' => 'ok'];
        }

        // Behind schedule — surface the most likely reason.
        //POCOR-9734: a log file the cron user cannot write to is the prime suspect —
        // the runtime fatals on its first write. Most often a root-owned log left
        // behind by a manual root run (the very thing the wrapper now prevents).
        if (($blocked = $this->blockedLogFile()) !== null) {
            return [
                'text' => $base . ' — ' . sprintf(
                    /* %1$s = log path, %2$s = owning user */
                    (string) __('the runtime cannot write its log file %1$s (blocked — owned by "%2$s"). The cron user has no write access; consult your system administrator.'),
                    $blocked['path'],
                    $blocked['owner']
                ),
                'severity' => 'stale',
            ];
        }

        if ($this->tickInProgress()) {
            return [
                'text' => $base . ' — ' . __('a previous tick is still running (lock held); this minute was skipped to prevent overlap. Normal during a large send.'),
                'severity' => 'attention',
            ];
        }

        $inFlight = $activeProcessCount + $queueBacklogCount;
        if ($inFlight > 0) {
            return [
                'text' => $base . ' — ' . sprintf(
                    /* %1$d active, %2$d waiting */
                    (string) __('a large batch is being processed (%1$d active, %2$d waiting); ticks are running longer than a minute.'),
                    $activeProcessCount,
                    $queueBacklogCount
                ),
                'severity' => 'attention',
            ];
        }

        // Nothing running and nothing queued, yet no recent tick → the cron is not firing.
        return [
            'text' => $base . ' — ' . __('the runtime tick is not running. Check that the openemis-core cron is installed and active.'),
            'severity' => $minutesAgo > self::HEARTBEAT_STALE_MINUTES ? 'stale' : 'attention',
        ];
    }

    /**
     * POCOR-9734: is a runtime tick holding the cron lock right now?
     *
     * The wrapper (api/openemis-core-cron.sh) guards each tick with flock on
     * `api/storage/openemis-core-cron.lock`, falling back to an atomic mkdir
     * mutex (`*.lock.d`) on hosts without flock. We detect either: a held flock
     * (non-blocking acquire fails) or the presence of the mkdir mutex dir.
     * Best-effort — any failure means "cannot tell", so we report not-in-progress.
     */
    private function tickInProgress(): bool
    {
        $lock = ROOT . DS . 'api' . DS . 'storage' . DS . 'openemis-core-cron.lock';

        // mkdir-mutex fallback (no-flock hosts): the dir exists only while a tick runs.
        if (is_dir($lock . '.d')) {
            return true;
        }
        if (!is_file($lock)) {
            return false;
        }

        $fp = @fopen($lock, 'r');
        if ($fp === false) {
            return false;
        }
        // If we cannot take the lock non-blocking, a tick currently holds it.
        $held = !@flock($fp, LOCK_EX | LOCK_NB);
        if (!$held) {
            @flock($fp, LOCK_UN);
        }
        @fclose($fp);

        return $held;
    }

    /**
     * POCOR-9734: is any runtime log file unwritable by the web/cron user?
     *
     * is_writable() reflects the effective user running PHP (www-data), so a
     * root-owned log left by a manual root run shows up here as not-writable.
     * Returns {{ ['path' => ..., 'owner' => ...] }} for the first offending file,
     * or null when everything is writable. Only existing files are checked — a
     * missing log is created on first write, not a permission problem.
     */
    private function blockedLogFile(): ?array
    {
        $logDir = ROOT . DS . 'api' . DS . 'storage' . DS . 'logs';
        $candidates = [
            $logDir . DS . 'openemis-core-cron.log',
            $logDir . DS . 'laravel-' . FrozenTime::now()->format('Y-m-d') . '.log',
            $logDir,
        ];

        foreach ($candidates as $path) {
            if (file_exists($path) && !is_writable($path)) {
                return ['path' => $path, 'owner' => $this->fileOwnerName($path)];
            }
        }

        return null;
    }

    /**
     * POCOR-9734: does Laravel's compiled config cache (api/bootstrap/cache/config.php)
     * disagree with api/.env on which mailer/host to use?
     *
     * `php artisan config:cache` freezes every env() call into that one file; every
     * .env edit made afterwards is silently ignored by the running app until the cache
     * is cleared. That mismatch is invisible from the UI otherwise — the queue backlog
     * and failed-job tiles just show generic connection errors, and diagnosing it today
     * means SSH + `php artisan tinker`.
     *
     * Filesystem-only, no framework boot, no network I/O — two small file reads and one
     * `include` of a plain PHP array literal, executed only when this one admin overview
     * page renders. Best-effort: any missing file or unparsable cache returns null
     * (nothing to report) rather than guessing or throwing.
     *
     * Params are injectable purely for testability; production callers always use the
     * real api/ paths via the defaults.
     *
     * @return array{cached_mailer: ?string, env_mailer: string, cached_host: ?string, env_host: ?string}|null
     */
    private function staleMailConfig(?string $envPath = null, ?string $cachePath = null): ?array
    {
        $envPath = $envPath ?? (ROOT . DS . 'api' . DS . '.env');
        $cachePath = $cachePath ?? (ROOT . DS . 'api' . DS . 'bootstrap' . DS . 'cache' . DS . 'config.php');

        if (!is_file($envPath) || !is_file($cachePath)) {
            // No compiled cache (common — most installs never run config:cache) means
            // Laravel reads .env live on every tick; nothing can be stale.
            return null;
        }

        $envValues = $this->readDotEnv($envPath);

        //POCOR-9734: Laravel's Dotenv loader prefers .env.{APP_ENV} over the plain
        // .env when it exists — mirror that so we diff against whichever file is
        // actually authoritative, not just the one most people edit.
        $appEnv = $envValues['APP_ENV'] ?? null;
        if ($appEnv !== null && $appEnv !== '') {
            $overridePath = dirname($envPath) . DS . '.env.' . $appEnv;
            if (is_file($overridePath)) {
                $envValues = $this->readDotEnv($overridePath) + $envValues;
            }
        }

        $envMailer = $envValues['MAIL_MAILER'] ?? 'smtp';
        $envHost = $envValues['MAIL_HOST'] ?? null;

        try {
            $cached = include $cachePath; // Laravel writes this file as a bare `return [...]`
        } catch (\Throwable $e) {
            return null; // corrupt/unreadable cache — don't guess, don't break the dashboard
        }

        if (!is_array($cached)) {
            return null;
        }

        $cachedMailer = $cached['mail']['default'] ?? null;
        $cachedHost = $cachedMailer !== null
            ? ($cached['mail']['mailers'][$cachedMailer]['host'] ?? null)
            : null;

        $mailerDrifted = $cachedMailer !== null && $cachedMailer !== $envMailer;
        //POCOR-9734: only a real drift if BOTH sides actually have a host to compare —
        // mailers like 'log'/'array'/'sendmail' have no host at all, and reporting a
        // mismatch against null would be a false positive on perfectly valid configs.
        $hostDrifted = $cachedHost !== null && $envHost !== null && $cachedHost !== $envHost;

        if (!$mailerDrifted && !$hostDrifted) {
            return null;
        }

        return [
            'cached_mailer' => $cachedMailer,
            'env_mailer' => $envMailer,
            'cached_host' => $cachedHost,
            'env_host' => $envHost,
        ];
    }

    /** POCOR-9734: renders whichever half of the drift (host and/or mailer) actually differs. */
    private function describeMailDrift(array $drift): string
    {
        $parts = [];

        if ($drift['cached_host'] !== null && $drift['cached_host'] !== $drift['env_host']) {
            $parts[] = sprintf(
                /* %1$s = cached host, %2$s = current .env host */
                (string) __('mail host is cached as "%1$s" but api/.env now resolves to "%2$s"'),
                $drift['cached_host'],
                $drift['env_host']
            );
        }

        if ($drift['cached_mailer'] !== null && $drift['cached_mailer'] !== $drift['env_mailer']) {
            $parts[] = sprintf(
                /* %1$s = cached mailer, %2$s = current .env mailer */
                (string) __('default mailer is cached as "%1$s" but api/.env now resolves to "%2$s"'),
                $drift['cached_mailer'],
                $drift['env_mailer']
            );
        }

        return implode('; ', $parts) . '.';
    }

    /**
     * POCOR-9734: minimal, dependency-free KEY=VALUE parser for .env files — close
     * enough to phpdotenv for drift detection (skips comments/blank lines, strips an
     * optional leading `export `, strips surrounding quotes and unquoted inline
     * comments). Not a full Dotenv implementation — this never feeds real app config,
     * it only diffs values for the diagnostic above.
     */
    private function readDotEnv(string $path): array
    {
        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            [$key, $value] = explode('=', $line, 2);
            $value = trim($value);

            //POCOR-9734: a quoted value ends at its matching closing quote — anything
            // after that (including ` # trailing comment`) is NOT part of the value.
            // Checking the comment marker before unwrapping quotes (as a naive
            // trim-then-strip would) leaves the closing quote+comment stuck to the
            // value; match the quoted span explicitly instead.
            if (($value[0] ?? '') === '"' && preg_match('/^"([^"]*)"/', $value, $m)) {
                $value = $m[1];
            } elseif (($value[0] ?? '') === "'" && preg_match("/^'([^']*)'/", $value, $m)) {
                $value = $m[1];
            } elseif (str_contains($value, ' #')) {
                $value = rtrim(substr($value, 0, strpos($value, ' #'))); // strip unquoted inline comment
            }

            $values[trim($key)] = $value;
        }

        return $values;
    }

    /** POCOR-9734: resolve a path's owning username (falls back to numeric uid). */
    private function fileOwnerName(string $path): string
    {
        $uid = @fileowner($path);
        if ($uid === false) {
            return (string) __('unknown');
        }
        if (function_exists('posix_getpwuid')) {
            $info = @posix_getpwuid($uid);
            if (!empty($info['name'])) {
                return $info['name'];
            }
        }

        return (string) $uid;
    }

    private function scalar($conn, string $sql)
    {
        $row = $conn->execute($sql)->fetch(0);
        return $row[0] ?? null;
    }
}
