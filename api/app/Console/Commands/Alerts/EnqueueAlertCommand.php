<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use App\Jobs\RunAlertJob;
use Illuminate\Console\Command;

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
        $command = (string) $this->option('command');
        $optionsJson = (string) $this->option('options');

        if ($command === '') {
            $this->error('--command is required');
            return self::INVALID;
        }

        $options = $optionsJson === '' ? [] : json_decode($optionsJson, true);
        if (!is_array($options)) {
            $this->error('--options must be a JSON-encoded object');
            return self::INVALID;
        }

        RunAlertJob::dispatch($command, $options);
        return self::SUCCESS;
    }
}
