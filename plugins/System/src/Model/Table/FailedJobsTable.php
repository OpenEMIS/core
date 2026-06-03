<?php
declare(strict_types=1);

namespace System\Model\Table;

use ArrayObject;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;

/**
 * POCOR-9694 — Failed Jobs admin screen.
 *
 * Surfaces Laravel's {{failed_jobs}} table. Every queued job (alerts,
 * webhooks, any future async work) that exhausts its retry budget lands
 * here, so this screen is the operator's single point of truth for
 * queue-side failures across features.
 *
 * Retry mechanics. Re-queueing replicates {{php artisan queue:retry}}
 * inside a single SQL transaction:
 *
 *   1. Copy the row's {{queue}} + {{payload}} into {{jobs}} with
 *      {{attempts = 0}} and a fresh availability timestamp.
 *   2. Delete the original {{failed_jobs}} row.
 *
 * Both statements share one transaction so a partial failure cannot leave
 * the job duplicated in both tables. The cron-driven {{openemis-core:run}}
 * picks the re-queued job up on its next tick — no daemon needed.
 *
 * @see api/app/Http/Middleware/AsyncCatchUp.php   Opportunistic catch-up.
 * @see plugins/System/src/Controller/SystemsController.php  FailedJobsRetry().
 */
class FailedJobsTable extends AsyncServicesAdminTable
{
    use AsyncTabsTrait; //POCOR-9719

    /** Display cap so the index doesn't vomit a 30 KB stack trace per row. */
    private const EXCEPTION_PREVIEW_CHARS = 120;

    public function initialize(array $config): void
    {
        $this->setTable('failed_jobs');
        parent::initialize($config);
    }

    protected function pageTitle(): string
    {
        return 'Failed Background Tasks';
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        $this->setupAsyncTabs(); //POCOR-9719: horizontal tab bar

        $this->field('uuid',       ['visible' => false]);
        $this->field('connection', ['visible' => false]);
        $this->field('payload',    ['visible' => false]);

        $this->setFieldOrder(['id', 'queue', 'exception', 'failed_at']);
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        $this->field('payload', ['visible' => true, 'attr' => ['label' => __('Payload')]]);
        $this->setFieldOrder(['id', 'uuid', 'connection', 'queue', 'failed_at', 'exception', 'payload']);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra): void
    {
        $this->addRetryToolbarButton((int) $entity->id, $extra);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'failed_at':  return __('Failed At');
            case 'exception':  return __('Exception');
            case 'queue':      return __('Queue');
            case 'connection': return __('Connection');
            default: return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    /**
     * Truncate the exception column on the index page. The view page renders
     * the full exception via the standard ControllerAction template.
     */
    public function onGetException(EventInterface $event, Entity $entity)
    {
        $message = (string) $entity->exception;
        if (mb_strlen($message) <= self::EXCEPTION_PREVIEW_CHARS) {
            return $message;
        }
        return mb_substr($message, 0, self::EXCEPTION_PREVIEW_CHARS) . '…';
    }

    /**
     * Replicates {{php artisan queue:retry <id>}} via DB transaction.
     * Returns true on success, false if the row no longer exists (e.g.
     * a concurrent retry already moved it).
     *
     * The {{SELECT ... FOR UPDATE}} on the failed_jobs row is essential —
     * without it, two operators clicking Retry simultaneously would both
     * see the row, both INSERT into {{jobs}}, and only one DELETE would
     * win, producing a duplicate enqueue. With the row lock, the second
     * transaction blocks until the first commits, then sees no row, and
     * returns false cleanly.
     */
    public function requeue(int $failedJobId): bool
    {
        $connection = ConnectionManager::get('default');
        return $connection->transactional(function ($conn) use ($failedJobId) {
            $row = $conn->newQuery()
                ->select(['queue', 'payload'])
                ->from('failed_jobs')
                ->where(['id' => $failedJobId])
                ->epilog('FOR UPDATE')
                ->execute()
                ->fetch('assoc');

            if ($row === false || $row === null) {
                return false;
            }

            $now = time();
            $conn->insert('jobs', [
                'queue'        => $row['queue'],
                'payload'      => $row['payload'],
                'attempts'     => 0,
                'reserved_at'  => null,
                'available_at' => $now,
                'created_at'   => $now,
            ]);
            $conn->delete('failed_jobs', ['id' => $failedJobId]);
            return true;
        });
    }

    /**
     * Pushes a "Retry" toolbar button onto the view page. Clicking POSTs
     * to {{SystemsController::FailedJobsRetry()}} which calls
     * {{requeue()}} and redirects back to the index.
     */
    private function addRetryToolbarButton(int $failedJobId, ArrayObject $extra): void
    {
        $url = [
            'plugin'     => 'System',
            'controller' => 'Systems',
            'action'     => 'FailedJobsRetry',
            $failedJobId,
        ];
        $button = [
            'type' => 'button',
            'label' => '<i class="fa fa-refresh"></i>',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Retry'),
            ],
            'url' => $url,
        ];

        $toolbarButtons = $extra['toolbarButtons']->getArrayCopy();
        $toolbarButtons['retry'] = $button;
        $extra['toolbarButtons']->exchangeArray($toolbarButtons);
    }
}
