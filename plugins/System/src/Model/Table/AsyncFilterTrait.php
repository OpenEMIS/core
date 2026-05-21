<?php
declare(strict_types=1);

namespace System\Model\Table;

use ArrayObject;
use Cake\Routing\Router;

//POCOR-9719: shared filter dropdown for the 5 Async Services pages.
//Replaces 5 separate sidebar entries with one entry + a dropdown.
//Selecting an option navigates to the corresponding existing controller
//action — each Table keeps its own indexBeforeQuery and security_function row.
trait AsyncFilterTrait
{
    /**
     * Filter key => [human label, SystemsController action].
     * The label is wrapped in __() at render time.
     */
    private const ASYNC_FILTERS = [
        'completed_jobs'  => ['Completed Jobs',  'SystemProcesses'],
        'failed_jobs'     => ['Failed Jobs',     'FailedJobs'],
        'frozen_jobs'     => ['Stuck Tasks',     'StuckProcesses'],
        'failed_webhooks' => ['Failed Webhooks', 'WebhookFailures'],
        'waiting_jobs'    => ['Waiting Jobs',    'QueueBacklog'],
    ];

    /**
     * Inject the filter dropdown. Call from indexBeforeAction.
     */
    protected function addAsyncFilter(ArrayObject $extra): void
    {
        $current = $this->asyncFilterKey();
        $options = [];
        foreach (self::ASYNC_FILTERS as $key => [$label, $action]) {
            $options[$key] = [
                'label' => __($label),
                'url'   => Router::url([
                    'plugin'     => 'System',
                    'controller' => 'Systems',
                    'action'     => $action,
                ]),
            ];
        }
        $extra['elements']['async_filter'] = [
            'name'    => 'System.async_filter',
            'data'    => ['options' => $options, 'selected' => $current],
            'options' => [],
            'order'   => 1,
        ];
    }

    /**
     * Concrete Tables return their own key from ASYNC_FILTERS so the
     * dropdown highlights the currently-displayed page.
     */
    abstract protected function asyncFilterKey(): string;
}
