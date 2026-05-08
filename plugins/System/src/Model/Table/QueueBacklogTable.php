<?php
declare(strict_types=1);

namespace System\Model\Table;

/**
 * POCOR-9694 — Queue Backlog admin screen.
 *
 * Aggregate view of pending work across every queue surface. The F7
 * follow-up will compute live counts from {{alert_queue}}, {{jobs}},
 * and {{webhook_queue}} into a single dashboard. This stub points at
 * {{alert_queue}} as the most operationally noisy of the three; until
 * F7 lands operators see the alert backlog directly and can cross-check
 * the other two queues by clicking through to their dedicated screens.
 */
class QueueBacklogTable extends AsyncServicesAdminTable
{
    public function initialize(array $config): void
    {
        $this->setTable('alert_queue');
        parent::initialize($config);
    }

    protected function describeScreen(): string
    {
        return 'Pending alert_queue rows. Full backlog dashboard (alerts +'
            . ' jobs + webhook_queue combined into one view) ships in the'
            . ' POCOR-9694 F7 follow-up.';
    }
}
