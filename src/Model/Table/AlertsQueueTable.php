<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use DateTime;
use DateTimeInterface;

// POCOR-9509: Alerts queue table for async alert processing
class AlertsQueueTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config Configuration options
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('alerts_queue');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        // POCOR-9509: Auto-manage created/modified timestamps
        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules
     *
     * @param \Cake\Validation\Validator $validator Validator instance
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->nonNegativeInteger('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('alert_type')
            ->maxLength('alert_type', 100)
            ->requirePresence('alert_type', 'create')
            ->notEmptyString('alert_type');

        $validator
            ->scalar('channel')
            ->maxLength('channel', 20)
            ->requirePresence('channel', 'create')
            ->notEmptyString('channel')
            ->inList('channel', ['email', 'sms'], 'Channel must be either email or sms');

        $validator
            ->scalar('recipient')
            ->maxLength('recipient', 255)
            ->requirePresence('recipient', 'create')
            ->notEmptyString('recipient');

        $validator
            ->scalar('subject')
            ->maxLength('subject', 255)
            ->allowEmptyString('subject');

        $validator
            ->scalar('message_body')
            ->requirePresence('message_body', 'create')
            ->notEmptyString('message_body');

        $validator
            ->integer('status')
            ->notEmptyString('status');

        $validator
            ->nonNegativeInteger('retry_count')
            ->notEmptyString('retry_count');

        return $validator;
    }

    /**
     * Queue an alert for async processing
     *
     * @param string $alertType Logical alert type (Attendance, Workflow, License, etc)
     * @param string $channel Delivery channel (email|sms)
     * @param string $recipient Email address or phone number
     * @param string $messageBody Message content
     * @param string|null $subject Email subject (optional, for email channel)
     * @param array|null $payload Optional structured payload for extensibility
     * @param \DateTimeInterface|null $availableAt When to process (default: now)
     * @return bool True if successfully queued
     */
    public function queueAlert(
        string $alertType,
        string $channel,
        string $recipient,
        string $messageBody,
        ?string $subject = null,
        ?array $payload = null,
        ?DateTimeInterface $availableAt = null
    ): bool {
        // POCOR-9509: Create alert queue entry
        $entity = $this->newEntity([
            'alert_type' => $alertType,
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'message_body' => $messageBody,
            'payload' => $payload ? json_encode($payload) : null,
            'status' => 0, // pending
            'retry_count' => 0,
            'available_at' => $availableAt ?? new DateTime(),
        ]);

        if ($this->save($entity)) {
            return true;
        }

        // Log validation errors if save failed
        if ($entity->hasErrors()) {
            $errors = $entity->getErrors();
            \Cake\Log\Log::error('Failed to queue alert', [
                'alert_type' => $alertType,
                'channel' => $channel,
                'errors' => $errors,
            ]);
        }

        return false;
    }

    /**
     * Queue an email alert
     *
     * @param string $recipient Email address(es) - comma-separated or "Name <email>" format
     * @param string $subject Email subject
     * @param string $message Email body
     * @param string $alertType Alert type for categorization
     * @param array|null $payload Optional metadata
     * @return bool True if successfully queued
     */
    public function queueEmail(
        string $recipient,
        string $subject,
        string $message,
        string $alertType = 'general',
        ?array $payload = null
    ): bool {
        return $this->queueAlert(
            $alertType,
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
     * @param string $recipient Phone number(s) - comma-separated, E.164 format (+1234567890)
     * @param string $message SMS body (will be sanitized by worker)
     * @param string $alertType Alert type for categorization
     * @param array|null $payload Optional metadata
     * @return bool True if successfully queued
     */
    public function queueSms(
        string $recipient,
        string $message,
        string $alertType = 'general',
        ?array $payload = null
    ): bool {
        return $this->queueAlert(
            $alertType,
            'sms',
            $recipient,
            $message,
            null,
            $payload
        );
    }

    /**
     * Get queue statistics
     *
     * @return array Statistics about queue state
     */
    public function getQueueStats(): array
    {
        $connection = $this->getConnection();

        $pending = $connection->execute(
            'SELECT COUNT(*) as count FROM alerts_queue WHERE status = 0'
        )->fetch('assoc')['count'] ?? 0;

        $processing = $connection->execute(
            'SELECT COUNT(*) as count FROM alerts_queue WHERE status = 1'
        )->fetch('assoc')['count'] ?? 0;

        $failed = $connection->execute(
            'SELECT COUNT(*) as count FROM alerts_queue WHERE status = -1'
        )->fetch('assoc')['count'] ?? 0;

        return [
            'pending' => (int) $pending,
            'processing' => (int) $processing,
            'failed' => (int) $failed,
        ];
    }
}
