<?php
declare(strict_types=1);

namespace System\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;

/**
 * POCOR-9694 — Stuck Processes admin screen.
 *
 * Surfaces rows in {{system_processes}} whose status is still NEW(1) or
 * RUNNING(2) older than {{STUCK_THRESHOLD_HOURS}} hours. The 1-day global
 * stale-sweep in {{CheckAndQueueAlerts::handle()}} reaps these eventually,
 * but operators need visibility BEFORE that sweep fires so they can
 * intervene on persistent failures (worker crash, hung exec, etc.).
 */
class StuckProcessesTable extends AsyncServicesAdminTable
{
    use AsyncTabsTrait; //POCOR-9719

    /** A row older than this with status IN (1,2) is considered stuck. */
    private const STUCK_THRESHOLD_HOURS = 1;

    /** Status codes that mean "started but never finished". */
    private const ACTIVE_STATUSES = [1, 2];

    /** Mirror of SystemProcessesTable so users see the same labels everywhere. */
    private const STATUS_LABELS = [
         1 => 'New',
         2 => 'Running',
         3 => 'Completed',
        -1 => 'Abort',
        -2 => 'Error',
    ];

    public function initialize(array $config): void
    {
        $this->setTable('system_processes');
        parent::initialize($config);
    }

    protected function pageTitle(): string
    {
        return 'Frozen Background Tasks';
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        $this->setupAsyncTabs(); //POCOR-9719: horizontal tab bar

        $this->field('process_id',       ['visible' => false]);
        $this->field('callable_event',   ['visible' => false]);
        $this->field('executed_count',   ['visible' => false]);
        $this->field('params',           ['visible' => false]);
        $this->field('end_date',         ['visible' => false]);
        $this->field('created_user_id',  ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified',         ['visible' => false]);

        $this->field('stuck_for', ['type' => 'string', 'after' => 'start_date']);

        $this->setFieldOrder(['name', 'status', 'start_date', 'stuck_for', 'model']);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): void
    {
        $threshold = sprintf('NOW() - INTERVAL %d HOUR', self::STUCK_THRESHOLD_HOURS);
        $query->where([
            $this->aliasField('status') . ' IN' => self::ACTIVE_STATUSES,
            $this->aliasField('created') . ' <' => $query->newExpr($threshold),
        ]);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'name':       return __('Feature');
            case 'start_date': return __('Started');
            case 'stuck_for':  return __('Stuck For');
            default: return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetStatus(EventInterface $event, Entity $entity)
    {
        return self::STATUS_LABELS[$entity->status] ?? (string) $entity->status;
    }

    /**
     * Human-readable "stuck for" duration computed from start_date (or
     * created if start_date is NULL — happens when status=NEW(1)).
     */
    public function onGetStuckFor(EventInterface $event, Entity $entity): string
    {
        $reference = $entity->start_date ?? $entity->created;
        if (!$reference instanceof FrozenTime) {
            return '';
        }
        return $reference->timeAgoInWords(['accuracy' => ['hour' => 'minute']]);
    }
}
