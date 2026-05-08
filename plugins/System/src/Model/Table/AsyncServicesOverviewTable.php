<?php
declare(strict_types=1);

namespace System\Model\Table;

/**
 * POCOR-9694 — Async Services overview screen.
 *
 * Landing page for the {{Administration → Async Services}} group. The full
 * implementation (F7 follow-up) will read scheduler last-run timestamps,
 * stuck-process counts, queue depths, and recent error totals into a single
 * health summary. For now this is a placeholder that points operators at
 * the four detail pages in the sidebar.
 */
class AsyncServicesOverviewTable extends AsyncServicesAdminTable
{
    public function initialize(array $config): void
    {
        $this->setTable('system_processes');
        parent::initialize($config);
    }

    protected function describeScreen(): string
    {
        return 'Async Services overview — placeholder until POCOR-9694 F7 ships.'
            . ' Use the sidebar links to inspect failed jobs, stuck processes,'
            . ' webhook failures, and queue backlog directly.';
    }
}
