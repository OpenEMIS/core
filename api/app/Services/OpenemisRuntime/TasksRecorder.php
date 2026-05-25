<?php

namespace App\Services\OpenemisRuntime;

use App\Models\Api5\Tasks;
use App\Models\Api5\TaskJobs;
use App\Models\Api5\TaskFailures;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

//POCOR-9694
//
// TasksRecorder — single helper for dual-writing OpenEMIS Task lifecycle
// to the `tasks` / `task_jobs` / `task_failures` abstraction tables.
//
// Callers invoke recordEnqueue() when work is added to a legacy queue
// (alert_queue, webhook_queue, jobs), then recordStart() / recordSuccess()
// / recordFailure() during execution. Failures are best-effort logged —
// recorder NEVER throws back to the caller, because the legacy write must
// remain authoritative.
class TasksRecorder
{
    //POCOR-9694: enqueue — caller has just inserted into a legacy queue
    public function recordEnqueue(
        string $taskType,
        ?array $payload = null,
        ?string $sourceTable = null,
        ?int $sourceId = null,
        ?Carbon $availableAt = null
    ): ?Tasks {
        try {
            return Tasks::create([
                'task_type' => $taskType,
                'source_table' => $sourceTable,
                'source_id' => $sourceId,
                'payload_json' => $payload,
                'status' => Tasks::STATUS_NEW,
                'available_at' => $availableAt ?? Carbon::now(),
                'retry_count' => 0,
            ]);
        } catch (Throwable $e) {
            Log::warning('[POCOR-9694] TasksRecorder::recordEnqueue failed', [
                'task_type' => $taskType,
                'source_table' => $sourceTable,
                'source_id' => $sourceId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    //POCOR-9694: start of an attempt — creates a task_jobs row, flips tasks.status to PROCESSING
    public function recordStart(?int $taskId): ?TaskJobs
    {
        if ($taskId === null) {
            return null;
        }
        try {
            $task = Tasks::find($taskId);
            if (!$task) {
                return null;
            }
            $attemptNumber = (int) (TaskJobs::where('task_id', $taskId)->max('attempt_number') ?? 0) + 1;
            $job = TaskJobs::create([
                'task_id' => $taskId,
                'attempt_number' => $attemptNumber,
                'started_at' => Carbon::now(),
                'status' => TaskJobs::STATUS_PROCESSING,
            ]);
            $task->status = Tasks::STATUS_PROCESSING;
            $task->started_at = $task->started_at ?? Carbon::now();
            $task->save();
            return $job;
        } catch (Throwable $e) {
            Log::warning('[POCOR-9694] TasksRecorder::recordStart failed', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    //POCOR-9694: success — finalises both task_jobs and tasks rows
    public function recordSuccess(?int $taskId, ?int $taskJobId, ?string $messagePreview = null): void
    {
        if ($taskId === null) {
            return;
        }
        try {
            $endedAt = Carbon::now();

            if ($taskJobId !== null) {
                $job = TaskJobs::find($taskJobId);
                if ($job) {
                    $duration = $job->started_at ? (int) ($job->started_at->diffInMilliseconds($endedAt)) : null;
                    $job->ended_at = $endedAt;
                    $job->duration_ms = $duration;
                    $job->status = TaskJobs::STATUS_DONE;
                    $job->message_preview = $messagePreview ? mb_substr($messagePreview, 0, 500) : null;
                    $job->save();
                }
            }

            $task = Tasks::find($taskId);
            if ($task) {
                $task->status = Tasks::STATUS_DONE;
                $task->completed_at = $endedAt;
                $task->save();
            }
        } catch (Throwable $e) {
            Log::warning('[POCOR-9694] TasksRecorder::recordSuccess failed', [
                'task_id' => $taskId,
                'task_job_id' => $taskJobId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    //POCOR-9694: failure — finalises task_jobs, writes task_failures, flips tasks.status
    public function recordFailure(
        ?int $taskId,
        ?int $taskJobId,
        Throwable $e,
        bool $retryAllowed = true
    ): void {
        if ($taskId === null) {
            return;
        }
        try {
            $endedAt = Carbon::now();
            $messagePreview = mb_substr($e->getMessage(), 0, 500);

            if ($taskJobId !== null) {
                $job = TaskJobs::find($taskJobId);
                if ($job) {
                    $duration = $job->started_at ? (int) ($job->started_at->diffInMilliseconds($endedAt)) : null;
                    $job->ended_at = $endedAt;
                    $job->duration_ms = $duration;
                    $job->status = TaskJobs::STATUS_FAILED;
                    $job->message_preview = $messagePreview;
                    $job->save();
                }
            }

            TaskFailures::create([
                'task_id' => $taskId,
                'task_job_id' => $taskJobId,
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'stack_trace' => mb_substr($e->getTraceAsString(), 0, 60000),
                'retry_allowed' => $retryAllowed,
            ]);

            $task = Tasks::find($taskId);
            if ($task) {
                $task->status = Tasks::STATUS_FAILED;
                $task->retry_count = (int) $task->retry_count + 1;
                $task->save();
            }
        } catch (Throwable $inner) {
            Log::warning('[POCOR-9694] TasksRecorder::recordFailure failed', [
                'task_id' => $taskId,
                'task_job_id' => $taskJobId,
                'original_error' => $e->getMessage(),
                'recorder_error' => $inner->getMessage(),
            ]);
        }
    }

    //POCOR-9694: retry — resets a failed/aborted task back to NEW for the next runtime tick
    public function recordRetry(?int $taskId): bool
    {
        if ($taskId === null) {
            return false;
        }
        try {
            $task = Tasks::find($taskId);
            if (!$task) {
                return false;
            }
            $task->status = Tasks::STATUS_NEW;
            $task->started_at = null;
            $task->completed_at = null;
            $task->available_at = Carbon::now();
            $task->save();
            return true;
        } catch (Throwable $e) {
            Log::warning('[POCOR-9694] TasksRecorder::recordRetry failed', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    //POCOR-9694: force-abort — admin action via Runtime UI
    public function recordAbort(?int $taskId): bool
    {
        if ($taskId === null) {
            return false;
        }
        try {
            $task = Tasks::find($taskId);
            if (!$task) {
                return false;
            }
            $task->status = Tasks::STATUS_ABORT;
            $task->completed_at = Carbon::now();
            $task->save();
            return true;
        } catch (Throwable $e) {
            Log::warning('[POCOR-9694] TasksRecorder::recordAbort failed', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
