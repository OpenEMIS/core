<?php

namespace App\Console\Commands;

use App\Services\WebhookSender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessWebhookQueue
 *
 * POCOR-9257: Process pending webhooks from webhook_queue table
 *
 * Usage:
 *   php artisan webhooks:process
 *   php artisan webhooks:process --limit=50
 *   php artisan webhooks:process --once
 *
 * Cron schedule (every minute):
 *   * * * * * php artisan webhooks:process --once
 */
class ProcessWebhookQueue extends Command
{
    protected $signature = 'webhooks:process
                            {--limit=100 : Maximum webhooks to process per batch}
                            {--once : Process one batch and exit}
                            {--max-retries=3 : Maximum retry attempts}';

    protected $description = 'POCOR-9257: Process pending webhooks from queue';

    private WebhookSender $sender;

    // Status constants (match CakePHP WebhookQueueTable)
    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_SENT = 2;
    const STATUS_FAILED = -1;

    public function __construct()
    {
        parent::__construct();
        $this->sender = new WebhookSender();
    }

    public function handle(): int
    {
        // $this->info("🚀 Starting webhook queue processor...");

        $limit = (int) $this->option('limit');
        $once = $this->option('once');
        $maxRetries = (int) $this->option('max-retries');

        $processed = 0;

        do {
            $batchProcessed = $this->processBatch($limit, $maxRetries);
            $processed += $batchProcessed;

            if ($batchProcessed > 0) {
                // $this->info("✓ Processed {$batchProcessed} webhooks (total: {$processed})");
            }

            if (!$once && $batchProcessed > 0) {
                // Brief pause between batches
                sleep(1);
            }

        } while (!$once && $batchProcessed > 0);

        if ($processed === 0) {
            // $this->info("✓ No pending webhooks");
        } else {
            // $this->info("✅ Completed! Total processed: {$processed}");
        }

        return self::SUCCESS;
    }

    /**
     * Process a batch of pending webhooks
     *
     * @param int $limit
     * @param int $maxRetries
     * @return int Number of webhooks processed
     */
    private function processBatch(int $limit, int $maxRetries): int
    {
        // Log::debug("[ProcessWebhookQueue] START processBatch - Limit: {$limit}, MaxRetries: {$maxRetries}");

        // Fetch pending webhooks (with retry logic)
        // Log::debug("[ProcessWebhookQueue] Querying webhook_queue for pending webhooks...");
        $webhooks = DB::table('webhook_queue')
            ->where('status', self::STATUS_PENDING)
            ->where('retry_count', '<', $maxRetries)
            ->where('available_at', '<=', now())
            ->whereNull('next_retry_at')
            ->orWhere('next_retry_at', '<=', now())
            ->orderBy('created', 'asc')
            ->limit($limit)
            ->get();

        $count = $webhooks->count();
        // Log::debug("[ProcessWebhookQueue] Found {$count} pending webhook(s) to process");

        if ($webhooks->isEmpty()) {
            // Log::debug("[ProcessWebhookQueue] No pending webhooks found");
            return 0;
        }

        $processed = 0;

        foreach ($webhooks as $webhook) {
            // Log::debug("[ProcessWebhookQueue] Processing webhook #{$webhook->id}, event_key: {$webhook->event_key}, URL: {$webhook->target_url}");

            try {
                $this->processSingleWebhook($webhook);
                $processed++;
                // Log::info("[ProcessWebhookQueue] ✓ Successfully processed webhook #{$webhook->id}");
            } catch (\Throwable $e) {
                Log::error("[ProcessWebhookQueue] ✗ Failed to process webhook #{$webhook->id}: " . $e->getMessage());
                Log::error("[ProcessWebhookQueue] Stack trace: " . $e->getTraceAsString());
            }
        }

        // Log::info("[ProcessWebhookQueue] Batch complete - Processed: {$processed}/{$count}");
        return $processed;
    }

    /**
     * Process a single webhook with transaction safety
     *
     * @param object $webhook
     */
    private function processSingleWebhook(object $webhook): void
    {
        // Log::debug("[ProcessWebhookQueue] START processSingleWebhook for webhook #{$webhook->id}");
        // Log::debug("[ProcessWebhookQueue] Event: {$webhook->event_key}, Method: {$webhook->http_method}, URL: {$webhook->target_url}");

        // POCOR-9257: Transaction safety - send + status update wrapped together
        DB::transaction(function () use ($webhook) {
            // Mark as processing
            // Log::debug("[ProcessWebhookQueue] Marking webhook #{$webhook->id} as PROCESSING");
            DB::table('webhook_queue')
                ->where('id', $webhook->id)
                ->update(['status' => self::STATUS_PROCESSING]);

            // Send webhook
            // Log::debug("[ProcessWebhookQueue] Calling WebhookSender->send() for webhook #{$webhook->id}");
            $startTime = microtime(true);
            $result = $this->sender->send((array) $webhook);
            $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);

            // Log::debug("[ProcessWebhookQueue] WebhookSender returned - Success: " . ($result['success'] ? 'YES' : 'NO') . ", Status: {$result['status_code']}, Duration: {$elapsedMs}ms");

            // Determine final status
            if ($result['success']) {
                // Success - mark as sent
                // Log::info("[ProcessWebhookQueue] ✓ Webhook #{$webhook->id} sent successfully - HTTP {$result['status_code']} in {$result['duration_ms']}ms");

                DB::table('webhook_queue')
                    ->where('id', $webhook->id)
                    ->update([
                        'status' => self::STATUS_SENT,
                        'response_status' => $result['status_code'],
                        'response_body' => $result['body'],
                        'duration_ms' => $result['duration_ms'],
                        'sent_at' => now(),
                        'modified' => now(),
                    ]);

                // Log to webhook_logs
                // Log::debug("[ProcessWebhookQueue] Logging successful webhook attempt to webhook_logs");
                $this->logWebhook($webhook, $result, true, $webhook->retry_count);

            } else {
                // Failed - check if should retry
                $retryCount = $webhook->retry_count + 1;

                Log::warning("[ProcessWebhookQueue] ✗ Webhook #{$webhook->id} failed - Error: {$result['error']}");

                if ($retryCount < $webhook->max_retries) {
                    // Schedule retry with exponential backoff
                    $nextRetryAt = $this->calculateNextRetry($retryCount);

                    // Log::info("[ProcessWebhookQueue] Scheduling retry #{$retryCount} for webhook #{$webhook->id} at {$nextRetryAt}");

                    DB::table('webhook_queue')
                        ->where('id', $webhook->id)
                        ->update([
                            'status' => self::STATUS_PENDING,
                            'retry_count' => $retryCount,
                            'next_retry_at' => $nextRetryAt,
                            'last_error' => $result['error'],
                            'response_status' => $result['status_code'],
                            'response_body' => $result['body'],
                            'duration_ms' => $result['duration_ms'],
                            'modified' => now(),
                        ]);

                    $this->warn("⚠ Webhook #{$webhook->id} failed, retry #{$retryCount} scheduled for {$nextRetryAt}");
                } else {
                    // Max retries reached - mark as failed
                    Log::error("[ProcessWebhookQueue] ✗✗✗ Webhook #{$webhook->id} PERMANENTLY FAILED after {$retryCount} attempts");
                    Log::error("[ProcessWebhookQueue] Final error: {$result['error']}");

                    DB::table('webhook_queue')
                        ->where('id', $webhook->id)
                        ->update([
                            'status' => self::STATUS_FAILED,
                            'retry_count' => $retryCount,
                            'last_error' => $result['error'],
                            'response_status' => $result['status_code'],
                            'response_body' => $result['body'],
                            'duration_ms' => $result['duration_ms'],
                            'modified' => now(),
                        ]);

                    Log::error("[ProcessWebhookQueue] Webhook #{$webhook->id} failed permanently after {$retryCount} attempts: " . $result['error']);
                }

                // Log to webhook_logs
                // Log::debug("[ProcessWebhookQueue] Logging failed webhook attempt to webhook_logs");
                $this->logWebhook($webhook, $result, false, $retryCount);
            }
        });

        // Log::debug("[ProcessWebhookQueue] END processSingleWebhook for webhook #{$webhook->id}");
    }

    /**
     * Calculate next retry time using exponential backoff
     *
     * @param int $retryCount
     * @return string
     */
    private function calculateNextRetry(int $retryCount): string
    {
        // Exponential backoff: 2^retry_count minutes
        // retry 1: 2 minutes, retry 2: 4 minutes, retry 3: 8 minutes
        $delayMinutes = pow(2, $retryCount);

        return now()->addMinutes($delayMinutes)->toDateTimeString();
    }

    /**
     * Log webhook attempt to webhook_logs table
     *
     * @param object $webhook
     * @param array $result
     * @param bool $success
     * @param int $retryAttempt
     */
    private function logWebhook(object $webhook, array $result, bool $success, int $retryAttempt): void
    {
        try {
            // Generate checksum for deduplication
            $checksum = $this->generateChecksum($webhook);

            DB::table('webhook_logs')->insert([
                'webhook_id' => $webhook->webhook_id,
                'webhook_queue_id' => $webhook->id,
                'event_key' => $webhook->event_key,
                'target_url' => $webhook->target_url,
                'http_method' => $webhook->http_method,
                'payload' => $webhook->payload,
                'headers' => $webhook->headers,
                'response_status' => $result['status_code'],
                'response_body' => $result['body'],
                'response_headers' => null, // TODO: Capture response headers if needed
                'duration_ms' => $result['duration_ms'],
                'success' => $success,
                'error_message' => $result['error'],
                'retry_attempt' => $retryAttempt,
                'checksum' => $checksum,
                'created' => now(),
                'created_user_id' => $webhook->created_user_id ?? null,
            ]);

        } catch (\Throwable $e) {
            Log::error("[ProcessWebhookQueue] Failed to log webhook attempt: " . $e->getMessage());
        }
    }

    /**
     * Generate SHA256 checksum for deduplication
     *
     * @param object $webhook
     * @return string
     */
    private function generateChecksum(object $webhook): string
    {
        $data = [
            'event_key' => $webhook->event_key,
            'target_url' => $webhook->target_url,
            'payload' => $webhook->payload,
        ];

        return hash('sha256', json_encode($data));
    }
}
