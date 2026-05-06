<?php
declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Api5\AlertLogs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * POCOR-9509: Trait for adding alert queueing capabilities to any Eloquent model
 *
 * Usage:
 *   class Student extends Model {
 *       use QueueableAlerts;
 *
 *       protected $alertType = 'student_alert'; // Optional: set default alert type
 *   }
 *
 * Then in your model methods:
 *   $student->queueEmail('user@example.com', 'Subject', 'Message');
 *   $student->queueSms('+1234567890', 'SMS message');
 *
 * @package App\Models\Concerns
 */
trait QueueableAlerts
{
    /**
     * Queue an alert for asynchronous sending
     *
     * @param string $alertType Alert type/feature name (e.g., 'student_attendance')
     * @param string $channel Channel: 'email' or 'sms'
     * @param string $recipient Email address or phone number
     * @param string $messageBody Message content (placeholders should already be replaced)
     * @param string|null $subject Email subject (required for email, null for SMS)
     * @param array|null $payload Additional metadata to store with the alert
     * @param \DateTimeInterface|null $availableAt When the alert should be available for processing
     * @return bool True if queued successfully
     */
    public function queueAlert(
        string $alertType,
        string $channel,
        string $recipient,
        string $messageBody,
        ?string $subject = null,
        ?array $payload = null,
        ?\DateTimeInterface $availableAt = null
    ): bool {
        try {
            // Use model's default alert type if not specified
            if (empty($alertType) && property_exists($this, 'alertType')) {
                $alertType = $this->alertType;
            }

            // Add model context to payload
            $enrichedPayload = array_merge($payload ?? [], [
                'model_class' => get_class($this),
                'model_id' => $this->getKey(),
                'model_table' => $this->getTable(),
            ]);

            $inserted = DB::table('alert_queue')->insert([
                'alert_type' => $alertType,
                'channel' => $channel,
                'recipient' => $recipient,
                'subject' => $subject,
                'message_body' => $messageBody,
                'payload' => json_encode($enrichedPayload),
                'status' => AlertLogs::STATUS_PENDING, //POCOR-9509: use constant
                'retry_count' => 0,
                'available_at' => $availableAt ?? now(),
                'created' => now(),
                'modified' => now(),
            ]);

            if ($inserted) {
                // Log::debug('✅ [POCOR-9509] Alert queued via model trait', [
//                    'model' => get_class($this),
//                    'model_id' => $this->getKey(),
//                    'alert_type' => $alertType,
//                    'channel' => $channel,
//                    'recipient' => $recipient,
//                ]);
            }

            return $inserted;
        } catch (\Throwable $e) {
            Log::error('❌ [POCOR-9509] Failed to queue alert from model', [
                'model' => get_class($this),
                'model_id' => $this->getKey(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Queue an email alert
     *
     * @param string $recipient Email address
     * @param string $subject Email subject
     * @param string $message Email body
     * @param string|null $alertType Alert type (uses model's default if not specified)
     * @param array|null $payload Additional metadata
     * @return bool
     */
    public function queueEmail(
        string $recipient,
        string $subject,
        string $message,
        ?string $alertType = null,
        ?array $payload = null
    ): bool {
        return $this->queueAlert(
            $alertType ?? (property_exists($this, 'alertType') ? $this->alertType : 'email_alert'),
            'email',
            $recipient,
            $message,
            $subject,
            $payload
        );
    }

    /**
     * Queue an SMS alert
     *
     * @param string $phoneNumber Phone number with country code
     * @param string $message SMS message body
     * @param string|null $alertType Alert type (uses model's default if not specified)
     * @param array|null $payload Additional metadata
     * @return bool
     */
    public function queueSms(
        string $phoneNumber,
        string $message,
        ?string $alertType = null,
        ?array $payload = null
    ): bool {
        return $this->queueAlert(
            $alertType ?? (property_exists($this, 'alertType') ? $this->alertType : 'sms_alert'),
            'sms',
            $phoneNumber,
            $message,
            null, // SMS doesn't have subject
            $payload
        );
    }

    /**
     * Queue a delayed alert (scheduled for future delivery)
     *
     * @param string $channel 'email' or 'sms'
     * @param string $recipient Email or phone
     * @param string $message Message body
     * @param \DateTimeInterface $sendAt When to send the alert
     * @param string|null $subject Email subject (required for email)
     * @param string|null $alertType Alert type
     * @param array|null $payload Additional metadata
     * @return bool
     */
    public function queueDelayedAlert(
        string $channel,
        string $recipient,
        string $message,
        \DateTimeInterface $sendAt,
        ?string $subject = null,
        ?string $alertType = null,
        ?array $payload = null
    ): bool {
        return $this->queueAlert(
            $alertType ?? (property_exists($this, 'alertType') ? $this->alertType : 'delayed_alert'),
            $channel,
            $recipient,
            $message,
            $subject,
            $payload,
            $sendAt
        );
    }

    /**
     * Get queue statistics for alerts related to this model
     *
     * @return array
     */
    public function getAlertQueueStats(): array
    {
        $modelClass = get_class($this);
        $modelId = $this->getKey();

        return [
            'pending' => DB::table('alert_queue')
                ->where('status', AlertLogs::STATUS_PENDING)
                ->where('payload->model_class', $modelClass)
                ->where('payload->model_id', $modelId)
                ->count(),
            'processing' => DB::table('alert_queue')
                ->where('status', 1) //POCOR-9509: alert_queue STATUS_PROCESSING=1
                ->where('payload->model_class', $modelClass)
                ->where('payload->model_id', $modelId)
                ->count(),
            'failed' => DB::table('alert_queue')
                ->where('status', AlertLogs::STATUS_FAILED)
                ->where('payload->model_class', $modelClass)
                ->where('payload->model_id', $modelId)
                ->count(),
        ];
    }

    /**
     * Get all queued alerts for this model instance
     *
     * @param int|null $status Filter by status (0=pending, 1=processing, 2=sent, -1=failed)
     * @return \Illuminate\Support\Collection
     */
    public function getQueuedAlerts(?int $status = null)
    {
        $modelClass = get_class($this);
        $modelId = $this->getKey();

        $query = DB::table('alert_queue')
            ->where('payload->model_class', $modelClass)
            ->where('payload->model_id', $modelId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->orderBy('created', 'desc')->get();
    }
}
