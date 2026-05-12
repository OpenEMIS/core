<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * POCOR-9509 Phase 3: Laravel Alert Trigger Service
 *
 * Provides consistent alert triggering architecture between CakePHP and Laravel.
 * Mirrors CakePHP's AlertLogsTable::triggerAlertSystemProcess() with Phase 2 enhancements.
 *
 * Key Features:
 * - Same params structure as CakePHP (Phase 2 enhanced)
 * - Same status codes (1=Starting, 3=Completed, -2=Error)
 * - Checksum-based deduplication
 * - Consistent system_processes records
 *
 * Usage:
 *   use App\Services\AlertTriggerService;
 *
 *   $result = AlertTriggerService::triggerAlert(
 *       processName: 'AlertStudentAbsence',
 *       featureName: 'StudentAttendance',
 *       userId: 1,
 *       ruleId: 5,
 *       entityId: 123,
 *       context: [
 *           'student_id' => 123,
 *           'academic_period_id' => 456,
 *           'total_days' => 5,
 *       ]
 *   );
 *
 * @package App\Services
 */
class AlertTriggerService
{
    /**
     * POCOR-9509: Trigger alert with consistent architecture
     *
     * Creates system_processes record with same format as CakePHP,
     * checks for duplicates using checksum, and optionally triggers alert command.
     *
     * @param string $processName Process name (e.g., 'AlertStudentAbsence', 'AlertStaffType')
     * @param string $featureName Feature name (e.g., 'StudentAttendance', 'StaffType')
     * @param int $userId User ID triggering the alert
     * @param int $ruleId Alert rule ID
     * @param int|string|null $entityId Entity ID (student_id, staff_id, institution_students.id, etc.)
     * @param array $context Additional context for deduplication and placeholder replacement
     * @param string $entityType Optional entity type (defaults to processName)
     * @param string $triggerType Optional trigger type (defaults to 'threshold_alert')
     * @return array ['success' => bool, 'process_id' => int|null, 'message' => string, 'duplicate' => bool]
     */
    public static function triggerAlert(
        string $processName,
        string $featureName,
        int    $userId,
        int    $ruleId,
        int|string|null $entityId = null,
        array  $context = [],
        string $entityType = '',
        string $triggerType = 'threshold_alert'
    ): array {
        // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlert() ENTRY'); //[TEMP-LOG]
        // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlert() All params: processName=' . $processName . ', featureName=' . $featureName . ', userId=' . $userId . ', ruleId=' . $ruleId . ', entityId=' . ($entityId ?? 'null') . ', context=' . json_encode($context) . ', entityType=' . $entityType . ', triggerType=' . $triggerType); //[TEMP-LOG]

        try {
            $entityType = $entityType ?: $processName;
            $now = Carbon::now();

            // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlert() entityType resolved: ' . $entityType . ', now=' . $now); //[TEMP-LOG]

            // POCOR-9509: Build params with Phase 2 structure
            $params = self::buildParams(
                $ruleId,
                $entityId,
                $entityType,
                $triggerType,
                $context,
                $now
            );

            $checksum = $params['checksum'];
            // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlert() Computed checksum: ' . $checksum); //[TEMP-LOG]

            // POCOR-9509: Check for duplicate (same checksum)
            // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlert() Checking for duplicate process...'); //[TEMP-LOG]
            $existingProcess = self::checkDuplicate($processName, $featureName, $userId, $checksum);

            if ($existingProcess) {
                // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlert() DUPLICATE FOUND - existing_process_id=' . $existingProcess->id); //[TEMP-LOG]
                // Log::debug('[POCOR-9509] Duplicate alert skipped (checksum match)', [
                //     'process_name' => $processName,
                //     'feature' => $featureName,
                //     'checksum' => $checksum,
                //     'existing_process_id' => $existingProcess->id,
                //     'entity_id' => $entityId,
                // ]);

                return [
                    'success' => false,
                    'process_id' => $existingProcess->id,
                    'message' => 'Duplicate alert (same checksum found)',
                    'duplicate' => true,
                ];
            }

            // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlert() No duplicate, proceeding to create system_processes'); //[TEMP-LOG]

            // POCOR-9509: Create system_processes record (consistent with CakePHP)
            // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlert() Calling createSystemProcess()'); //[TEMP-LOG]
            $processId = self::createSystemProcess(
                $featureName,
                $processName,
                $userId,
                $params,
                $now
            );

            // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlert() system_processes created with ID: ' . $processId); //[TEMP-LOG]
            // Log::info('[POCOR-9509] Alert process created (Laravel)', [
            //     'process_id' => $processId,
            //     'process_name' => $processName,
            //     'feature' => $featureName,
            //     'checksum' => $checksum,
            //     'entity_id' => $entityId,
            // ]);

            // POCOR-9509: Trigger Laravel alert command (Phase 3 - remove duplicate logic)
            // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlert() About to call triggerAlertCommand()'); //[TEMP-LOG]
            self::triggerAlertCommand($processName, $userId, $ruleId, $processId, $entityId, $context);
            // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlert() triggerAlertCommand() returned'); //[TEMP-LOG]

            return [
                'success' => true,
                'process_id' => $processId,
                'message' => 'Alert process created and command triggered',
                'duplicate' => false,
            ];
        } catch (\Throwable $e) {
            // //Log::error('[TEMP-LOG] @AlertTriggerService::triggerAlert() EXCEPTION: ' . $e->getMessage() . ' - trace: ' . $e->getTraceAsString()); //[TEMP-LOG]
            Log::error('[POCOR-9509] Failed to trigger alert', [
                'process_name' => $processName,
                'feature' => $featureName,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'process_id' => null,
                'message' => 'Error: ' . $e->getMessage(),
                'duplicate' => false,
            ];
        }
    }

    /**
     * POCOR-9509: Build params JSON with Phase 2 structure
     *
     * Mirrors CakePHP's AlertLogsTable::triggerAlertSystemProcess() params format.
     *
     * @param int $ruleId Alert rule ID
     * @param int|string|null $entityId Entity ID
     * @param string $entityType Entity type
     * @param string $triggerType Trigger type
     * @param array $context Additional context
     * @param Carbon $now Current timestamp
     * @return array Params array with checksum
     */
    private static function buildParams(
        int    $ruleId,
        int|string|null $entityId,
        string $entityType,
        string $triggerType,
        array  $context,
        Carbon $now
    ): array {
        // POCOR-9509: Generate checksum for deduplication (Phase 2)
        $checksumData = [
            'entity_id' => $entityId,
            'context' => $context,
            'trigger_type' => $triggerType,
        ];
        $checksum = hash('sha256', json_encode($checksumData));

        // POCOR-9509: Build params with same structure as CakePHP Phase 2
        return [
            'rule_id' => $ruleId,
            'entity_id' => $entityId,
            'entity_type' => $entityType,
            'trigger_type' => $triggerType,
            'context' => $context,
            'checksum' => $checksum,
            'triggered_at' => $now->toDateTimeString(),
        ];
    }

    /**
     * POCOR-9509: Check for duplicate process (same checksum)
     *
     * @param string $processName Process name
     * @param string $featureName Feature name
     * @param int $userId User ID
     * @param string $checksum Checksum to check
     * @return object|null Existing process or null
     */
    private static function checkDuplicate(
        string $processName,
        string $featureName,
        int    $userId,
        string $checksum
    ): ?object {
        return DB::table('system_processes')
            ->where('model', $processName)
            ->where('name', $featureName)
            ->where('created_user_id', $userId)
            ->where('params', 'LIKE', '%"checksum":"' . $checksum . '"%')
            ->first();
    }

    /**
     * POCOR-9509: Create system_processes record (consistent with CakePHP)
     *
     * @param string $featureName Feature name
     * @param string $processName Process name
     * @param int $userId User ID
     * @param array $params Params array
     * @param Carbon $now Current timestamp
     * @return int Process ID
     */
    private static function createSystemProcess(
        string $featureName,
        string $processName,
        int    $userId,
        array  $params,
        Carbon $now
    ): int {
        return DB::table('system_processes')->insertGetId([
            'name' => $featureName, // ✅ Same as CakePHP: feature name
            'status' => 1, // ✅ Same as CakePHP: 1 = Starting
            'start_date' => $now,
            'model' => $processName, // ✅ Same as CakePHP: process name
            'created_user_id' => $userId,
            'created' => $now,
            'modified' => $now,
            'modified_user_id' => $userId,
            'params' => json_encode($params), // ✅ Same structure as CakePHP Phase 2
        ]);
    }

    /**
     * POCOR-9509: Mark system process as completed
     *
     * @param int $processId Process ID
     * @param int $userId User ID
     * @return bool Success
     */
    public static function completeProcess(int $processId, int $userId): bool
    {
        try {
            DB::table('system_processes')
                ->where('id', $processId)
                ->update([
                    'status' => 3, // ✅ Completed
                    'end_date' => now(),
                    'modified' => now(),
                    'modified_user_id' => $userId,
                ]);

            // Log::debug('[POCOR-9509] System process completed (Laravel)', [
            //     'process_id' => $processId,
            // ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[POCOR-9509] Failed to complete process', [
                'process_id' => $processId,
                'exception' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * POCOR-9509: Mark system process as failed with error details
     *
     * Mirrors Phase 1 failProcess() implementation.
     *
     * @param int $processId Process ID
     * @param int $userId User ID
     * @param \Throwable $exception Exception that caused failure
     * @return bool Success
     */
    public static function failProcess(int $processId, int $userId, \Throwable $exception): bool
    {
        try {
            // POCOR-9509: Update system_processes status to -2 (Error)
            DB::table('system_processes')
                ->where('id', $processId)
                ->update([
                    'status' => -2, // ✅ Error (Phase 1)
                    'end_date' => now(),
                    'modified' => now(),
                    'modified_user_id' => $userId,
                ]);

            // POCOR-9509: Log error details to system_errors table (Phase 1)
            $errorId = \Illuminate\Support\Str::uuid()->toString();

            $serverInfo = json_encode([
                'process_id' => $processId,
                'source' => 'Laravel AlertTriggerService',
                'user_id' => $userId,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ]);

            DB::table('system_errors')->insert([
                'id' => $errorId,
                'code' => 'ALERT_FAIL',
                'error_message' => $exception->getMessage(),
                'request_method' => 'CLI',
                'request_url' => 'Laravel AlertTriggerService',
                'referrer_url' => '',
                'client_ip' => '127.0.0.1',
                'client_browser' => 'laravel',
                'triggered_from' => $exception->getFile() . ':' . $exception->getLine(),
                'stack_trace' => $exception->getTraceAsString(),
                'server_info' => $serverInfo,
                'created_user_id' => $userId,
                'created' => now(),
            ]);

            Log::error('[POCOR-9509] System process failed (Laravel)', [
                'process_id' => $processId,
                'error_id' => $errorId,
                'exception' => $exception->getMessage(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[POCOR-9509] Failed to mark process as failed', [
                'process_id' => $processId,
                'original_error' => $exception->getMessage(),
                'logging_error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * POCOR-9509: Get alert rule by ID
     *
     * @param int $ruleId Alert rule ID
     * @return object|null Alert rule object
     */
    public static function getAlertRule(int $ruleId): ?object
    {
        return DB::table('alert_rules')
            ->where('id', $ruleId)
            ->where('enabled', 1)
            ->first();
    }

    /**
     * POCOR-9509: Get active alert rule by feature name
     *
     * @param string $featureName Feature name (e.g., 'StudentAttendance')
     * @param int|null $institutionId Optional institution filter
     * @return object|null Alert rule object
     */
    public static function getActiveAlertRule(string $featureName, ?int $institutionId = null): ?object
    {
        $query = DB::table('alert_rules')
            ->where('feature', $featureName)
            ->join('alerts', 'alerts.name', '=', 'alert_rules.feature')
            ->where('alerts.frequency', '!=', 'Never')
            ->where('alert_rules.enabled', 1);

        // Optional: filter by institution if needed
        // if ($institutionId) {
        //     $query->where('institution_id', $institutionId);
        // }

        return $query->select('alert_rules.*')->first();
    }

    /**
     * POCOR-9509 Phase 3: Trigger Laravel alert command
     *
     * Dispatches the appropriate alert command based on process name.
     * Commands do all the work: threshold checking, placeholder building, sending.
     *
     * @param string $processName Process name (e.g., 'AlertStudentAbsence')
     * @param int $userId User ID
     * @param int $ruleId Alert rule ID
     * @param int $processId System process ID
     * @param array $context Context data to pass to command
     * @return void
     */
    private static function triggerAlertCommand(
        string $processName,
        int    $userId,
        int    $ruleId,
        int    $processId,
        int|string|null $entityId,
        array  $context
    ): void {
        // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlertCommand() ENTRY'); //[TEMP-LOG]
        // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlertCommand() params: processName=' . $processName . ', userId=' . $userId . ', ruleId=' . $ruleId . ', processId=' . $processId . ', entityId=' . ($entityId ?? 'null')); //[TEMP-LOG]

        // Map process names to Laravel artisan commands
        $commandMap = [
            'AlertStudentAbsence' => 'alerts:student-absence',
            'AlertStaffLeave' => 'alerts:staff-leave',
            'AlertStaffEmployment' => 'alerts:staff-employment',
            'AlertStudentAdmission' => 'alerts:student-admission',
            'AlertStudentEnrolment' => 'alerts:student-enrolment',
            'AlertStudentStatusChange' => 'alerts:student-status-change',
            'AlertStudentStatus' => 'alerts:student-status-change',
            'AlertRetirementWarning' => 'alerts:retirement-warning',
            'AlertStaffType' => 'alerts:staff-type',
            'AlertSystemUpdates' => 'alerts:system-updates',
        ];

        $commandName = $commandMap[$processName] ?? null;

        // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlertCommand() Command mapping: ' . ($commandName ?? 'null')); //[TEMP-LOG]

        if (!$commandName) {
            // //Log::warning('[TEMP-LOG] @AlertTriggerService::triggerAlertCommand() NO COMMAND MAPPING - returning early'); //[TEMP-LOG]
            // Log::warning('[POCOR-9509 Phase 3] No command mapping for process', [
            //     'process_name' => $processName,
            //     'process_id' => $processId,
            // ]);
            return;
        }

        // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlertCommand() Command mapped successfully: ' . $commandName); //[TEMP-LOG]

        // POCOR-9509: Build command arguments
        // Event commands rely on query-backed placeholders, so pass the full context they expect.
        $arguments = [
            '--user_id' => $userId,
            '--rule_id' => $ruleId,
            '--process_id' => $processId,
        ];

        if ($commandName === 'alerts:student-absence') {
            // POCOR-9509: Absence alerts depend on the same ids the command query uses.
            $requiredKeys = ['student_id', 'academic_period_id'];
            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $context) || $context[$key] === null || $context[$key] === '') {
                    throw new \InvalidArgumentException('Missing required alert context: ' . $key);
                }
            }

            foreach (['student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'period', 'subject_id', 'date'] as $key) {
                if (array_key_exists($key, $context) && $context[$key] !== null && $context[$key] !== '') {
                    $arguments['--' . $key] = $context[$key];
                }
            }
        } elseif ($entityId !== null) {
            // POCOR-9509: Admission, enrolment and status commands all use a single entity id.
            $arguments['--entity_id'] = $entityId;
        }

        // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlertCommand() Final arguments: ' . json_encode($arguments)); //[TEMP-LOG]

        try {
            // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlertCommand() Calling Artisan::call()'); //[TEMP-LOG]
            // Dispatch command asynchronously
            \Illuminate\Support\Facades\Artisan::call($commandName, $arguments);

            // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlertCommand() Artisan::call() returned successfully'); //[TEMP-LOG]
            // Log::info('[POCOR-9509 Phase 3] Alert command triggered', [
            //     'command' => $commandName,
            //     'process_id' => $processId,
            //     'arguments' => $arguments,
            // ]);
        } catch (\Throwable $e) {
            // //Log::error('[TEMP-LOG] @AlertTriggerService::triggerAlertCommand() EXCEPTION in Artisan::call(): ' . $e->getMessage()); //[TEMP-LOG]
            Log::error('[POCOR-9509 Phase 3] Failed to trigger alert command', [
                'command' => $commandName,
                'process_id' => $processId,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }

        // //Log::debug('[TEMP-LOG] @AlertTriggerService::triggerAlertCommand() EXIT'); //[TEMP-LOG]
    }
}
