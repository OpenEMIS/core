<?php
declare(strict_types=1);

namespace System\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;

/**
 * POCOR-9694 — Webhook Failures admin screen.
 *
 * Surfaces rows in {{webhook_queue}} whose status is FAILED(-1). These are
 * deliveries that exceeded their attempt budget without a 2xx response from
 * the receiving endpoint; operators see them in one place to decide whether
 * to retry, blacklist a target, or escalate.
 */
class WebhookFailuresTable extends AsyncServicesAdminTable
{
    use AsyncTabsTrait; //POCOR-9719

    /** Status code in {{webhook_queue}} that marks a final delivery failure. */
    private const FAILED_STATUS = -1;

    /** Display cap for the {{last_error}} preview column on the index page. */
    private const ERROR_PREVIEW_CHARS = 120;

    public function initialize(array $config): void
    {
        $this->setTable('webhook_queue');
        parent::initialize($config);
    }

    protected function pageTitle(): string
    {
        return 'Webhook Failures';
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        $this->setupAsyncTabs(); //POCOR-9719: horizontal tab bar

        // Hide noisy / sensitive columns on the index — visible on view page.
        // {{status}} is always FAILED here (we filter by it) so it's redundant;
        // {{max_retries}} is configuration, not data, so it belongs on view only.
        foreach (['headers', 'payload', 'auth_type', 'auth_credentials',
                  'signature', 'response_body', 'available_at', 'next_retry_at',
                  'duration_ms', 'modified', 'created_user_id', 'webhook_id',
                  'status', 'max_retries'] as $hide) {
            $this->field($hide, ['visible' => false]);
        }

        $this->setFieldOrder([
            'event_key', 'target_url', 'http_method',
            'response_status', 'last_error', 'retry_count', 'sent_at', 'created',
        ]);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): void
    {
        $query->where([$this->aliasField('status') => self::FAILED_STATUS]);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'event_key':       return __('Event');
            case 'target_url':      return __('Target URL');
            case 'http_method':     return __('Method');
            case 'response_status': return __('HTTP Status');
            case 'last_error':      return __('Last Error');
            case 'retry_count':     return __('Retries');
            case 'sent_at':         return __('Last Attempt');
            default: return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    /**
     * Truncate the error column on the index. The view page renders the
     * full error via the standard ControllerAction template.
     */
    public function onGetLastError(EventInterface $event, Entity $entity)
    {
        $message = (string) ($entity->last_error ?? '');
        if (mb_strlen($message) <= self::ERROR_PREVIEW_CHARS) {
            return $message;
        }
        return mb_substr($message, 0, self::ERROR_PREVIEW_CHARS) . '…';
    }
}
