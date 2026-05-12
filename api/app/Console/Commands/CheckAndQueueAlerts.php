<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * POCOR-9509: Check alert frequency and queue alert commands
 *
 * This command replaces the synchronous shell-based alert triggering
 * in CakePHP with an async queue-based approach.
 *
 * Scheduled via: $schedule->command('alerts:check')->dailyAt('02:00');
 *
 * Behavior:
 * 1. Checks which alerts are enabled and have scheduled frequency
 * 2. Determines if enough time has passed since last run
 * 3. Queues alert commands asynchronously instead of blocking
 * 4. Updates system_processes table to track execution
 */
class CheckAndQueueAlerts extends Command
{
    protected $signature = 'alerts:check
        {--user_id=1 : User ID triggering the check}
        {--force : Force trigger all alerts regardless of frequency}
        {--sync : Run alerts synchronously (for cron) instead of queuing}'; //POCOR-9509: renamed from alerts:check-and-queue

    protected $description = 'Check alert frequency and trigger alert commands (POCOR-9509)';

    /**
     * Execute the command
     */
    public function handle(): int
    {
        $userId = (int) $this->option('user_id') ?? 1;
        $force = $this->option('force');
        $sync = $this->option('sync');

        // Log::info('[POCOR-9509] CheckAndQueueAlerts started', [
        //     'user_id' => $userId,
        //     'force' => $force,
        //     'sync' => $sync,
        // ]);

        try {
            //POCOR-9509: global stale-process sweep — abort any system_processes row stuck at status 1/2 for >= 1 day,
            //regardless of feature. The per-feature sweep in getLastRunDate() only covers features that get scheduled
            //in this run, so rows for misconfigured / disabled / never-rescheduled features used to hang forever.
            $aborted = DB::table('system_processes')
                ->whereIn('status', [1, 2])
                ->where('created', '<=', Carbon::now()->subDay())
                ->update([
                    'status' => -1, // ABORT
                    'end_date' => Carbon::now(),
                    'modified' => Carbon::now(),
                    'modified_user_id' => $userId,
                ]);

            if ($aborted > 0) {
                Log::warning('[POCOR-9509] Aborted stale system_processes (>= 1 day old)', [
                    'aborted_count' => $aborted,
                ]);
            }

            // Get all enabled alerts with valid frequency
            $alerts = DB::table('alerts')
                ->where('frequency', '!=', 'Never')
                ->where('frequency', '!=', 'Once')
                ->where('frequency', '!=', null)
                ->get(['id', 'name', 'frequency', 'process_name']);

            if ($alerts->isEmpty()) {
                // Log::info('[POCOR-9509] No alerts configured with frequency');
                return self::SUCCESS;
            }

            $triggeredCount = 0;

            foreach ($alerts as $alert) {
                // Skip certain alert types (POCOR-9391)
                if (in_array($alert->name, ['UnmarkedAttendance'])) {
                    continue;
                }

                // Check if alert should be triggered based on frequency
                if ($force || $this->shouldTriggerAlert($alert)) {
                    // Log::info('[POCOR-9509] Alert scheduled for execution', [
                    //     'feature' => $alert->name,
                    //     'frequency' => $alert->frequency,
                    //     'mode' => $sync ? 'sync' : 'async',
                    // ]);

                    // Queue or run alert command
                    $this->queueAlertCommand($alert, $userId, $sync);
                    $triggeredCount++;
                } else {
                    // Log::debug('[POCOR-9509] Alert not yet due', [
                    //     'feature' => $alert->name,
                    //     'frequency' => $alert->frequency,
                    // ]);
                }
            }

            // Log::info('[POCOR-9509] CheckAndQueueAlerts completed', [
            //     'triggered' => $triggeredCount,
            //     'total' => $alerts->count(),
            // ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('[POCOR-9509] CheckAndQueueAlerts failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }

    /**
     * POCOR-9509: Check if alert should trigger based on frequency and last run date
     */
    private function shouldTriggerAlert($alert): bool
    {
        $lastRun = $this->getLastRunDate($alert->process_name, $alert->name);

        // Never run before
        if ($lastRun === null) {
            // Log::debug('[POCOR-9509] Alert never run before', [
            //     '$alert->process_name' => $alert->process_name,
            //     '$alert->name' => $alert->name,
            // ]);
            return true;
        }

        // Still running (active process)
        if ($lastRun === false) {
            // Log::debug('[POCOR-9509] Alert still running', [
            //     '$alert->process_name' => $alert->process_name,
            //     '$alert->name' => $alert->name,
            // ]);
            return false;
        }

        // Check frequency against last run date
        $nextRun = $this->calculateNextRunDate($alert->frequency, $lastRun);

        if ($nextRun === null) {
            // 'Once' frequency - don't run again
            return false;
        }

        $shouldRun = Carbon::now(config('app.timezone'))->format('Y-m-d') >= $nextRun; //POCOR-9509: use app timezone so daily boundary matches server date

        // Log::debug('[POCOR-9509] Frequency check result', [
        //     'feature' => $alert->name,
        //     'frequency' => $alert->frequency,
        //     'last_run' => $lastRun->format('Y-m-d'),
        //     'next_run' => $nextRun,
        //     'should_run' => $shouldRun,
        // ]);

        return $shouldRun;
    }

    /**
     * POCOR-9509: Get last run date for alert from system_processes table
     *
     * @return Carbon|null Last run date, false if still running, null if never run
     */
    private function getLastRunDate(string $processName, string $alterName): false|Carbon|null
    {
        // 1. Получаем ВСЕ потенциально активные процессы
        $activeProcesses = DB::table('system_processes')
            ->whereIn('name', [$processName, $alterName])
            ->whereIn('status', [1, 2])
            ->orderBy('created')
            ->get();

        $isBlocked = false;

        foreach ($activeProcesses as $process) {
            $createdDate = Carbon::parse($process->created);

            // Если процесс висит больше 1 дня — помечаем как ABORT
            if ($createdDate->diffInDays(Carbon::now()) >= 1) {
                DB::table('system_processes')
                    ->where('id', $process->id)
                    ->update([
                        'status' => -1, // ABORT
                        'modified' => Carbon::now()
                    ]);

                // Log::warning("[POCOR-9509] Aborted stale process detected", [
                //     'process_id' => $process->id,
                //     'name' => $process->name
                // ]);
                continue; // Продолжаем цикл, чтобы почистить другие записи
            }

            // Если нашли процесс, которому МЕНЬШЕ дня — значит, система реально занята
            $isBlocked = true;
        }

        // Если после всей чистки остался хоть один живой процесс — выходим
        if ($isBlocked) {
            return false;
        }

        // 2. Получаем дату последнего успешного завершения
        $lastCompleted = DB::table('system_processes')
            ->whereIn('name', [$processName, $alterName])
            ->where('status', 3) // Completed
            ->orderByDesc('end_date')
            ->first(['end_date']);

        return $lastCompleted ? Carbon::parse($lastCompleted->end_date) : null;
    }

    /**
     * POCOR-9509: Calculate next run date based on frequency
     */
    private function calculateNextRunDate(string $frequency, Carbon $lastRun): ?string
    {
        $nextRun = $lastRun->copy();

        switch ($frequency) {
            case 'Daily':
                $nextRun->addDay();
                break;
            case 'Weekly':
                $nextRun->addWeek();
                break;
            case 'Monthly':
                $nextRun->addMonth();
                break;
            case 'Yearly':
                $nextRun->addYear();
                break;
            default:
                return '9999-12-31'; // Never run
        }

        return $nextRun->format('Y-m-d');
    }

    /**
     * POCOR-9509: Queue or run alert command
     *
     * This creates a system_processes record and dispatches the appropriate
     * Laravel artisan command based on the alert's process_name.
     *
     * Maps CakePHP process names to Laravel artisan commands:
     * - AlertRetirementWarning → alerts:retirement-warning (scheduled)
     * - AlertStaffEmployment → alerts:staff-employment (scheduled)
     * - AlertStaffLeave → alerts:staff-leave (scheduled)
     * - AlertSystemUpdates → alerts:system-updates (scheduled)
     * - AlertStaffType → alerts:staff-type (scheduled) [POCOR-9509]
     * - AlertStudentAbsence → alerts:student-absence (event-based, needs student_id)
     * - AlertStudentAdmission → alerts:student-admission (event-based, needs admission_id)
     * - AlertStudentEnrolment → alerts:student-enrolment (event-based, needs enrolment_id)
     *
     * Note: Event-based alerts (with required parameters) are triggered from specific
     * events via AlertLogsTable::triggerAlertSystemProcess(), not from this scheduler.
     *
     * @param object $alert Alert configuration
     * @param int $userId User ID
     * @param bool $sync Run synchronously (true) or queue (false)
     */
    private function queueAlertCommand($alert, int $userId, bool $sync = false): void
    {
        try {
            // Map process name to Laravel artisan command
            $commandMap = [
                'AlertRetirementWarning' => 'alerts:retirement-warning',
                'AlertStaffEmployment' => 'alerts:staff-employment',
                'AlertStaffLeave' => 'alerts:staff-leave',
                'AlertSystemUpdates' => 'alerts:system-updates',
                'AlertStaffType' => 'alerts:staff-type', // POCOR-9509: New scheduled alert
                'AlertCaseEscalation' => 'alerts:case-escalation', // POCOR-9509: Scheduled, runs against open cases
                'AlertLicenseValidity' => 'alerts:license-validity', // POCOR-9509: Scheduled, expiring licenses
                'AlertLicenseRenewal' => 'alerts:license-renewal', // POCOR-9509: Scheduled, insufficient training hours
                'AlertScholarshipApplication' => 'alerts:scholarship-application', // POCOR-9509: Scheduled, closing scholarships
                'AlertScholarshipDisbursement' => 'alerts:scholarship-disbursement', // POCOR-9509: Scheduled, upcoming disbursements
                // Event-based commands (require specific parameters, not scheduled):
                // 'AlertStudentAbsence' => 'alerts:student-absence',
                // 'AlertStudentAdmission' => 'alerts:student-admission',
                // 'AlertStudentEnrolment' => 'alerts:student-enrolment',
            ];

            $commandName = $commandMap[$alert->process_name] ?? null;

            if (!$commandName) {
                // Log::debug('[POCOR-9509] Alert not supported for scheduled execution', [
                //     'process_name' => $alert->process_name,
                //     'feature' => $alert->name,
                //     'reason' => 'Event-based alert, triggered from specific events only',
                // ]);
                return;
            }

            // Get alert rule for this alert
            $rule = DB::table('alert_rules')
                ->where('feature', $alert->name)
                ->where('enabled', 1)
                ->first();

            if (!$rule) {
                // Log::info('[POCOR-9509] No active alert rules for alert', [
                //     'feature' => $alert->name,
                // ]);
                return;
            }

            // POCOR-9509: Build params for tracking and deduplication
            $triggeredAt = Carbon::now(config('app.timezone'))->format('Y-m-d H:i:s'); //POCOR-9509: use app timezone
            $params = [
                'rule_id' => $rule->id,
                'trigger_type' => 'scheduled',
                'frequency' => $alert->frequency,
                'alert_name' => $alert->name,
                'process_name' => $alert->process_name,
                'command' => $commandName,
                'execution_mode' => $sync ? 'synchronous' : 'asynchronous',
                'triggered_at' => $triggeredAt,
                'triggered_by' => 'CheckAndQueueAlerts',
            ];

            // POCOR-9509: Generate checksum for deduplication
            $checksumData = [
                'alert_name' => $alert->name,
                'frequency' => $alert->frequency,
                'date' => Carbon::now()->format('Y-m-d'), // Same day = same checksum
            ];
            $params['checksum'] = hash('sha256', json_encode($checksumData));

            // Create system_processes record
            $processId = DB::table('system_processes')->insertGetId([
                'name' => $alert->name,
                'model' => $alert->process_name,
                'status' => 1, // Starting
                'start_date' => now(),
                'params' => json_encode($params), // POCOR-9509: Track execution context
                'created_user_id' => $userId,
                'created' => now(),
                'modified' => now(),
                'modified_user_id' => $userId,
            ]);

            // POCOR-9509: Dispatch command (sync for cron, async for queue workers)
            $arguments = [
                '--user_id' => $userId,
                '--rule_id' => $rule->id,
                '--process_id' => $processId,
            ];

            if ($sync) {
                // Synchronous execution (for cron-based scheduling)
                \Illuminate\Support\Facades\Artisan::call($commandName, $arguments);
                // Log::info('[POCOR-9509] Executed alert command synchronously', [
                //     'feature' => $alert->name,
                //     'command' => $commandName,
                //     'process_id' => $processId,
                // ]);
            } else {
                // Asynchronous execution (for queue workers)
                \Illuminate\Support\Facades\Artisan::queue($commandName, $arguments);
                // Log::info('[POCOR-9509] Queued alert command for background processing', [
                //     'feature' => $alert->name,
                //     'command' => $commandName,
                //     'process_id' => $processId,
                // ]);
            }

        } catch (\Throwable $e) {
            Log::error('[POCOR-9509] Failed to queue alert command', [
                'feature' => $alert->name,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
