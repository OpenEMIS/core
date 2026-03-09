<?php
// POCOR-9257: Webhook async queue table
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Log\Log;

/**
 * WebhooksQueue Model
 *
 * Operational queue for pending webhook requests (purged after sending).
 * Works with ProcessWebhooksQueue Laravel command for async processing.
 *
 * Status values:
 * - 0: pending
 * - 1: processing
 * - 2: sent
 * - -1: failed
 */
class WebhooksQueueTable extends Table
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
        parent::initialize($config);

        $this->setTable('webhooks_queue');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                    'modified' => 'always'
                ]
            ]
        ]);
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
            Log::error("[WebhooksQueue] Empty event key provided");
            return false;
        }

        try {
            $ConfigWebhooks = TableRegistry::getTableLocator()->get('Configuration.ConfigWebhooks');
            $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');

            // Fetch all active webhooks for this event_key
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
                // No active webhooks for this event - not an error
                return true;
            }

            $queuedCount = 0;

            foreach ($webhooks as $webhookConfig) {
                // Validate URL
                $url = trim($webhookConfig->url);
                if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                    Log::warning("[WebhooksQueue] Invalid URL for webhook [{$eventKey}]: {$url}");
                    continue;
                }

                // Build final URL with query parameters (POCOR-9403 placeholder system)
                $finalUrl = $ConfigWebhooks->buildWebhookUrl(
                    $url,
                    $webhookConfig->query_template ?? '',
                    $body
                );

                // Build final body (POCOR-9403 template system)
                $finalBody = $ConfigWebhooks->prepareFinalWebhookBody(
                    $webhookConfig->body_template ?? '',
                    $body
                );

                // Prepare queue entry
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
                    'auth_type' => null, // TODO: Fetch from config_items if needed
                    'auth_credentials' => null,
                    'signature' => null, // TODO: HMAC signature if needed
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

                // Insert into queue
                $queueEntity = $this->newEntity($queueData);
                if ($this->save($queueEntity)) {
                    $queuedCount++;
                } else {
                    $errors = $queueEntity->getErrors();
                    Log::error("[WebhooksQueue] Failed to save queue entry for [{$eventKey}]: " . json_encode($errors));
                }
            }

            if ($queuedCount > 0) {
                return true;
            }

            return false;

        } catch (\Throwable $e) {
            Log::error("[WebhooksQueue] Exception in queueWebhook: " . $e->getMessage());
            return false;
        }
    }
}
