<?php
declare(strict_types=1);

namespace System\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;

/**
 * POCOR-9694 — Webhook Failures admin screen.
 *
 * Surfaces rows in {{webhook_queue}} whose status is FAILED(-1). These are
 * deliveries that exceeded their attempt budget without a 2xx response from
 * the receiving endpoint; the operator needs to see them in one place to
 * decide whether to retry, blacklist a target, or escalate.
 */
class WebhookFailuresTable extends AsyncServicesAdminTable
{
    /** Status code in {{webhook_queue}} that marks a final delivery failure. */
    private const FAILED_STATUS = -1;

    public function initialize(array $config): void
    {
        $this->setTable('webhook_queue');
        parent::initialize($config);
    }

    protected function describeScreen(): string
    {
        return 'webhook_queue rows whose final status is FAILED. Each row'
            . ' represents a delivery that exhausted its retry budget;'
            . ' inspect the response body and target URL before deciding'
            . ' whether to retry or blacklist.';
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): void
    {
        $query->where([$this->aliasField('status') => self::FAILED_STATUS]);
    }
}
