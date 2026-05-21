<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

//POCOR-9694
//
// openemis-core:run — single-cron entry-point for the OpenEMIS Runtime.
//
// Wraps Laravel's scheduler so that operationally there is exactly ONE cron
// entry per OpenEMIS Core install:
//   * * * * * cd /var/www/html/emis/core/api && php artisan openemis-core:run
//
// All scheduled work (webhooks:process, alerts:check, alerts:send, …) is
// dispatched via the Laravel Schedule defined in App\Console\Kernel.
// We wrap rather than replace `schedule:run` so future tick-level work
// (queue draining, health snapshots) can hang off the same entry-point
// without touching Kernel::schedule().
class OpenemisCoreRunCommand extends Command
{
    //POCOR-9694
    protected $signature = 'openemis-core:run {--quiet-log : Suppress per-tick INFO log line}';

    //POCOR-9694
    protected $description = 'OpenEMIS Runtime tick — dispatches scheduled work (single-cron entry-point).';

    //POCOR-9694
    public function handle(): int
    {
        $startedAt = Carbon::now();
        $quietLog = (bool) $this->option('quiet-log');

        if (!$quietLog) {
            Log::info('[POCOR-9694] openemis-core:run tick started', ['started_at' => $startedAt->toIso8601String()]);
        }

        $exitCode = 0;
        try {
            //POCOR-9694: dispatch the Laravel scheduler — every entry in Kernel::schedule() runs through here
            $exitCode = Artisan::call('schedule:run');
            $output = trim(Artisan::output());
            if ($output !== '' && !$quietLog) {
                Log::info('[POCOR-9694] openemis-core:run scheduler output', ['output' => mb_substr($output, 0, 2000)]);
            }
        } catch (Throwable $e) {
            $exitCode = 1;
            Log::error('[POCOR-9694] openemis-core:run tick failed', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
            ]);
        }

        $endedAt = Carbon::now();
        $durationMs = (int) $startedAt->diffInMilliseconds($endedAt);

        //POCOR-9694: stamp last-tick metadata so the Runtime Scheduler page can show liveness
        $this->recordHeartbeat($startedAt, $endedAt, $durationMs, $exitCode);

        if (!$quietLog) {
            Log::info('[POCOR-9694] openemis-core:run tick finished', [
                'duration_ms' => $durationMs,
                'exit_code' => $exitCode,
            ]);
        }

        return $exitCode;
    }

    //POCOR-9694: persists last-tick info to a single row in `tasks` (task_type=runtime_heartbeat)
    //  Lets the Administration → System → OpenEMIS Runtime page surface "last tick" without
    //  scraping logs. Best-effort — heartbeat write must not fail the tick.
    private function recordHeartbeat(Carbon $startedAt, Carbon $endedAt, int $durationMs, int $exitCode): void
    {
        try {
            $payload = json_encode([
                'duration_ms' => $durationMs,
                'exit_code' => $exitCode,
                'started_at' => $startedAt->toIso8601String(),
                'ended_at' => $endedAt->toIso8601String(),
            ]);

            $existing = DB::table('tasks')
                ->where('task_type', 'runtime_heartbeat')
                ->orderBy('id', 'desc')
                ->first();

            $row = [
                'task_type' => 'runtime_heartbeat',
                'source_table' => null,
                'source_id' => null,
                'payload_json' => $payload,
                'status' => $exitCode === 0 ? 2 : -2, //POCOR-9694: STATUS_DONE or STATUS_FAILED
                'available_at' => $startedAt,
                'started_at' => $startedAt,
                'completed_at' => $endedAt,
                'retry_count' => 0,
            ];

            if ($existing) {
                DB::table('tasks')->where('id', $existing->id)->update($row);
            } else {
                DB::table('tasks')->insert($row);
            }
        } catch (Throwable $e) {
            Log::warning('[POCOR-9694] openemis-core:run heartbeat write failed', ['error' => $e->getMessage()]);
        }
    }
}
