<?php
declare(strict_types=1);

namespace System\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;

/**
 * POCOR-9694 — Stuck Processes admin screen.
 *
 * Surfaces rows in {{system_processes}} whose status is still NEW(1) or
 * RUNNING(2) older than {{STUCK_THRESHOLD_HOURS}} hours. The 1-day global
 * stale-sweep in {{CheckAndQueueAlerts::handle()}} reaps these eventually,
 * but the operator needs visibility BEFORE that sweep fires so they can
 * intervene on persistent failures.
 */
class StuckProcessesTable extends AsyncServicesAdminTable
{
    /** A row older than this with status IN (1,2) is considered stuck. */
    private const STUCK_THRESHOLD_HOURS = 1;

    /** Status codes that mean "started but never finished". */
    private const ACTIVE_STATUSES = [1, 2];

    public function initialize(array $config): void
    {
        $this->setTable('system_processes');
        parent::initialize($config);
    }

    protected function describeScreen(): string
    {
        return sprintf(
            'system_processes rows still NEW(1) or RUNNING(2) for more than'
            . ' %d hour(s). Use this to spot async work that started but'
            . ' never finished — typically a worker crash or a hung exec().',
            self::STUCK_THRESHOLD_HOURS
        );
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): void
    {
        $threshold = sprintf(
            'NOW() - INTERVAL %d HOUR',
            self::STUCK_THRESHOLD_HOURS
        );
        $query->where([
            $this->aliasField('status IN') => self::ACTIVE_STATUSES,
            $this->aliasField('created <') => $query->newExpr($threshold),
        ]);
    }
}
