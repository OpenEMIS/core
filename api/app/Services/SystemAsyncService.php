<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * POCOR-9694 — data layer for the {{Administration → Async Services}} screen.
 *
 * Pulls operator-visibility figures out of the four async surfaces that
 * cohabit on a v5.x deployment:
 *
 *   - {{system_processes}}  — OpenEMIS-native execution-tracking
 *   - {{alert_queue}}       — pending alert dispatch
 *   - {{webhook_queue}}     — pending webhook dispatch + final failures
 *   - {{failed_jobs}}       — Laravel queue worker terminal failures
 *
 * Intentionally framework-thin: every method returns a plain array so
 * the controller can pass it straight to {{response()->json()}} without
 * a transformer layer. Heavy lifting is left to MySQL — the queries are
 * cheap aggregates / scoped reads, not full-table scans.
 *
 * @see app/Http/Controllers/SystemAsyncController.php
 * @see api/storage/release-docs/POCOR-9694-README.md
 */
class SystemAsyncService
{
    /** webhook_queue.status value that means "final delivery failure". */
    private const WEBHOOK_STATUS_FAILED = -1;

    /** system_processes statuses that are still "in flight". */
    private const ACTIVE_PROCESS_STATUSES = [1, 2];

    /** Default stale-window for {{stuckProcesses()}} when no override is given. */
    private const DEFAULT_STUCK_HOURS = 1;

    /** Hard upper bound on `limit` to keep responses small. */
    private const MAX_PAGE_LIMIT = 200;

    /**
     * Single-shot health summary for the dashboard landing page.
     *
     * @return array{
     *   scheduler: array<string,string|null>,
     *   queues:    array<string,int>,
     *   errors:    array<string,int>,
     *   timestamp: string
     * }
     */
    public function overview(): array
    {
        return [
            'scheduler' => [
                'system_processes_last_activity' => $this->maxCreated('system_processes'),
                'alert_queue_last_send'          => $this->maxColumn('alert_queue', 'sent_at'),
                'webhook_queue_last_send'        => $this->maxColumn('webhook_queue', 'sent_at'),
            ],
            'queues' => [
                'alert_queue_pending'   => $this->countPending('alert_queue'),
                'webhook_queue_pending' => $this->countPending('webhook_queue'),
                'jobs_pending'          => $this->countTable('jobs'),
            ],
            'errors' => [
                'failed_jobs_total'      => $this->countTable('failed_jobs'),
                'webhook_failures_total' => $this->countWebhookFailures(),
                'stuck_processes_total'  => $this->countStuckProcesses(self::DEFAULT_STUCK_HOURS),
            ],
            'timestamp' => now()->toAtomString(),
        ];
    }

    public function failedJobs(int $page, int $limit, ?string $queue = null): array
    {
        $query = DB::table('failed_jobs')->select([
            'id', 'uuid', 'connection', 'queue', 'failed_at',
            DB::raw('LEFT(exception, 240) AS exception_preview'),
        ])->orderByDesc('failed_at');
        if ($queue !== null && $queue !== '') {
            $query->where('queue', $queue);
        }
        return $this->paginate($query, $page, $limit);
    }

    public function stuckProcesses(int $page, int $limit, ?int $hours = null): array
    {
        $threshold = max(1, $hours ?? self::DEFAULT_STUCK_HOURS);
        $query = DB::table('system_processes')
            ->whereIn('status', self::ACTIVE_PROCESS_STATUSES)
            ->whereRaw('created < (NOW() - INTERVAL ? HOUR)', [$threshold])
            ->orderByDesc('created');
        return $this->paginate($query, $page, $limit) + ['threshold_hours' => $threshold];
    }

    public function webhookFailures(int $page, int $limit): array
    {
        $query = DB::table('webhook_queue')
            ->select([
                'id', 'webhook_id', 'event_key', 'target_url', 'retry_count',
                'max_retries', 'response_status', 'sent_at', 'created',
                DB::raw('LEFT(last_error, 240) AS last_error_preview'),
            ])
            ->where('status', self::WEBHOOK_STATUS_FAILED)
            ->orderByDesc('created');
        return $this->paginate($query, $page, $limit);
    }

    /**
     * @return array{
     *   alert_queue:   array{pending:int, retrying:int},
     *   webhook_queue: array{pending:int, retrying:int, failed:int},
     *   jobs:          int,
     *   failed_jobs:   int
     * }
     */
    public function queueBacklog(): array
    {
        return [
            'alert_queue' => [
                'pending'  => $this->countPending('alert_queue'),
                'retrying' => DB::table('alert_queue')->where('retry_count', '>', 0)->count(),
            ],
            'webhook_queue' => [
                'pending'  => $this->countPending('webhook_queue'),
                'retrying' => DB::table('webhook_queue')->where('retry_count', '>', 0)
                                                       ->where('status', '!=', self::WEBHOOK_STATUS_FAILED)
                                                       ->count(),
                'failed'   => $this->countWebhookFailures(),
            ],
            'jobs'        => $this->countTable('jobs'),
            'failed_jobs' => $this->countTable('failed_jobs'),
        ];
    }

    /**
     * Re-enqueues a single failed/stuck row. Delegates the actual work to
     * the relevant artisan command so the runtime semantics stay in one
     * place — this method is just routing.
     *
     * Supported {{$kind}} values:
     *  - {{failed-job}}  → `queue:retry {id}` (Laravel)
     *  - {{webhook}}     → resets webhook_queue row to NEW(0)
     *  - {{alert}}       → resets alert_queue row to NEW(0)
     *
     * @return array{ok:bool, kind:string, id:int, action:string, message?:string}
     */
    public function retry(string $kind, int $id): array
    {
        switch ($kind) {
            case 'failed-job':
                $exitCode = Artisan::call('queue:retry', ['id' => [$id]]);
                return [
                    'ok'      => $exitCode === 0,
                    'kind'    => $kind,
                    'id'      => $id,
                    'action'  => 'queue:retry',
                    'message' => trim((string) Artisan::output()),
                ];

            case 'webhook':
                $rows = DB::table('webhook_queue')->where('id', $id)->update([
                    'status'        => 0,
                    'last_error'    => null,
                    'next_retry_at' => null,
                ]);
                return $this->retryResult($kind, $id, $rows, 'webhook_queue.status reset to NEW');

            case 'alert':
                $rows = DB::table('alert_queue')->where('id', $id)->update([
                    'status'     => 0,
                    'last_error' => null,
                ]);
                return $this->retryResult($kind, $id, $rows, 'alert_queue.status reset to NEW');

            default:
                return [
                    'ok'      => false,
                    'kind'    => $kind,
                    'id'      => $id,
                    'action'  => 'unsupported',
                    'message' => "Unknown retry kind '$kind'. Use failed-job | webhook | alert.",
                ];
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function maxCreated(string $table): ?string
    {
        return $this->maxColumn($table, 'created');
    }

    private function maxColumn(string $table, string $column): ?string
    {
        $row = DB::table($table)->selectRaw("MAX($column) AS max_value")->first();
        return $row->max_value ?? null;
    }

    private function countPending(string $table): int
    {
        return DB::table($table)->where('status', 0)->count();
    }

    private function countTable(string $table): int
    {
        return DB::table($table)->count();
    }

    private function countWebhookFailures(): int
    {
        return DB::table('webhook_queue')->where('status', self::WEBHOOK_STATUS_FAILED)->count();
    }

    private function countStuckProcesses(int $hours): int
    {
        return DB::table('system_processes')
            ->whereIn('status', self::ACTIVE_PROCESS_STATUSES)
            ->whereRaw('created < (NOW() - INTERVAL ? HOUR)', [max(1, $hours)])
            ->count();
    }

    /**
     * Standard paginated envelope for any builder. Caps `limit` at
     * MAX_PAGE_LIMIT so a runaway query string can't bloat the response.
     *
     * @return array{
     *   data:      array<int,array<string,mixed>>,
     *   page:      int,
     *   limit:     int,
     *   total:     int,
     *   last_page: int
     * }
     */
    private function paginate($query, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = min(self::MAX_PAGE_LIMIT, max(1, $limit));
        $total = (clone $query)->count();
        $rows  = $query->forPage($page, $limit)->get()->map(static fn ($row) => (array) $row)->all();
        return [
            'data'      => $rows,
            'page'      => $page,
            'limit'     => $limit,
            'total'     => $total,
            'last_page' => (int) max(1, ceil($total / $limit)),
        ];
    }

    private function retryResult(string $kind, int $id, int $rows, string $message): array
    {
        return [
            'ok'      => $rows > 0,
            'kind'    => $kind,
            'id'      => $id,
            'action'  => $rows > 0 ? 'reset' : 'no-op',
            'message' => $rows > 0 ? $message : "No row found in queue '$kind' with id=$id.",
        ];
    }
}
