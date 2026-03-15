<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\Log\Log;
use DateTimeInterface;

// POCOR-9509: Behavior to easily queue alerts from any table
class AlertQueueBehavior extends Behavior
{
    /**
     * Default configuration
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'alertType' => 'general', // Default alert type if not specified
    ];

    /**
     * Queue an email alert
     *
     * @param string $recipient Email address(es) - comma-separated or "Name <email>" format
     * @param string $subject Email subject
     * @param string $message Email body
     * @param string|null $alertType Alert type (defaults to config or 'general')
     * @param array|null $payload Optional metadata
     * @param \DateTimeInterface|null $availableAt When to send (default: now)
     * @return bool True if successfully queued
     */
    public function queueEmail(
        string $recipient,
        string $subject,
        string $message,
        ?string $alertType = null,
        ?array $payload = null,
        ?DateTimeInterface $availableAt = null
    ): bool {
        $alertType = $alertType ?? $this->getConfig('alertType');

        try {
            $AlertQueue = $this->_table->fetchTable('Alert.AlertQueue'); //POCOR-9509: consolidated into Alert plugin

            return $AlertQueue->queueAlert(
                $alertType,
                'email',
                $recipient,
                $message,
                $subject,
                $payload,
                $availableAt
            );
        } catch (\Exception $e) {
            Log::error('Failed to queue email alert', [
                'alert_type' => $alertType,
                'error' => $e->getMessage(),
                'table' => $this->_table->getTable(),
            ]);
            return false;
        }
    }

    /**
     * Queue an SMS alert
     *
     * @param string $recipient Phone number(s) - comma-separated, E.164 format (+1234567890)
     * @param string $message SMS body (will be sanitized by worker)
     * @param string|null $alertType Alert type (defaults to config or 'general')
     * @param array|null $payload Optional metadata
     * @param \DateTimeInterface|null $availableAt When to send (default: now)
     * @return bool True if successfully queued
     */
    public function queueSms(
        string $recipient,
        string $message,
        ?string $alertType = null,
        ?array $payload = null,
        ?DateTimeInterface $availableAt = null
    ): bool {
        $alertType = $alertType ?? $this->getConfig('alertType');

        try {
            $AlertQueue = $this->_table->fetchTable('Alert.AlertQueue'); //POCOR-9509: consolidated into Alert plugin

            return $AlertQueue->queueAlert(
                $alertType,
                'sms',
                $recipient,
                $message,
                null,
                $payload,
                $availableAt
            );
        } catch (\Exception $e) {
            Log::error('Failed to queue SMS alert', [
                'alert_type' => $alertType,
                'error' => $e->getMessage(),
                'table' => $this->_table->getTable(),
            ]);
            return false;
        }
    }

    /**
     * Queue a generic alert
     *
     * @param string $channel Delivery channel (email|sms)
     * @param string $recipient Email or phone
     * @param string $message Message body
     * @param string|null $subject Email subject (for email channel)
     * @param string|null $alertType Alert type
     * @param array|null $payload Optional metadata
     * @param \DateTimeInterface|null $availableAt When to send (default: now)
     * @return bool True if successfully queued
     */
    public function queueAlert(
        string $channel,
        string $recipient,
        string $message,
        ?string $subject = null,
        ?string $alertType = null,
        ?array $payload = null,
        ?DateTimeInterface $availableAt = null
    ): bool {
        if ($channel === 'email') {
            return $this->queueEmail($recipient, $subject ?? '', $message, $alertType, $payload, $availableAt);
        } elseif ($channel === 'sms') {
            return $this->queueSms($recipient, $message, $alertType, $payload, $availableAt);
        }

        Log::error('Invalid channel for alert queue', [
            'channel' => $channel,
            'table' => $this->_table->getTable(),
        ]);
        return false;
    }
}
