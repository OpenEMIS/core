<?php
declare(strict_types=1);

namespace System\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;

/**
 * POCOR-9694 — Queue Backlog admin screen.
 *
 * Pending {{alert_queue}} rows ordered by oldest-first, so operators can
 * see what's stuck waiting and how long it has been waiting. Status code
 * mapping mirrors the dispatcher: 0=PENDING, 1=SENT, -1=FAILED. This
 * screen scopes to PENDING only — failed deliveries live on the Webhook
 * Failures screen for webhook-channel work, and a future Failed Alerts
 * screen will expose non-webhook failures.
 *
 * The alert_queue is consulted on every {{openemis-core:run}} tick; rows
 * that have been pending for more than a few minutes indicate the
 * dispatcher is wedged and worth investigating.
 */
class QueueBacklogTable extends AsyncServicesAdminTable
{
    use AsyncTabsTrait; //POCOR-9719

    /** Status code: a row that has not yet been picked up for delivery. */
    private const PENDING_STATUS = 0;

    public function initialize(array $config): void
    {
        $this->setTable('alert_queue');
        parent::initialize($config);
    }

    protected function pageTitle(): string
    {
        return 'Waiting Background Tasks';
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        $this->setupAsyncTabs(); //POCOR-9719: horizontal tab bar

        foreach (['payload', 'last_error', 'sent_at', 'modified', 'message_body',
                  'status', 'retry_count'] as $hide) {
            $this->field($hide, ['visible' => false]);
        }

        $this->field('waiting_for', ['type' => 'string', 'after' => 'available_at']);
        $this->setFieldOrder([
            'alert_type', 'channel', 'recipient', 'subject',
            'available_at', 'waiting_for', 'created',
        ]);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): void
    {
        $query
            ->where([$this->aliasField('status') => self::PENDING_STATUS])
            ->order([$this->aliasField('available_at') => 'ASC']);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'alert_type':   return __('Alert Type');
            case 'channel':      return __('Channel');
            case 'recipient':    return __('Recipient');
            case 'available_at': return __('Available At');
            case 'waiting_for':  return __('Waiting For');
            default: return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    /**
     * Human-readable "waiting for" — how long the row has been pending
     * since {{available_at}}. Only meaningful for rows where
     * {{available_at}} is in the past, but since the screen filters to
     * PENDING and the dispatcher only schedules into the future for
     * deliberate delays, "future" rows are normal and we render them
     * as "(scheduled)" instead of a negative duration.
     */
    public function onGetWaitingFor(EventInterface $event, Entity $entity): string
    {
        $availableAt = $entity->available_at;
        if (!$availableAt instanceof FrozenTime) {
            return '';
        }
        if ($availableAt->isFuture()) {
            return __('(scheduled)');
        }
        return $availableAt->timeAgoInWords(['accuracy' => ['hour' => 'minute']]);
    }
}
