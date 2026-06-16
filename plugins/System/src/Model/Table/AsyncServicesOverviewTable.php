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
 *   5. Heartbeat          — time since the most recent system_processes row
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
        $latestHeartbeat     = $this->scalar($conn, 'SELECT MAX(created) FROM system_processes');
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
     * describing how fresh the last system_processes write is — a proxy for
     * "is the runtime ticking?".
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

        // Beating on schedule — nothing to explain.
        if ($minutesAgo < self::HEARTBEAT_BEHIND_MINUTES) {
            return ['text' => $base, 'severity' => 'ok'];
        }

        // Behind schedule — surface the most likely reason.
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

    private function scalar($conn, string $sql)
    {
        $row = $conn->execute($sql)->fetch(0);
        return $row[0] ?? null;
    }
}
