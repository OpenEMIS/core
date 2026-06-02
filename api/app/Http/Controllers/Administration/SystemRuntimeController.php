<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Api5\Tasks;
use App\Services\OpenemisRuntime\TasksRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

//POCOR-9694
//
// SystemRuntimeController — action / aggregate endpoints for the OpenEMIS Runtime UI.
//
// Plain resource reads of `tasks`, `task_jobs`, `task_failures` are served by
// CrudApiController under v5 (`/api/v5/tasks`, `/api/v5/task-jobs`,
// `/api/v5/task-failures`). This controller only exposes endpoints that
// CrudApiController cannot model — they live under v4:
//   GET  /api/v4/system-runtime/logs        (tail openemis-core-run.log)
//   GET  /api/v4/system-runtime/queue       (cross-table counts)
//   GET  /api/v4/system-runtime/scheduler   (last heartbeat + schedule:list)
//   POST /api/v4/system-runtime/tasks/{id}/retry
//   POST /api/v4/system-runtime/tasks/{id}/abort
class SystemRuntimeController extends Controller
{
    private TasksRecorder $recorder;

    //POCOR-9694
    public function __construct(TasksRecorder $recorder)
    {
        $this->recorder = $recorder;
    }

    /**
     * @OA\Get(
     *     path="/api/v4/system-runtime/logs",
     *     summary="Tail the OpenEMIS Runtime log",
     *     description="Returns the last N lines of api/storage/logs/openemis-core-run.log (cron output of the openemis-core:run scheduler tick). Read is capped at 64 KiB regardless of `lines`.",
     *     tags={"OpenEMIS Runtime"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="lines",
     *         in="query",
     *         required=false,
     *         description="Number of trailing lines to return (10–500, default 100).",
     *         @OA\Schema(type="integer", example=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="path", type="string", example="/var/www/html/emis/core/api/storage/logs/openemis-core-run.log"),
     *             @OA\Property(property="exists", type="boolean", example=true),
     *             @OA\Property(property="lines", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    //POCOR-9694: GET /logs — tail the openemis-core:run log (last N lines, capped)
    public function logs(Request $request): JsonResponse
    {
        $lines = min(500, max(10, (int) $request->input('lines', 100)));
        $path = storage_path('logs/openemis-core-run.log');

        if (!File::exists($path)) {
            return response()->json([
                'path' => $path,
                'exists' => false,
                'lines' => [],
            ]);
        }

        $tail = $this->tailFile($path, $lines);
        return response()->json([
            'path' => $path,
            'exists' => true,
            'lines' => $tail,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v4/system-runtime/queue",
     *     summary="Aggregate queue counts across OpenEMIS Tasks + legacy queue tables",
     *     description="Returns counts of `tasks` rows by status and by task_type, plus row counts of the legacy queue tables (webhook_queue, alert_queue, jobs, failed_jobs). Legacy tables that don't exist on a given install return null.",
     *     tags={"OpenEMIS Runtime"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="tasks_by_status", type="object", description="Map of tasks.status -> count"),
     *             @OA\Property(property="tasks_by_type", type="object", description="Map of tasks.task_type -> count"),
     *             @OA\Property(
     *                 property="legacy_tables", type="object",
     *                 @OA\Property(property="webhook_queue", type="integer", nullable=true),
     *                 @OA\Property(property="alert_queue", type="integer", nullable=true),
     *                 @OA\Property(property="jobs", type="integer", nullable=true),
     *                 @OA\Property(property="failed_jobs", type="integer", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    //POCOR-9694: GET /queue — aggregate counts across the abstraction + legacy tables
    public function queue(): JsonResponse
    {
        $taskCounts = Tasks::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $taskByType = Tasks::query()
            ->select('task_type', DB::raw('COUNT(*) as count'))
            ->groupBy('task_type')
            ->pluck('count', 'task_type');

        $legacy = [
            'webhook_queue' => $this->safeCount('webhook_queue'),
            'alert_queue' => $this->safeCount('alert_queue'),
            'jobs' => $this->safeCount('jobs'),
            'failed_jobs' => $this->safeCount('failed_jobs'),
        ];

        return response()->json([
            'tasks_by_status' => $taskCounts,
            'tasks_by_type' => $taskByType,
            'legacy_tables' => $legacy,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v4/system-runtime/scheduler",
     *     summary="OpenEMIS Runtime liveness + scheduled entries",
     *     description="Returns the most recent runtime_heartbeat row (stamped by openemis-core:run on every tick) and a parsed snapshot of the Laravel scheduler entries.",
     *     tags={"OpenEMIS Runtime"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="heartbeat", type="object", nullable=true,
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="task_type", type="string", example="runtime_heartbeat"),
     *                 @OA\Property(property="status", type="integer", example=2),
     *                 @OA\Property(property="started_at", type="string", format="date-time"),
     *                 @OA\Property(property="completed_at", type="string", format="date-time"),
     *                 @OA\Property(property="payload_json", type="object")
     *             ),
     *             @OA\Property(property="scheduled_entries", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    //POCOR-9694: GET /scheduler — heartbeat row + scheduled-entry summary
    public function scheduler(): JsonResponse
    {
        $heartbeat = Tasks::where('task_type', 'runtime_heartbeat')
            ->orderByDesc('id')
            ->first();

        //POCOR-9694: scheduled entries are read directly from Kernel::schedule via Artisan
        $entries = [];
        try {
            $output = '';
            \Artisan::call('schedule:list');
            $output = \Artisan::output();
            $entries = array_values(array_filter(array_map('trim', explode("\n", $output))));
        } catch (Throwable $e) {
            Log::warning('[POCOR-9694] schedule:list failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'heartbeat' => $heartbeat,
            'scheduled_entries' => $entries,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v4/system-runtime/tasks/{id}/retry",
     *     summary="Retry a failed/aborted OpenEMIS Task",
     *     description="Resets the `tasks` row back to status=NEW (0) and clears its started_at/completed_at so the next openemis-core:run tick picks it up. Idempotent.",
     *     tags={"OpenEMIS Runtime"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true,
     *         description="tasks.id",
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="retried", type="boolean", example=true),
     *             @OA\Property(property="task_id", type="integer", example=42)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    //POCOR-9694: POST /tasks/{id}/retry
    public function retryTask(int $id): JsonResponse
    {
        $ok = $this->recorder->recordRetry($id);
        return response()->json(['retried' => $ok, 'task_id' => $id]);
    }

    /**
     * @OA\Post(
     *     path="/api/v4/system-runtime/tasks/{id}/abort",
     *     summary="Force-abort an OpenEMIS Task",
     *     description="Marks the `tasks` row as status=ABORT (-1) and stamps completed_at. The next openemis-core:run tick will not pick it up.",
     *     tags={"OpenEMIS Runtime"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id", in="path", required=true,
     *         description="tasks.id",
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="aborted", type="boolean", example=true),
     *             @OA\Property(property="task_id", type="integer", example=42)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    //POCOR-9694: POST /tasks/{id}/abort
    public function abortTask(int $id): JsonResponse
    {
        $ok = $this->recorder->recordAbort($id);
        return response()->json(['aborted' => $ok, 'task_id' => $id]);
    }

    //POCOR-9694: defensive count — table may not exist on older installs
    private function safeCount(string $table): ?int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                return null;
            }
            return (int) DB::table($table)->count();
        } catch (Throwable $e) {
            return null;
        }
    }

    //POCOR-9694: simple tail — read last N lines from a small log file
    private function tailFile(string $path, int $lines): array
    {
        $size = filesize($path) ?: 0;
        $chunk = min($size, 64 * 1024); //POCOR-9694: cap read at 64 KiB
        if ($chunk === 0) {
            return [];
        }
        $fp = fopen($path, 'r');
        if ($fp === false) {
            return [];
        }
        fseek($fp, -$chunk, SEEK_END);
        $contents = fread($fp, $chunk) ?: '';
        fclose($fp);
        $all = preg_split("/\r?\n/", $contents) ?: [];
        $all = array_values(array_filter($all, fn ($l) => $l !== ''));
        return array_slice($all, -$lines);
    }
}
