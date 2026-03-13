<?php

// POCOR-9509: Laravel worker command to process alerts queue
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;
use App\Services\AlertSender\EmailSender;
use App\Services\AlertSender\SmsSender;
use App\Services\AlertSender\MessageSanitizer;

class ProcessAlertsQueue extends Command
{
    protected $signature = 'alerts:process {--limit=50}';
    protected $description = 'Process pending alerts from alerts_queue';

    public function handle(): int
    {
        $limit = (int)$this->option('limit');

        $alerts = DB::table('alerts_queue')
            ->where('status', 0)
            ->where('available_at', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($alerts->isEmpty()) {
            return Command::SUCCESS;
        }

        $this->info("Processing {$alerts->count()} alerts...");

        foreach ($alerts as $alert) {
            $this->processSingleAlert($alert);
        }

        return Command::SUCCESS;
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

            if (!$existingRecord) {
                DB::table('alert_logs')->insert([
                    'feature' => $alertType,
                    'method' => $channel,
                    'destination' => $recipient,
                    'status' => 0,
                    'subject' => $subject,
                    'message' => $message,
                    'checksum' => $checksum,
                    'created' => now(),
                ]);
            }
            // Lock the row using optimistic locking
            $updated = DB::table('alerts_queue')
                ->where('id', $alert->id)
                ->where('status', 0)
                ->update([
                    'status' => 1,
                    'modified' => now(),
                ]);

            if ($updated === 0) {
                // Another worker picked it up
                return;
            }

            // Send the alert within a transaction
            DB::transaction(function () use ($alert,
                $alertType,
                $channel,
                $recipient,
                $checksum) {
                $emailSender = app(EmailSender::class);
                $smsSender = app(SmsSender::class);

                // Dispatch by channel
                switch ($channel) {
                    case 'email':
                        $emailSender->send(
                            $recipient,
                            $alert->subject,
                            $alert->message_body
                        );
                        break;

                    case 'sms':
                        $smsSender->send(
                            $recipient,
                            MessageSanitizer::sanitize($alert->message_body)
                        );
                        break;

                    default:
                        throw new \RuntimeException('Unknown channel: ' . $alert->channel);
                }

                // Mark as sent
                DB::table('alerts_queue')
                    ->where('id', $alert->id)
                    ->update([
                        'status' => 2,
                        'sent_at' => now(),
                        'modified' => now(),
                    ]);

                DB::table('alert_logs')
                    ->where('feature', $alertType)
                    ->where('method', $channel)
                    ->where('destination', $recipient)
                    ->where('checksum', $checksum)
                    ->where('status', '=', 0)
                    ->update([
                        'status' => 1,
                        'processed_date' => now(),
                    ]);

//                Log::info('Alert sent successfully', [
//                    'alert_id' => $alert->id,
//                    'channel' => $channel,
//                    'alert_type' => $alertType,
//                ]);
            });

        } catch (Throwable $e) {
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
        $status = $retryCount >= $maxRetries ? -1 : 0;

        DB::table('alerts_queue')
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
