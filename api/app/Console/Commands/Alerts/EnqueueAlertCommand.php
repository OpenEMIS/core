<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use App\Jobs\RunAlertJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * POCOR-9509: Bridge command — CakePHP fires this, Laravel enqueues a queueable
 * Job, command exits. The actual alert work runs inside `queue:work --queue=alerts`.
 *
 * Replaces the previous direct `exec()` of per-feature artisan commands which
 * spawned a long-running PHP process per trigger. At country scale (e.g. 5k
 * schools marking attendance simultaneously) that approach blew through the
 * PHP-FPM pool. The queue mediates backpressure: triggers enqueue cheaply,
 * workers drain at their own pace.
 */
class EnqueueAlertCommand extends Command
{
    protected $signature = 'alerts:enqueue
        {--command= : Target artisan command, e.g. alerts:student-absence}
        {--options=  : JSON-encoded options array, e.g. {"--user_id":1,"--rule_id":54}}';

    protected $description = 'Enqueue an alert command run on the alerts queue.';

    public function handle(): int
    {
        // Log::debug('[TEMP-LOG] @EnqueueAlertCommand::handle() ENTRY'); //[TEMP-LOG]
        $command = (string) $this->option('command');
        $optionsJson = (string) $this->option('options');
        // Log::debug('[TEMP-LOG] @EnqueueAlertCommand::handle() command=' . $command . ', options_json_length=' . strlen($optionsJson)); //[TEMP-LOG]

        if ($command === '') {
            $this->error('--command is required');
            // Log::debug('[TEMP-LOG] @EnqueueAlertCommand::handle() EXIT EARLY - command empty'); //[TEMP-LOG]
            return self::INVALID;
        }

        $options = $optionsJson === '' ? [] : json_decode($optionsJson, true);
        if (!is_array($options)) {
            $this->error('--options must be a JSON-encoded object');
            // Log::debug('[TEMP-LOG] @EnqueueAlertCommand::handle() EXIT EARLY - options not JSON array'); //[TEMP-LOG]
            return self::INVALID;
        }

        // Log::debug('[TEMP-LOG] @EnqueueAlertCommand::handle() Calling RunAlertJob::dispatch()'); //[TEMP-LOG]
        RunAlertJob::dispatch($command, $options);
        // Log::debug('[TEMP-LOG] @EnqueueAlertCommand::handle() EXIT SUCCESS'); //[TEMP-LOG]
        return self::SUCCESS;
    }
}
