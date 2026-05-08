<?php
declare(strict_types=1);

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Services\SystemAsyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POCOR-9694 — HTTP layer for the {{Administration → Async Services}} dashboard.
 *
 * Each method is a thin pass-through to {{SystemAsyncService}} that
 * extracts query parameters, validates them, and wraps the service's
 * plain-array result in a JSON envelope. Heavy lifting lives in the
 * service so the controller stays trivial and testable.
 *
 * Routes (all gated by {{auth.jwt}}, all under {{/api/v4}}):
 *
 *   GET  /system-async/overview
 *   GET  /system-async/failed-jobs
 *   GET  /system-async/stuck-processes
 *   GET  /system-async/webhook-failures
 *   GET  /system-async/queue-backlog
 *   POST /system-async/retry/{kind}/{id}
 *
 * @see app/Services/SystemAsyncService.php
 * @see routes/api.php  (registration block in the v4 group)
 */
class SystemAsyncController extends Controller
{
    private const DEFAULT_LIMIT = 50;
    private const SUPPORTED_RETRY_KINDS = ['failed-job', 'webhook', 'alert'];

    public function __construct(private SystemAsyncService $service)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v4/system-async/overview",
     *     summary="Async runtime health summary",
     *     description="One-shot snapshot of scheduler activity, pending queue counts, and error totals across alert / webhook / job queues.",
     *     tags={"SystemAsync"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Health summary",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="scheduler", type="object"),
     *             @OA\Property(property="queues", type="object"),
     *             @OA\Property(property="errors", type="object"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function overview(): JsonResponse
    {
        return response()->json($this->service->overview());
    }

    /**
     * @OA\Get(
     *     path="/api/v4/system-async/failed-jobs",
     *     summary="List Laravel failed_jobs rows (paginated, newest first)",
     *     tags={"SystemAsync"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page",  in="query", required=false, @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", example=50)),
     *     @OA\Parameter(name="queue", in="query", required=false, description="Filter by queue name", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Paginated list"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function failedJobs(Request $request): JsonResponse
    {
        return response()->json($this->service->failedJobs(
            $this->page($request),
            $this->limit($request),
            $this->trimmedString($request, 'queue')
        ));
    }

    /**
     * @OA\Get(
     *     path="/api/v4/system-async/stuck-processes",
     *     summary="List system_processes still NEW(1) or RUNNING(2) older than ?hours",
     *     tags={"SystemAsync"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page",  in="query", required=false, @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", example=50)),
     *     @OA\Parameter(name="hours", in="query", required=false, description="Stale-window threshold in hours (default 1)", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Paginated list"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function stuckProcesses(Request $request): JsonResponse
    {
        return response()->json($this->service->stuckProcesses(
            $this->page($request),
            $this->limit($request),
            $request->filled('hours') ? (int) $request->query('hours') : null
        ));
    }

    /**
     * @OA\Get(
     *     path="/api/v4/system-async/webhook-failures",
     *     summary="List webhook_queue rows whose final status is FAILED (-1)",
     *     tags={"SystemAsync"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page",  in="query", required=false, @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", example=50)),
     *     @OA\Response(response=200, description="Paginated list"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function webhookFailures(Request $request): JsonResponse
    {
        return response()->json($this->service->webhookFailures(
            $this->page($request),
            $this->limit($request)
        ));
    }

    /**
     * @OA\Get(
     *     path="/api/v4/system-async/queue-backlog",
     *     summary="Aggregate counts across alert_queue / webhook_queue / jobs / failed_jobs",
     *     tags={"SystemAsync"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Backlog summary"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function queueBacklog(): JsonResponse
    {
        return response()->json($this->service->queueBacklog());
    }

    /**
     * @OA\Post(
     *     path="/api/v4/system-async/retry/{kind}/{id}",
     *     summary="Re-enqueue a single failed/stuck row by kind + id",
     *     description="Supported kinds: failed-job (Laravel queue:retry) | webhook (resets webhook_queue.status to NEW) | alert (resets alert_queue.status to NEW)",
     *     tags={"SystemAsync"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="kind", in="path", required=true, @OA\Schema(type="string", enum={"failed-job","webhook","alert"})),
     *     @OA\Parameter(name="id",   in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Retry outcome"),
     *     @OA\Response(response=400, description="Unsupported kind"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function retry(string $kind, int $id): JsonResponse
    {
        if (!in_array($kind, self::SUPPORTED_RETRY_KINDS, true)) {
            return response()->json([
                'ok'      => false,
                'kind'    => $kind,
                'id'      => $id,
                'action'  => 'unsupported',
                'message' => 'Unknown retry kind. Use failed-job | webhook | alert.',
            ], 400);
        }
        $result = $this->service->retry($kind, $id);
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    // -------------------------------------------------------------------------
    // Query helpers
    // -------------------------------------------------------------------------

    private function page(Request $request): int
    {
        return max(1, (int) $request->query('page', '1'));
    }

    private function limit(Request $request): int
    {
        return max(1, (int) $request->query('limit', (string) self::DEFAULT_LIMIT));
    }

    private function trimmedString(Request $request, string $key): ?string
    {
        $value = trim((string) $request->query($key, ''));
        return $value === '' ? null : $value;
    }
}
