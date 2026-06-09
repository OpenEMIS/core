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

        return [
            'tiles' => [
                $this->tile('Failed Jobs',     $failedJobsCount,    ['action' => 'FailedJobs']),
                $this->tile('Frozen Jobs',     $stuckProcessCount,  ['action' => 'StuckProcesses']),
                $this->tile('Failed Webhooks', $webhookFailureCount,['action' => 'WebhookFailures']),
                $this->tile('Waiting Jobs',    $queueBacklogCount,  ['action' => 'QueueBacklog']),
            ],
            'heartbeat' => $this->describeHeartbeat($latestHeartbeat),
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
     */
    private function describeHeartbeat(?string $latestHeartbeat): array
    {
        if ($latestHeartbeat === null) {
            return ['text' => __('No heartbeat recorded yet.'), 'severity' => 'stale'];
        }
        $time = FrozenTime::parse($latestHeartbeat);
        $minutesAgo = $time->diffInMinutes(FrozenTime::now());
        $severity = $minutesAgo > 5 ? 'stale' : 'ok';

        return [
            'text' => __('Last heartbeat: ') . $this->formatDateTime($time), //POCOR-9719
            'severity' => $severity,
        ];
    }

    private function scalar($conn, string $sql)
    {
        $row = $conn->execute($sql)->fetch(0);
        return $row[0] ?? null;
    }
}
