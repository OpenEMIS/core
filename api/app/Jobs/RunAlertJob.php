<?php
declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * POCOR-9509: Queue-driven alert dispatch.
 *
 * Runs an existing alerts:* artisan command inside a queue worker. The artisan
 * command itself owns the system_processes row lifecycle (1 → 2 → 3 / -2) via
 * prepareContext / completeProcess / failProcess / markProcessFailed. This Job
 * just provides queue-mediated backpressure so a burst of triggers (e.g. a
 * country marking attendance) doesn't spawn 5k PHP-FPM processes at once.
 */
class RunAlertJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public string $commandName,
        public array $options
    ) {
        $this->onQueue('alerts');
    }

    public function handle(): void
    {
        // Log::debug('[TEMP-LOG] @RunAlertJob::handle() ENTRY'); //[TEMP-LOG]
        // Log::debug('[TEMP-LOG] @RunAlertJob::handle() commandName=' . $this->commandName . ', options=' . json_encode($this->options)); //[TEMP-LOG]

        $exitCode = Artisan::call($this->commandName, $this->options);

        // Log::debug('[TEMP-LOG] @RunAlertJob::handle() Artisan::call() returned exitCode=' . $exitCode); //[TEMP-LOG]

        if ($exitCode !== 0) {
            Log::warning('[POCOR-9509] RunAlertJob: command exited non-zero', [
                'command' => $this->commandName,
                'options' => $this->options,
                'exit_code' => $exitCode,
            ]);
        }

        // Log::debug('[TEMP-LOG] @RunAlertJob::handle() EXIT'); //[TEMP-LOG]
    }

    public function failed(\Throwable $e): void
    {
        // Log::debug('[TEMP-LOG] @RunAlertJob::failed() ENTRY - exception=' . $e->getMessage()); //[TEMP-LOG]
        Log::error('[POCOR-9509] RunAlertJob exhausted retries', [
            'command' => $this->commandName,
            'options' => $this->options,
            'exception' => $e->getMessage(),
        ]);

        // Backstop: artisan command owns the lifecycle, but if the worker died
        // before reaching it (or all retries failed) make sure system_processes
        // doesn't hang at status=1 forever.
        $processId = (int) ($this->options['--process_id'] ?? $this->options['process_id'] ?? 0);
        if ($processId > 0) {
            DB::table('system_processes')
                ->where('id', $processId)
                ->whereIn('status', [1, 2])
                ->update([
                    'status' => -2,
                    'end_date' => now(),
                    'modified' => now(),
                ]);
            // Log::debug('[TEMP-LOG] @RunAlertJob::failed() Updated system_processes status to -2'); //[TEMP-LOG]
        }
        // Log::debug('[TEMP-LOG] @RunAlertJob::failed() EXIT'); //[TEMP-LOG]
    }
}
