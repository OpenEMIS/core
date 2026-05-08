<?php
declare(strict_types=1);

namespace System\Model\Table;

/**
 * POCOR-9694 — Failed Jobs admin screen.
 *
 * Reads Laravel's {{failed_jobs}} table directly. The same table is
 * written by the Laravel queue worker when a job exceeds its retry
 * budget, so this screen is the single point of operator visibility
 * for queue-side failures across alerts, webhooks, and any future
 * async work.
 *
 * Columns of interest: {{uuid}}, {{connection}}, {{queue}},
 * {{exception}}, {{failed_at}}.
 */
class FailedJobsTable extends AsyncServicesAdminTable
{
    public function initialize(array $config): void
    {
        $this->setTable('failed_jobs');
        parent::initialize($config);
    }

    protected function describeScreen(): string
    {
        return 'Laravel failed_jobs — every queued task that exhausted its'
            . ' retry budget. Filter by queue or by failed_at to scope an'
            . ' incident; investigate {{exception}} for the root cause.';
    }
}
