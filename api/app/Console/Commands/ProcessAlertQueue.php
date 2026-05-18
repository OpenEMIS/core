<?php

// POCOR-9509: Laravel worker command to process alerts queue
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan; //POCOR-9509: needed for inline queue:work drain
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;
use App\Services\AlertSender\EmailSender;
use App\Services\AlertSender\SmsSender;
use App\Services\AlertSender\MessageSanitizer;
use App\Models\Api5\AlertLogs;

class ProcessAlertQueue extends Command
{
    //POCOR-9509: alert_queue states (AlertLogs only has 3 — no PROCESSING / DEDUPED states)
    const QUEUE_STATUS_PROCESSING = 1;
    const QUEUE_STATUS_SENT = 2;
    const QUEUE_STATUS_DEDUPED = 4; //POCOR-9509: row was a same-(feature,method,destination,checksum) duplicate — hidden from queue listing

    protected $signature = 'alerts:send {--limit=}'; //POCOR-9509: renamed from alerts:process; default resolved from ALERT_SEND_LIMIT env
    protected $description = 'Send pending alerts from alert_queue'; //POCOR-9509

    public function handle(): int
    {
        // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::handle() ENTRY'); //[TEMP-LOG]

        //POCOR-9509: Drain the Laravel `alerts` queue (jobs table) before sending.
        //Bridges the gap between attendance-driven RunAlertJob enqueues and alert_queue.
        //Removes the need for a separate queue:work daemon — every 10-min alerts:send cron
        //tick now does: jobs → alert_queue → SMTP/SMS in a single run.
        //Latency: up to 10 min (matches the alerts:send cron cadence). For sub-minute
        //dispatch, install the systemd queue worker instead.
        // // $jobsBefore = DB::table('jobs')->where('queue', 'alerts')->count(); //[TEMP-LOG]
        // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::handle() jobs(queue=alerts) BEFORE drain=' . $jobsBefore); //[TEMP-LOG]

        try {
            // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::handle() calling Artisan::queue:work --queue=alerts --stop-when-empty'); //[TEMP-LOG]
            $drainExit = Artisan::call('queue:work', [
                '--queue'           => 'alerts',
                '--stop-when-empty' => true,
            ]);
            // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::handle() queue:work returned exit=' . $drainExit); //[TEMP-LOG]

            // // $drainOutput = Artisan::output(); //[TEMP-LOG]
            // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::handle() queue:work stdout (truncated 4KB):' . PHP_EOL . substr($drainOutput, 0, 4096)); //[TEMP-LOG]
        } catch (\Throwable $e) {
            // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::handle() queue:work threw: ' . $e->getMessage()); //[TEMP-LOG]
            Log::warning('[POCOR-9509] alerts:send queue drain threw — continuing with alert_queue send', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(), //[TEMP-LOG]
            ]);
        }

        // // $jobsAfter = DB::table('jobs')->where('queue', 'alerts')->count(); //[TEMP-LOG]
        // // $failedCount = DB::table('failed_jobs')->count(); //[TEMP-LOG]
        // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::handle() jobs(queue=alerts) AFTER drain=' . $jobsAfter . ', failed_jobs=' . $failedCount . ', drained=' . ($jobsBefore - $jobsAfter)); //[TEMP-LOG]

        //POCOR-9509: resolve limit — CLI option overrides env; 0 means no limit (send all)
        $limit = $this->option('limit') !== null
            ? (int)$this->option('limit')
            : (int)env('ALERT_SEND_LIMIT', 50);
        // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::handle() resolved limit=' . $limit); //[TEMP-LOG]

        $query = DB::table('alert_queue')
            ->where('status', AlertLogs::STATUS_PENDING) //POCOR-9509: use constant
            ->where('available_at', '<=', now())
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit); //POCOR-9509: limit=0 skips this — sends all pending
        }

        $alerts = $query->get();

        // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::handle() alert_queue pending due now count=' . $alerts->count()); //[TEMP-LOG]

        if ($alerts->isEmpty()) {
            // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::handle() EXIT - nothing pending'); //[TEMP-LOG]
            return self::SUCCESS;
        }

        // $this->info("Processing {$alerts->count()} alerts..."); //POCOR-9509: commented out per CLAUDE.md

        foreach ($alerts as $alert) {
            // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::handle() processSingleAlert id=' . $alert->id . ', channel=' . $alert->channel . ', recipient=' . $alert->recipient . ', alert_type=' . $alert->alert_type); //[TEMP-LOG]
            $this->processSingleAlert($alert);
        }

        // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::handle() EXIT - processed ' . $alerts->count() . ' alerts'); //[TEMP-LOG]
        return self::SUCCESS;
    }

    // POCOR-9509: Process a single alert with optimistic locking and transactions
    private function processSingleAlert(object $alert): void
    {
        try {
            $alertType = $alert->alert_type;
            $channel = $alert->channel;
            $recipient = $alert->recipient;
            $subject = $alert->subject ?? $alert->message_body;
            $message = $alert->message_body;
            $checksum = self::generateChecksum($subject . $recipient . $alertType . $channel, $message);
            $existingRecord = DB::table('alert_logs')
                ->where('feature', $alertType)
                ->where('method', $channel)
                ->where('destination', $recipient)
                ->where('checksum', $checksum)
                ->first();

            //POCOR-9509: start - DEDUPED only fires when an existing alert_logs row is
            //already SENT — that means another queue row for the same identity already
            //delivered the message (multi-period same-day enqueue, etc.). If the existing
            //row is PENDING, it was pre-inserted by the CakePHP path (e.g. Messaging) and
            //THIS queue row owns the send — skip the insert below and let the post-send
            //UPDATE flip that PENDING row to SENT.
            //
            //Messaging is admin-triggered with explicit intent (each Send click must
            //deliver, even if the same subject/message/recipient was sent earlier).
            //Skip DEDUPED entirely for that feature.
            $isMessaging = $alertType === 'Messaging';
            $logAlreadySent = !$isMessaging
                && $existingRecord
                && (int)$existingRecord->status === AlertLogs::STATUS_SENT;
            if ($logAlreadySent) {
                DB::table('alert_queue')
                    ->where('id', $alert->id)
                    ->update([
                        'status' => self::QUEUE_STATUS_DEDUPED,
                        'modified' => now(),
                    ]);
                return;
            }

            if (!$existingRecord) {
                DB::table('alert_logs')->insert([
                    'feature' => $alertType,
                    'method' => $channel,
                    'destination' => $recipient,
                    'status' => AlertLogs::STATUS_PENDING, //POCOR-9509: use constant
                    'subject' => $subject,
                    'message' => $message,
                    'checksum' => $checksum,
                    'created_user_id' => 2, //POCOR-9509: system user (NOT NULL, no default)
                    'created' => now(),
                ]);
            }
            //POCOR-9509: end
            // Lock the row using optimistic locking
            $updated = DB::table('alert_queue')
                ->where('id', $alert->id)
                ->where('status', AlertLogs::STATUS_PENDING) //POCOR-9509: use constant
                ->update([
                    'status' => self::QUEUE_STATUS_PROCESSING, //POCOR-9509: alert_queue processing state
                    'modified' => now(),
                ]);

            if ($updated === 0) {
                // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::processSingleAlert() id=' . $alert->id . ' SKIPPED - another worker grabbed it (status changed)'); //[TEMP-LOG]
                // Another worker picked it up
                return;
            }
            // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::processSingleAlert() id=' . $alert->id . ' LOCKED status=PROCESSING'); //[TEMP-LOG]

            // Send the alert within a transaction
            DB::transaction(function () use ($alert,
                $alertType,
                $channel,
                $recipient,
                $checksum) {
                $emailSender = app(EmailSender::class);
                $smsSender = app(SmsSender::class);

                // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::processSingleAlert() id=' . $alert->id . ' DISPATCH channel=' . $channel . ' to=' . $recipient); //[TEMP-LOG]

                // Dispatch by channel
                switch ($channel) {
                    case 'email':
                        $emailSender->send(
                            $recipient,
                            $alert->subject,
                            $alert->message_body
                        );
                        // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::processSingleAlert() id=' . $alert->id . ' EmailSender->send() returned'); //[TEMP-LOG]
                        break;

                    case 'sms':
                        $smsSender->send(
                            $recipient,
                            MessageSanitizer::sanitize($alert->message_body)
                        );
                        // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::processSingleAlert() id=' . $alert->id . ' SmsSender->send() returned'); //[TEMP-LOG]
                        break;

                    default:
                        throw new \RuntimeException('Unknown channel: ' . $alert->channel);
                }

                // Mark as sent
                $nowLocal = Carbon::now(date_default_timezone_get()); //POCOR-9509: use PHP system timezone so CakePHP display matches
                DB::table('alert_queue')
                    ->where('id', $alert->id)
                    ->update([
                        'status' => self::QUEUE_STATUS_SENT, //POCOR-9509: alert_queue STATUS_SENT=2 (queue has extra PROCESSING state)
                        'sent_at' => $nowLocal,
                        'modified' => $nowLocal,
                    ]);

                DB::table('alert_logs')
                    ->where('feature', $alertType)
                    ->where('method', $channel)
                    ->where('destination', $recipient)
                    ->where('checksum', $checksum)
                    ->where('status', '=', AlertLogs::STATUS_PENDING)
                    ->update([
                        'status' => AlertLogs::STATUS_SENT, //POCOR-9509: use constant
                        'processed_date' => $nowLocal,
                    ]);

                // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::processSingleAlert() id=' . $alert->id . ' MARKED SENT (alert_queue + alert_logs)'); //[TEMP-LOG]
            });

        } catch (Throwable $e) {
            // // Log::debug('[TEMP-LOG] @ProcessAlertQueue::processSingleAlert() id=' . $alert->id . ' THREW: ' . $e->getMessage()); //[TEMP-LOG]
            $this->markFailed($alert, $e);
        }
    }

    private static function generateChecksum(?string $subject, ?string $message): string
    {
        $subject = mb_strtolower($subject);
        $message = mb_strtolower($message);
        return hash('sha256', "{$subject},{$message}");
    }

    // POCOR-9509: Mark alert as failed with exponential backoff
    private function markFailed(object $alert, Throwable $e): void
    {
        $maxRetries = config('alerts.max_retries', 3);

        $retryCount = $alert->retry_count + 1;
        $status = $retryCount >= $maxRetries ? AlertLogs::STATUS_FAILED : AlertLogs::STATUS_PENDING; //POCOR-9509: use constants

        DB::table('alert_queue')
            ->where('id', $alert->id)
            ->update([
                'status' => $status,
                'retry_count' => $retryCount,
                'last_error' => $e->getMessage(),
                'available_at' => now()->addMinutes($retryCount * 5),
                'modified' => now(),
            ]);

        Log::error('Alert sending failed', [
            'alert_id' => $alert->id,
            'channel' => $alert->channel,
            'alert_type' => $alert->alert_type,
            'retry_count' => $retryCount,
            'max_retries' => $maxRetries,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
