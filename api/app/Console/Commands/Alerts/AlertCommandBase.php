<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use App\Services\AlertProcessor\PlaceholderReplacer;
use App\Services\AlertProcessor\RecipientResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * POCOR-9509: Base class for all Laravel alert commands
 *
 * Mirrors the architecture of CakePHP's AlertCommandBase with the same pattern:
 * - Template method: runFeatureAlert()
 * - Abstract methods: getPendingItems(), fillPlaceholders()
 * - Common functionality: recipient resolution, placeholder replacement
 *
 * Subclasses must implement:
 * - getPendingItems(): Query database for items to alert on
 * - fillPlaceholders(): Map item data to ${placeholder} => value array
 *
 * @package App\Console\Commands\Alerts
 */
abstract class AlertCommandBase extends Command
{
    // Security role constants (matches CakePHP)
    const ROLE_STUDENT = 8;
    const ROLE_GUARDIAN = 9;

    /**
     * @var string Process name (e.g., 'StudentAbsence')
     */
    protected string $processName = '';

    /**
     * @var string Feature name (e.g., 'StudentAttendance')
     */
    protected string $featureName = '';

    /**
     * @var int User ID triggering the alert
     */
    protected int $userId = 0;

    /**
     * @var int Alert rule ID
     */
    protected int $ruleId = 0;

    /**
     * @var int System process ID
     */
    protected int $processId = 0;
    protected int $statusId = 0;

    /**
     * @var object|null Alert rule entity
     */
    protected ?object $rule = null;

    /**
     * @var array Contact list [email => [...], phone => [...]]
     */
    protected array $contacts = [];

    /**
     * @var PlaceholderReplacer
     */
    protected PlaceholderReplacer $placeholderReplacer;

    /**
     * @var RecipientResolver
     */
    protected RecipientResolver $recipientResolver;

    /**
     * POCOR-9509: Initialize services
     */
    public function __construct()
    {
        parent::__construct();

        // Extract process/feature names from class name
        $className = class_basename(static::class);
        $this->processName = str_replace('Command', '', $className);
        $this->featureName = str_replace('Alert', '', $this->processName);

        // Initialize services
        $this->placeholderReplacer = new PlaceholderReplacer();
        $this->recipientResolver = new RecipientResolver();
    }

    /**
     * POCOR-9509: Prepare command context (load rule, validate options)
     *
     * @return bool True if context is valid
     */
    protected function prepareContext(): bool
    {
        //POCOR-9509: capture processId FIRST so early-return paths can mark system_processes failed
        $this->processId = (int) $this->option('process_id');
        $this->userId = (int) $this->option('user_id');
        $this->ruleId = (int) $this->option('rule_id');

        if (!$this->userId || !$this->ruleId) {
            $this->error("Missing required --user_id or --rule_id.");
            //POCOR-9509: mark process failed so system_processes never hangs at status=1
            $this->markProcessFailed('Missing required --user_id or --rule_id');
            return false;
        }

        $this->rule = DB::table('alert_rules')
            ->where('id', $this->ruleId)
            ->first();

        if (!$this->rule) {
            $this->error("Alert rule with ID {$this->ruleId} not found.");
            //POCOR-9509: mark process failed
            $this->markProcessFailed("Alert rule with ID {$this->ruleId} not found");
            return false;
        }

        $roles = DB::table('alerts_roles')
            ->join('security_roles', 'security_roles.id', '=', 'alerts_roles.security_role_id')
            ->where('alerts_roles.alert_rule_id', $this->ruleId)
            ->select('security_roles.*')
            ->get();

        if ($roles->isEmpty()) {
            //POCOR-9509: rule with no roles is a misconfiguration — complete (not fail) so it doesn't pile up errors but doesn't hang either
            $this->completeProcess();
            return false;
        }

        $this->rule->security_roles = $roles->toArray();
        return true;
    }

    /**
     * POCOR-9509: Mark system process as failed without an exception
     *
     * Used by prepareContext() and subclass handle() early-return paths where
     * validation fails before runFeatureAlert() runs — prevents system_processes
     * row from hanging at status=1 forever.
     *
     * @param string $reason Short human-readable reason (logged + stored in system_errors)
     */
    protected function markProcessFailed(string $reason): void
    {
        if (empty($this->processId)) {
            return;
        }

        DB::table('system_processes')
            ->where('id', $this->processId)
            ->update([
                'status' => -2, // Error
                'end_date' => now(),
                'modified' => now(),
                'modified_user_id' => $this->userId ?: 1,
            ]);

        try {
            DB::table('system_errors')->insert([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'code' => 'ALERT_FAIL',
                'error_message' => $reason,
                'request_method' => 'CLI',
                'request_url' => $this->signature ?? '',
                'referrer_url' => '',
                'client_ip' => '127.0.0.1',
                'client_browser' => 'artisan',
                'triggered_from' => static::class,
                'stack_trace' => '',
                'server_info' => json_encode([
                    'process_id' => $this->processId,
                    'feature' => $this->featureName,
                    'rule_id' => $this->ruleId,
                    'user_id' => $this->userId,
                ]),
                'created_user_id' => $this->userId ?: 1,
                'created' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("[POCOR-9509] markProcessFailed: failed to insert system_errors", [
                'process_id' => $this->processId,
                'reason' => $reason,
                'logging_error' => $e->getMessage(),
            ]);
        }

        Log::warning("[POCOR-9509] Alert process marked failed (no exception)", [
            'process_id' => $this->processId,
            'feature' => $this->featureName,
            'reason' => $reason,
        ]);
    }

    /**
     * POCOR-9509: Main template method to execute feature-based alerts
     *
     * This method orchestrates the entire alert process:
     * 1. Get pending items (database query)
     * 2. For each item:
     *    - Build recipient list
     *    - Fill placeholders
     *    - Replace placeholders in subject/message
     *    - Queue alerts
     *
     * @param string $featureKey Feature identifier (e.g., 'StudentAttendance')
     * @return int Exit code
     */
    protected function runFeatureAlert(string $featureKey): int
    {
        $this->featureName = $featureKey; // POCOR-9509: use the explicit feature key so queue/completeProcess match alerts.name

        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() ENTRY - featureKey=' . $featureKey); //[TEMP-LOG]

        try {
            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() Calling getPendingItems()'); //[TEMP-LOG]
            $pendingItems = $this->getPendingItems($featureKey);

            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() getPendingItems returned ' . count($pendingItems) . ' items'); //[TEMP-LOG]

            if (empty($pendingItems)) {
                // $this->info("✅ Alert {$featureKey} has no pending items"); //POCOR-9509: commented out per CLAUDE.md
                // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() EXIT SUCCESS - No pending items'); //[TEMP-LOG]
                if (!empty($this->processId)) { //POCOR-9509: always complete the process even when no items found — prevents system_processes stuck at status=1
                    $this->completeProcess();
                }
                return self::SUCCESS;
            }

            // $this->info("Processing " . count($pendingItems) . " pending items for {$featureKey}");
            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() Starting loop over ' . count($pendingItems) . ' items'); //[TEMP-LOG]

            $itemCount = 0;
            $recipientsCache = []; //POCOR-9509: cache per institution_id — avoids N+1 queries on large deployments
            foreach ($pendingItems as $item) {
                $itemCount++;
                // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() Processing item ' . $itemCount . '/' . count($pendingItems)); //[TEMP-LOG]
                // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() Item data: ' . json_encode($item)); //[TEMP-LOG]

                // Get recipients for this item — cached per institution to avoid N+1 queries
                // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() Calling resolveRecipients()'); //[TEMP-LOG]
                $cacheKey = (string) ($item['institution_id'] ?? 'global'); //POCOR-9509: cache key
                if (!isset($recipientsCache[$cacheKey])) { //POCOR-9509: resolve once per institution
                    $recipientsCache[$cacheKey] = $this->resolveRecipients($item);
                }
                $this->contacts = $recipientsCache[$cacheKey]; //POCOR-9509: reuse cached result

                // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() Recipients resolved: email_count=' . count($this->contacts['email'] ?? []) . ', phone_count=' . count($this->contacts['phone'] ?? [])); //[TEMP-LOG]

                if (empty($this->contacts['email']) && empty($this->contacts['phone'])) {
                    $this->warn("No contacts found for item id=" . ($item['id'] ?? '?') . ", skipping"); //POCOR-9509
                    // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() SKIPPING item - No contacts'); //[TEMP-LOG]
                    continue;
                }

                // Fill placeholders with item data
                // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() Calling fillPlaceholders()'); //[TEMP-LOG]
                $placeholders = $this->fillPlaceholders($item);

                // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() Placeholders filled: ' . json_encode($placeholders)); //[TEMP-LOG]

                // Process contact list and queue alerts
                // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() Calling processContactList()'); //[TEMP-LOG]
                $this->processContactList($placeholders);
            }
        } catch (\Throwable $e) {
            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() EXCEPTION: ' . $e->getMessage()); //[TEMP-LOG]
            $this->failProcess($e);
            $this->error("[Process Error] " . $e->getMessage());
            Log::error("[POCOR-9509] Alert command failed", [
                'feature' => $featureKey,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }

        if (!empty($this->processId)) {
            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() Completing processId=' . $this->processId); //[TEMP-LOG]
            $this->completeProcess();
        }

        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::runFeatureAlert() EXIT SUCCESS'); //[TEMP-LOG]
        return self::SUCCESS;
    }

    /**
     * POCOR-9509: Resolve recipients for an item
     *
     * Override this method in subclasses for custom recipient logic
     *
     * @param array $item Pending item data
     * @return array Contact list [email => [...], phone => [...]]
     */
    protected function resolveRecipients(array $item): array
    {
        $institutionId = $item['institution_id'] ?? null;

        if ($institutionId) {
            return $this->recipientResolver->getRoleAssociatedContactList(
                $this->rule->security_roles,
                $institutionId
            );
        }

        return $this->recipientResolver->getRoleAssociatedContactList(
            $this->rule->security_roles
        );
    }

    /**
     * POCOR-9509: Process contact list and queue alerts
     *
     * @param array $placeholders Placeholder => value mapping
     */
    protected function processContactList(array $placeholders): void
    {
        // Replace placeholders in subject and message
        $subject = $this->placeholderReplacer->replace(
            $this->rule->subject,
            $placeholders
        );

        $message = $this->placeholderReplacer->replace(
            $this->rule->message,
            $placeholders
        );

        // Parse methods (e.g., "Email, SMS" => ['email', 'sms'])
        $methods = array_map(
            'trim',
            explode(',', strtolower($this->rule->method))
        );

        // Queue email alerts
        if (in_array('email', $methods, true)) {
            foreach ($this->contacts['email'] ?? [] as $email) {
                $this->queueAlert('email', $email, $subject, $message);
                //POCOR-9509: removed usleep(500000) — was exhausting PHP workers on large deployments
            }
        }

        // Queue SMS alerts
        if (in_array('sms', $methods, true)) {
            foreach ($this->contacts['phone'] ?? [] as $phone) {
                $this->queueAlert('sms', $phone, $subject, $message);
                //POCOR-9509: removed usleep(500000) — was exhausting PHP workers on large deployments
            }
        }
    }

    /**
     * POCOR-9509: Queue a single alert
     *
     * @param string $method 'email' or 'sms'
     * @param string $recipient Email address or phone number
     * @param string $subject Alert subject
     * @param string $message Alert message body
     */
    protected function queueAlert(string $method, string $recipient, string $subject, string $message): void
    {
        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::queueAlert() ENTRY'); //[TEMP-LOG]
        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::queueAlert() method=' . $method . ', recipient=' . $recipient); //[TEMP-LOG]
        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::queueAlert() subject (truncated 100): ' . mb_strimwidth($subject, 0, 100, '...')); //[TEMP-LOG]
        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::queueAlert() message (truncated 100): ' . mb_strimwidth($message, 0, 100, '...')); //[TEMP-LOG]

        try {
            // Queue via alert_queue table
            $insertData = [
                'alert_type' => $this->featureName,
                'channel' => $method,
                'recipient' => $recipient,
                'subject' => $subject,
                'message_body' => $message,
                'payload' => json_encode([
                    'feature' => $this->featureName,
                    'rule_id' => $this->ruleId,
                    'user_id' => $this->userId,
                ]),
                'status' => \App\Models\Api5\AlertLogs::STATUS_PENDING, //POCOR-9509: use constant
                'retry_count' => 0,
                'available_at' => now(),
                'created' => now(),
                'modified' => now(),
            ];

            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::queueAlert() Inserting into alert_queue: ' . json_encode($insertData)); //[TEMP-LOG]

            $queued = DB::table('alert_queue')->insert($insertData);

            if ($queued) {
                $shortSubject = mb_strimwidth($subject, 0, 100, '...');
                $shortMessage = mb_strimwidth($message, 0, 100, '...');
                // $this->info("✅ Alert {$this->featureName} queued via {$method} to {$recipient}"); //POCOR-9509: commented out per CLAUDE.md
                // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::queueAlert() INSERT SUCCESS'); //[TEMP-LOG]
                // Log::debug("[POCOR-9509] Alert queued", [
                //     'feature' => $this->featureName,
                //     'method' => $method,
                //     'recipient' => $recipient,
                //     'subject' => $shortSubject,
                // ]);
            } else {
                // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::queueAlert() INSERT FAILED (returned false)'); //[TEMP-LOG]
            }
        } catch (\Throwable $e) {
            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::queueAlert() EXCEPTION: ' . $e->getMessage()); //[TEMP-LOG]
            $this->error("Failed to queue alert: " . $e->getMessage());
            Log::error("[POCOR-9509] Failed to queue alert", [
                'feature' => $this->featureName,
                'method' => $method,
                'recipient' => $recipient,
                'exception' => $e->getMessage(),
            ]);
        }

        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::queueAlert() EXIT'); //[TEMP-LOG]
    }

    /**
     * POCOR-9509: Mark system process as failed and log to system_errors table
     *
     * Updates system_processes.status to -2 (Error) and creates a detailed error
     * record in system_errors table for debugging and monitoring.
     *
     * Status codes (from schema_reference.sql):
     * - 1 = New (Starting)
     * - 2 = Running (In Progress)
     * - 3 = Completed
     * - -1 = Abort
     * - -2 = Error
     *
     * @param \Throwable $exception The exception that caused the failure
     */
    protected function failProcess(\Throwable $exception): void
    {
        if (empty($this->processId)) {
            return;
        }

        // POCOR-9509: Update system_processes status to -2 (Error)
        DB::table('system_processes')
            ->where('id', $this->processId)
            ->update([
                'status' => -2, // POCOR-9509: Use -2 (Error) instead of 2 (Running)
                'end_date' => now(),
                'modified' => now(),
                'modified_user_id' => $this->userId,
            ]);

        // POCOR-9509: Log error details to system_errors table
        try {
            $errorId = \Illuminate\Support\Str::uuid()->toString();

            // Build server info JSON with process context
            $serverInfo = json_encode([
                'process_id' => $this->processId,
                'feature' => $this->featureName,
                'rule_id' => $this->ruleId,
                'user_id' => $this->userId,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ]);

            DB::table('system_errors')->insert([
                'id' => $errorId,
                'code' => 'ALERT_FAIL', // POCOR-9509: Alert command failure
                'error_message' => $exception->getMessage(),
                'request_method' => 'CLI', // Console command
                'request_url' => $this->signature, // Command signature
                'referrer_url' => '', // N/A for CLI
                'client_ip' => '127.0.0.1', // Local execution
                'client_browser' => 'artisan', // CLI tool
                'triggered_from' => $exception->getFile() . ':' . $exception->getLine(),
                'stack_trace' => $exception->getTraceAsString(),
                'server_info' => $serverInfo,
                'created_user_id' => $this->userId,
                'created' => now(),
            ]);

            Log::error("[POCOR-9509] System process failed - logged to system_errors", [
                'process_id' => $this->processId,
                'error_id' => $errorId,
                'feature' => $this->featureName,
                'exception' => $exception->getMessage(),
            ]);
        } catch (\Exception $e) {
            // POCOR-9509: Fallback if system_errors insert fails
            Log::error("[POCOR-9509] Failed to log to system_errors table", [
                'process_id' => $this->processId,
                'original_error' => $exception->getMessage(),
                'logging_error' => $e->getMessage(),
            ]);
        }

        // POCOR-9509: Always log to file for immediate debugging
        Log::error("[POCOR-9509] Alert command exception", [
            'process_id' => $this->processId,
            'feature' => $this->featureName,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * POCOR-9509: Mark system process as completed
     */
    protected function completeProcess(): void
    {
        if (empty($this->processId)) {
            return;
        }

        DB::table('system_processes')
            ->where('id', $this->processId)
            ->update([
                'status' => 3, // completed
                'end_date' => now(),
                'modified' => now(),
                'modified_user_id' => $this->userId,
            ]);

        DB::table('alerts')
            ->where('name', $this->featureName)
            ->update([
                'process_id' => getmypid(),
                'modified' => now(),
            ]);

        // Log::debug("[POCOR-9509] System process completed", [
        //     'process_id' => $this->processId,
        // ]);
    }

    /**
     * POCOR-9509: Abstract method - Get pending items to alert on
     *
     * Subclasses must implement this to query the database for items
     * that need alerts sent (e.g., absent students, expiring staff contracts)
     *
     * @param string $featureKey Feature identifier
     * @return array List of data items (arrays or objects)
     */
    abstract protected function getPendingItems(string $featureKey): array;

    /**
     * POCOR-9509: Abstract method - Fill placeholders for an item
     *
     * Subclasses must implement this to map item data to placeholder values.
     *
     * Example return:
     * [
     *     '${student.name}' => 'John Doe',
     *     '${institution.name}' => 'ABC School',
     *     '${total_days}' => 5,
     * ]
     *
     * @param array $item The item from getPendingItems()
     * @return array Placeholder => value mapping
     */
    abstract protected function fillPlaceholders(array $item): array;
}
