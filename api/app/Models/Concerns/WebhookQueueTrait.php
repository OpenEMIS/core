<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WebhookQueueTrait
 *
 * POCOR-9257: Queue webhook requests from Laravel API5 models
 *
 * Usage:
 * class ModelName extends Model
 * {
 *     use WebhookQueueTrait;
 *
 *     // Which events to queue webhooks for
 *     protected $webhookEvents = ['created', 'updated', 'deleted'];
 *
 *     // Optional: Relations to include in webhook payload (matching CakePHP 'contain')
 *     protected $webhookRelations = ['relatedModel', 'anotherRelation'];
 *
 *     // Optional: Custom excluded fields (in addition to $hidden)
 *     protected $webhookExcludedFields = ['password', 'super_admin', 'sensitive_field'];
 *
 *     // Optional: Custom prefix for webhook event keys
 *     protected $webhookEventPrefix = 'area_education_'; // Results in 'area_education_created', etc.
 * }
 *
 * Laravel equivalent of CakePHP WebhookQueueBehavior
 *
 * Features:
 * - Auto-queues on created/updated/deleted events
 * - Loads relations specified in $webhookRelations (matching CakePHP 'contain')
 * - Respects model's $hidden property automatically
 * - Excludes sensitive fields: password, super_admin, _content, remember_token
 * - Customizable via $webhookExcludedFields property
 */
trait WebhookQueueTrait
{
    /**
     * Boot the trait and register model event listeners
     */
    public static function bootWebhookQueueTrait()
    {
        // POCOR-9257: Log trait initialization
        $modelClass = static::class;
        // Log::debug("[WebhookQueueTrait] Booting trait for model: {$modelClass}");

        // Listen to created event
        if (static::shouldQueueWebhookEvent('created')) {
            // Log::debug("[WebhookQueueTrait] Registering 'created' event listener for {$modelClass}");
            static::created(function ($model) {
                // Log::info("[WebhookQueueTrait] 'created' event fired for {$model->getTable()}, ID: {$model->getKey()}");
                $model->queueWebhookForModel('create');
            });
        }

        // Listen to updated event
        if (static::shouldQueueWebhookEvent('updated')) {
            // Log::debug("[WebhookQueueTrait] Registering 'updated' event listener for {$modelClass}");
            static::updated(function ($model) {
                // Log::info("[WebhookQueueTrait] 'updated' event fired for {$model->getTable()}, ID: {$model->getKey()}");
                $model->queueWebhookForModel('update');
            });
        }

        // Listen to deleted event
        if (static::shouldQueueWebhookEvent('deleted')) {
            // Log::debug("[WebhookQueueTrait] Registering 'deleted' event listener for {$modelClass}");
            static::deleted(function ($model) {
                // Log::info("[WebhookQueueTrait] 'deleted' event fired for {$model->getTable()}, ID: {$model->getKey()}");
                $model->queueWebhookForModel('delete');
            });
        }
    }

    /**
     * Check if webhook should be queued for this event
     *
     * @param string $event Event name (created, updated, deleted)
     * @return bool
     */
    protected static function shouldQueueWebhookEvent(string $event): bool
    {
        $webhookEvents = (new static)->webhookEvents ?? [];
        return in_array($event, $webhookEvents, true);
    }

    /**
     * Queue webhook for this model
     *
     * @param string $action Action type (create, update, delete)
     */
    protected function queueWebhookForModel(string $action): void
    {
        $tableName = $this->getTable();
        $modelId = $this->getKey();

        // Log::debug("[WebhookQueueTrait] START queueWebhookForModel - Table: {$tableName}, ID: {$modelId}, Action: {$action}");

        try {
            // Build event_key from model configuration or table name
            // POCOR-9257: Support custom prefix for event key matching (e.g., area_education_)
            if (!empty($this->webhookEventPrefix)) {
                $eventKey = $this->webhookEventPrefix . $action;
            } else {
                $singularTable = $this->singularizeTableName($tableName);
                $eventKey = "{$singularTable}_{$action}";
            }

            // Log::debug("[WebhookQueueTrait] Generated event_key: {$eventKey}");

            // POCOR-9257: Load relations if configured (matching CakePHP 'contain' parameter)
            $relations = $this->webhookRelations ?? [];
            if (!empty($relations)) {
                // Log::debug("[WebhookQueueTrait] Loading relations: " . implode(', ', $relations));
                // Load all specified relations
                $this->load($relations);
            } else {
                // Log::debug("[WebhookQueueTrait] No relations configured to load");
            }

            // Get model data (respects $hidden fields automatically, includes loaded relations)
            $body = $this->toArray();
            // Log::debug("[WebhookQueueTrait] Model data serialized, field count: " . count($body));

            // POCOR-9257: Exclude sensitive fields (matching CakePHP EXCLUDED_FIELDS)
            $excludedFields = $this->webhookExcludedFields ?? ['password', 'super_admin', '_content', 'remember_token'];
            // Log::debug("[WebhookQueueTrait] Excluding sensitive fields: " . implode(', ', $excludedFields));
            $body = array_diff_key($body, array_flip($excludedFields));

            // Add delete metadata for delete events
            if ($action === 'delete') {
                $body['deleted_at'] = now()->toDateTimeString();
                $body['deleted_by'] = auth()->user()->openemis_no ?? auth()->user()->username ?? 'system';
                // Log::debug("[WebhookQueueTrait] Added delete metadata to payload");
            }

            // Queue the webhook
            // Log::debug("[WebhookQueueTrait] Calling queueWebhook with event_key: {$eventKey}");
            $this->queueWebhook($eventKey, $body);

            // Log::info("[WebhookQueueTrait] ✓ Successfully completed queueWebhookForModel for {$eventKey}");

        } catch (\Throwable $e) {
            // POCOR-9257: Graceful degradation - don't break model operations
            // Log::error("[WebhookQueueTrait] ✗ Failed to queue webhook for {$tableName}#{$modelId}: " . $e->getMessage());
            // Log::error("[WebhookQueueTrait] Stack trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Queue webhook request to webhook_queue table
     *
     * @param string $eventKey Webhook event key
     * @param array $body Entity data
     */
    protected function queueWebhook(string $eventKey, array $body): void
    {
        if (empty($eventKey)) {
            // Log::warning("[WebhookQueueTrait] Empty event_key provided, skipping");
            return;
        }

        // Log::debug("[WebhookQueueTrait] START queueWebhook for event_key: {$eventKey}");

        try {
            // Fetch active webhooks for this event_key
            // Log::debug("[WebhookQueueTrait] Querying database for active webhooks matching event_key: {$eventKey}");
            $webhooks = DB::table('webhooks as w')
                ->join('config_items as ci', 'w.external_data_source_id', '=', 'ci.id')
                ->where('w.event_key', trim($eventKey))
                ->where('w.status', 1) // ACTIVE
                ->where('ci.value', 1) // CONFIG_ITEM_ACTIVE
                ->select([
                    'w.id as webhook_id',
                    'w.url',
                    'w.query_template',
                    'w.body_template',
                    'w.method',
                    'w.event_key',
                    'w.external_data_source_id',
                ])
                ->get();

            // Log::debug("[WebhookQueueTrait] Found " . $webhooks->count() . " active webhook(s) for event_key: {$eventKey}");

            if ($webhooks->isEmpty()) {
                // No active webhooks for this event - not an error
                // Log::debug("[WebhookQueueTrait] No active webhooks configured for event_key: {$eventKey}, skipping");
                return;
            }

            $queuedCount = 0;
            foreach ($webhooks as $webhookConfig) {
                // Log::debug("[WebhookQueueTrait] Processing webhook ID: {$webhookConfig->webhook_id}, URL: {$webhookConfig->url}");

                // Validate URL
                $url = trim($webhookConfig->url);
                if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                    // Log::warning("[WebhookQueueTrait] ✗ Invalid URL for webhook [{$eventKey}]: {$url}");
                    continue;
                }

                // Build final URL with query parameters (POCOR-9403 placeholder replacement)
                $finalUrl = $this->buildWebhookUrl($url, $webhookConfig->query_template ?? '', $body);
                // Log::debug("[WebhookQueueTrait] Built final URL: {$finalUrl}");

                // Build final body (POCOR-9403 template system)
                $finalBody = $this->buildWebhookBody($webhookConfig->body_template ?? '', $body);
                // $payloadSize = strlen(is_string($finalBody) ? $finalBody : json_encode($finalBody));
                // Log::debug("[WebhookQueueTrait] Built payload, size: {$payloadSize} bytes");

                // Insert into webhook_queue
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
                    'status' => 0, // PENDING
                    'retry_count' => 0,
                    'max_retries' => 3,
                    'available_at' => now(),
                    'created' => now(),
                    'created_user_id' => auth()->id(),
                ];

                // Log::debug("[WebhookQueueTrait] Inserting into webhook_queue table...");
                $queueId = DB::table('webhook_queue')->insertGetId($queueData);
                $queuedCount++;

                // Log::info("[WebhookQueueTrait] ✓ Queued webhook #{$queueId} for event_key: {$eventKey}, webhook_id: {$webhookConfig->webhook_id}");
            }

            // Log::info("[WebhookQueueTrait] ✓ Successfully queued {$queuedCount} webhook(s) for event_key: {$eventKey}");

        } catch (\Throwable $e) {
            // Log::error("[WebhookQueueTrait] ✗ Exception in queueWebhook for event_key: {$eventKey}");
            // Log::error("[WebhookQueueTrait] Error message: " . $e->getMessage());
            // Log::error("[WebhookQueueTrait] Stack trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Build final webhook URL with query parameters
     *
     * @param string $baseUrl Base URL
     * @param string $queryTemplate Query template with placeholders
     * @param array $data Data for placeholder replacement
     * @return string
     */
    protected function buildWebhookUrl(string $baseUrl, string $queryTemplate, array $data): string
    {
        if (empty($queryTemplate)) {
            return $baseUrl;
        }

        // Replace placeholders in query template
        $queryString = $this->replacePlaceholders($queryTemplate, $data);

        // Append to URL
        $separator = (strpos($baseUrl, '?') === false) ? '?' : '&';
        return $baseUrl . $separator . $queryString;
    }

    /**
     * Build final webhook body from template
     *
     * @param string $bodyTemplate Body template (JSON with placeholders)
     * @param array $data Data for placeholder replacement
     * @return string|array
     */
    protected function buildWebhookBody(string $bodyTemplate, array $data)
    {
        if (empty($bodyTemplate)) {
            // No template - return raw data as JSON
            return $data;
        }

        // Replace placeholders in body template
        $bodyJson = $this->replacePlaceholders($bodyTemplate, $data);

        // Try to decode as JSON
        $decoded = json_decode($bodyJson, true);
        return ($decoded !== null) ? $decoded : $bodyJson;
    }

    /**
     * Replace ${placeholder} with actual values
     *
     * @param string $template Template string with ${placeholders}
     * @param array $data Data array
     * @return string
     */
    protected function replacePlaceholders(string $template, array $data): string
    {
        return preg_replace_callback('/\$\{([a-zA-Z0-9_]+)\}/', function ($matches) use ($data) {
            $key = $matches[1];
            return $data[$key] ?? $matches[0]; // Keep placeholder if not found
        }, $template);
    }

    /**
     * Singularize table name for event_key generation
     *
     * @param string $tableName Table name (plural)
     * @return string Singular table name
     */
    protected function singularizeTableName(string $tableName): string
    {
        // Simple singularization (handles most cases)
        if (substr($tableName, -3) === 'ies') {
            return substr($tableName, 0, -3) . 'y'; // categories -> category
        }
        if (substr($tableName, -1) === 's') {
            return substr($tableName, 0, -1); // users -> user
        }
        return $tableName;
    }
}
