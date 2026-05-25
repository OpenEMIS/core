<?php
// POCOR-9257: Webhook async queue table - moved to Alert plugin
declare(strict_types=1);

namespace Alert\Model\Table; //POCOR-9257: moved from App\Model\Table

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

class WebhookQueueTable extends ControllerActionTable
{
    // Status constants
    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_SENT = 2;
    const STATUS_FAILED = -1;

    // Webhook table constants
    const WEBHOOK_ACTIVE = 1;
    const CONFIG_ITEM_ACTIVE = 1;

    public function initialize(array $config): void
    {
        $this->setTable('webhook_queue');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        parent::initialize($config);

        //POCOR-9257: Disable add and edit actions; this is a read-only operational log
        $this->toggle('add', false);
        $this->toggle('edit', false);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('event_key')
            ->maxLength('event_key', 100)
            ->requirePresence('event_key', 'create')
            ->notEmptyString('event_key');

        $validator
            ->scalar('target_url')
            ->maxLength('target_url', 512)
            ->requirePresence('target_url', 'create')
            ->notEmptyString('target_url');

        $validator
            ->scalar('http_method')
            ->maxLength('http_method', 10)
            ->notEmptyString('http_method');

        $validator
            ->requirePresence('payload', 'create')
            ->notEmptyString('payload');

        return $validator;
    }

    /**
     * Queue webhook requests for async processing
     *
     * @param string $eventKey Webhook event key (e.g., 'student_create')
     * @param array $body Entity data with placeholders
     * @param array|null $user Current user for audit trail
     * @return bool Success status
     */
    public function queueWebhook(string $eventKey, array $body, ?array $user = null): bool
    {
        if (empty($eventKey)) {
            Log::error("[WebhookQueue] Empty event key provided");
            return false;
        }

        try {
            $ConfigWebhooks = TableRegistry::getTableLocator()->get('Configuration.ConfigWebhooks');
            $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');

            $webhooks = $ConfigWebhooks->find()
                ->select([
                    'webhook_id' => $ConfigWebhooks->aliasField('id'),
                    'url' => $ConfigWebhooks->aliasField('url'),
                    'query_template' => $ConfigWebhooks->aliasField('query_template'),
                    'body_template' => $ConfigWebhooks->aliasField('body_template'),
                    'method' => $ConfigWebhooks->aliasField('method'),
                    'event_key' => $ConfigWebhooks->aliasField('event_key'),
                    'external_data_source_id' => $ConfigWebhooks->aliasField('external_data_source_id'),
                ])
                ->innerJoin(
                    [$ConfigItems->getAlias() => $ConfigItems->getTable()],
                    [$ConfigWebhooks->aliasField('external_data_source_id') . ' = ' . $ConfigItems->aliasField('id')]
                )
                ->where([
                    $ConfigWebhooks->aliasField('event_key') => trim($eventKey),
                    $ConfigWebhooks->aliasField('status') => self::WEBHOOK_ACTIVE,
                    $ConfigItems->aliasField('value') => self::CONFIG_ITEM_ACTIVE,
                ])
                ->all();

            if ($webhooks->isEmpty()) {
                return true; // No active webhooks for this event - not an error
            }

            $queuedCount = 0;

            foreach ($webhooks as $webhookConfig) {
                $url = trim($webhookConfig->url);
                if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                    Log::warning("[WebhookQueue] Invalid URL for webhook [{$eventKey}]: {$url}");
                    continue;
                }

                $finalUrl = $ConfigWebhooks->buildWebhookUrl(
                    $url,
                    $webhookConfig->query_template ?? '',
                    $body
                );

                $finalBody = $ConfigWebhooks->prepareFinalWebhookBody(
                    $webhookConfig->body_template ?? '',
                    $body
                );

                $queueData = [
                    'webhook_id' => $webhookConfig->webhook_id,
                    'event_key' => $eventKey,
                    'target_url' => $finalUrl,
                    'http_method' => strtoupper($webhookConfig->method ?? 'POST'),
                    'headers' => json_encode([
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'OpenEMIS-Webhook/1.0'
                    ]),
                    'payload' => is_string($finalBody) ? $finalBody : json_encode($finalBody),
                    'auth_type' => null,
                    'auth_credentials' => null,
                    'signature' => null,
                    'status' => self::STATUS_PENDING,
                    'retry_count' => 0,
                    'max_retries' => 3,
                    'last_error' => null,
                    'available_at' => date('Y-m-d H:i:s'),
                    'next_retry_at' => null,
                    'response_status' => null,
                    'response_body' => null,
                    'duration_ms' => null,
                    'sent_at' => null,
                    'created_user_id' => $user['id'] ?? null,
                ];

                $queueEntity = $this->newEntity($queueData);
                if ($this->save($queueEntity)) {
                    $queuedCount++;
                } else {
                    $errors = $queueEntity->getErrors();
                    Log::error("[WebhookQueue] Failed to save queue entry for [{$eventKey}]: " . json_encode($errors));
                }
            }

            if ($queuedCount > 0) {
                return true;
            }

            return false;

        } catch (\Throwable $e) {
            Log::error("[WebhookQueue] Exception in queueWebhook: " . $e->getMessage());
            return false;
        }
    }

    // UI configuration methods

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        //POCOR-9257: Hide large/technical fields from the index view
        $this->field('payload', ['visible' => false]);
        $this->field('headers', ['visible' => false]);
        $this->field('auth_credentials', ['visible' => false]);
        $this->field('signature', ['visible' => false]);
        $this->field('last_error', ['visible' => false]);
        $this->field('response_body', ['visible' => false]);
        $this->field('webhook_id', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('max_retries', ['visible' => false]);
        $this->field('retry_count', ['visible' => false]);
        $this->field('available_at', ['visible' => false]);
        $this->field('next_retry_at', ['visible' => false]);
        $this->field('duration_ms', ['visible' => false]);
        $this->field('auth_type', ['visible' => false]);

        // Order fields for better readability
        $this->field('event_key', ['after' => 'id']);
        $this->field('target_url', ['after' => 'event_key']);
        $this->field('http_method', ['after' => 'target_url']);
        $this->field('status', ['after' => 'http_method']);
        $this->field('sent_at', ['after' => 'status']);

        //POCOR-9257: Add Process Queue toolbar button
        $processButton = [
            'type' => 'button',
            'label' => '<i class="fa fa-play"></i> Process Queue',
            'class' => 'btn btn-primary',
            'url' => ['plugin' => 'Alert', 'controller' => 'Webhook', 'action' => 'processQueue'], //POCOR-9257: plugin=Alert
            'attr' => [
                'title' => 'Manually process pending webhooks',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom'
            ],
            'order' => 1
        ];
        $toolbarButton = [
            'type' => 'button',
            'label' => '<i class="fa fa-refresh"></i>',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Synchronisation')
            ],
            'url' => [
                'plugin' => 'Alert', //POCOR-9257: plugin=Alert
                'controller' => $this->controller->getName(),
                'action' => 'processQueue',
            ]
        ];

        //POCOR-9694: cross-link to Async Services → Webhook Failures dashboard.
        $checkFailures = [
            'type' => 'button',
            'label' => '<i class="fa fa-exclamation-triangle"></i>',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Check failures'),
            ],
            'url' => [
                'plugin' => 'System', 'controller' => 'Systems',
                'action' => 'WebhookFailures',
            ],
        ];

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarButtonsArray['process'] = $processButton;
        $toolbarButtonsArray['access'] = $toolbarButton;
        $toolbarButtonsArray['checkFailures'] = $checkFailures;
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        $this->controller->set('toolbarButtons', $extra['toolbarButtons']);
    }

    public function onGetStatus(EventInterface $event, Entity $entity): string
    {
        $statuses = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_SENT => 'Sent',
            self::STATUS_FAILED => 'Failed',
        ];
        return $statuses[$entity->status] ?? (string)$entity->status;
    }

    //POCOR-9257: Explicitly build view/remove URLs with encoded ID (same pattern as WebhookLogsTable)
    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons): array
    {
        $id = $this->getEncodedKeys($entity); //POCOR-9257: get encoded primary key

        if (isset($buttons['view'])) {
            $buttons['view']['url'] = [
                'plugin' => 'Alert', //POCOR-9257: moved to Alert plugin
                'controller' => 'Webhook',
                'action' => 'WebhookQueue',
                0 => 'view',
                1 => $id,
            ];
        }

        if (isset($buttons['remove'])) {
            //POCOR-9257: trigger delete modal (same as view-page toolbar button)
            $buttons['remove']['attr']['data-toggle'] = 'modal';
            $buttons['remove']['attr']['data-target'] = '#delete-modal';
            $buttons['remove']['attr']['field-target'] = '#recordId';
            $buttons['remove']['attr']['onclick'] = 'ControllerAction.fieldMapping(this)';
            $buttons['remove']['attr']['field-value'] = $id;
        }

        return $buttons;
    }
}
