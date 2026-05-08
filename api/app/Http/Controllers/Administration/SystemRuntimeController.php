<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Api5\TaskFailures;
use App\Models\Api5\TaskJobs;
use App\Models\Api5\Tasks;
use App\Services\OpenemisRuntime\TasksRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

//POCOR-9694
//
// SystemRuntimeController — backend for Administration → System → OpenEMIS Runtime.
//
// Surfaces the abstraction tables (`tasks`, `task_jobs`, `task_failures`) plus
// existing queue/scheduler state without exposing Laravel internals to the UI.
// Five endpoints map 1:1 to the five Runtime pages:
//   GET  /api/v5/system-runtime/tasks       (list w/ filters)
//   GET  /api/v5/system-runtime/failures    (list)
//   GET  /api/v5/system-runtime/logs        (tail openemis-core-run.log)
//   GET  /api/v5/system-runtime/queue       (counts by source/status)
//   GET  /api/v5/system-runtime/scheduler   (last heartbeat + scheduled entries)
// Plus two actions invoked from the UI:
//   POST /api/v5/system-runtime/tasks/{id}/retry
//   POST /api/v5/system-runtime/tasks/{id}/abort
class SystemRuntimeController extends Controller
{
    private TasksRecorder $recorder;

    //POCOR-9694
    public function __construct(TasksRecorder $recorder)
    {
        $this->recorder = $recorder;
    }

    //POCOR-9694: GET /tasks — paged list with optional task_type / status filter
    public function tasks(Request $request): JsonResponse
    {
        $query = Tasks::query();

        if ($request->filled('task_type')) {
            $query->where('task_type', $request->input('task_type'));
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }
        if ($request->filled('source_table')) {
            $query->where('source_table', $request->input('source_table'));
        }

        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));
        $tasks = $query->orderByDesc('id')->paginate($perPage);

        return response()->json($tasks);
    }

    //POCOR-9694: GET /failures — paged list of task_failures with task context
    public function failures(Request $request): JsonResponse
    {
        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));
        $failures = TaskFailures::with(['task', 'job'])
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($failures);
    }

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

    //POCOR-9694: POST /tasks/{id}/retry
    public function retryTask(int $id): JsonResponse
    {
        $ok = $this->recorder->recordRetry($id);
        return response()->json(['retried' => $ok, 'task_id' => $id]);
    }

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
