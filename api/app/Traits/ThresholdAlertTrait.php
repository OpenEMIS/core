<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\Api5\AlertLogs;
use App\Services\AlertProcessor\PlaceholderReplacer;
use App\Services\AlertProcessor\RecipientResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

/**
 * POCOR-9509: Reusable trait for threshold-based alert processing
 *
 * Provides generic alert functionality that can be used by any model
 * (Student, Staff, etc.) that needs to send alerts when thresholds are reached.
 *
 * Usage in your model:
 *   class MyModel extends Model {
 *       use ThresholdAlertTrait;
 *       protected $alertType = 'MyFeature'; // e.g., 'StudentAttendance', 'StaffAbsence'
 *       protected $institutionClassId = null; // Optional: for recipient resolution
 *
 *       protected function getThresholdData(): array { ... }
 *       protected function getSubjectPlaceholders(): array { ... }
 *       protected function getAuditLabel(): string { ... }
 *   }
 *
 *   // In your model's boot() method or controller:
 *   $result = $model->processThresholdAlert($institutionId, [
 *       'student_id' => 123,
 *       'academic_period_id' => 456
 *   ]);
 */
trait ThresholdAlertTrait
{
    /**
     * POCOR-9509: Process threshold-based alerts
     *
     * Main entry point for threshold alert processing.
     *
     * @param int $institutionId Institution ID
     * @param array $context Additional context (student_id, academic_period_id, etc.)
     * @return array Result ['sent' => bool, 'message' => string, 'details' => array]
     */
    public function processThresholdAlert(int $institutionId, array $context = [], ?int $specificUserId = null): array
    {
        $alertType = $this->getAlertTypeForAudit();
        $label = $this->getAuditLabel();

        // Log::debug("[POCOR-9509] Starting threshold alert processing ($label)", [
        //     'alert_type' => $alertType,
        //     'institution_id' => $institutionId,
        //     'context' => $context,
        // ]);

        try {
            // Get active alert rule
            $alertRule = self::getActiveAlertRule($alertType, $institutionId);

            if (!$alertRule) {
                // Log::debug("[POCOR-9509] No active alert rule for $label");
                return [
                    'sent' => false,
                    'message' => "No active alert rule configured for $alertType",
                    'details' => null,
                ];
            }

            // Get threshold data (absences, tardies, etc.)
            $thresholdData = $this->getThresholdData($context);

            if (!$this->isThresholdMet($alertRule->threshold, $thresholdData)) {
                // Log::debug("[POCOR-9509] Threshold not reached ($label)", [
                //     'current' => $thresholdData['current'],
                //     'threshold' => $alertRule->threshold,
                // ]);
                return [
                    'sent' => false,
                    'message' => "Threshold not reached ({$thresholdData['current']} < {$alertRule->threshold})",
                    'details' => $thresholdData,
                ];
            }

            // Get recipient data
            $subjectPlaceholders = $this->getSubjectPlaceholders($context);

            if (empty($subjectPlaceholders)) {
                return [
                    'sent' => false,
                    'message' => 'Unable to retrieve subject data for alert',
                    'details' => null,
                ];
            }

            // Resolve recipients
            $recipientResolver = new RecipientResolver();
            $roles = self::getAlertRuleRoles($alertRule->id);

            if (empty($roles)) {
                return [
                    'sent' => false,
                    'message' => 'No roles assigned to alert rule',
                    'details' => null,
                ];
            }

            $contacts = ['email' => [], 'phone' => []];
            if ($specificUserId !== null) {
                // If a specific user ID is provided, resolve contacts for that student and their guardians
                $contacts = $recipientResolver->getStudentAssociatedContactList($roles, $specificUserId);
                // Log::debug("[POCOR-9509] Using getStudentAssociatedContactList for specific user", [
                //     'student_user_id' => $specificUserId,
                //     'roles' => array_column($roles, 'id'),
                //     'contacts' => $contacts
                // ]);
            } else {
                // Otherwise, use the general role-associated contact list
                $institutionClassId = $context['institution_class_id'] ?? $this->institutionClassId ?? null;
                $contacts = $recipientResolver->getRoleAssociatedContactList($roles, $institutionId, $institutionClassId);
                // Log::debug("[POCOR-9509] Using getRoleAssociatedContactList for general roles", [
                //     'institution_id' => $institutionId,
                //     'institution_class_id' => $institutionClassId,
                //     'roles' => array_column($roles, 'id'),
                //     'contacts' => $contacts
                // ]);
            }

            if (empty($contacts['email']) && empty($contacts['phone'])) {
                // Log::warning("[POCOR-9509] No contacts found for $label", [
                //     'roles' => array_column($roles, 'id'),
                // ]);
                return [
                    'sent' => false,
                    'message' => 'No contacts found for configured roles',
                    'details' => ['roles' => $roles],
                ];
            }

            // Build placeholders
            $placeholders = array_merge(
                $subjectPlaceholders,
                [
                    '${current_value}' => (string)$thresholdData['current'],
                    '${threshold}' => (string)$alertRule->threshold,
                ]
            );

            // Replace placeholders
            $placeholderReplacer = new PlaceholderReplacer();
            $subject = $placeholderReplacer->replace($alertRule->subject, $placeholders);
            $message = $placeholderReplacer->replace($alertRule->message, $placeholders);

            // Send alerts
            $methods = array_map('trim', explode(',', strtolower($alertRule->method)));
            $sentCount = 0;

            if (in_array('email', $methods, true)) {
                foreach ($contacts['email'] as $email) {
                    if (self::queueAlertDirect(
                        'email',
                        $email,
                        $subject,
                        $message,
                        $alertType,
                        array_merge($context, ['rule_id' => $alertRule->id])
                    )) {
                        $sentCount++;
                    }
                    usleep(100000); // 0.1 second delay
                }
            }

            if (in_array('sms', $methods, true)) {
                foreach ($contacts['phone'] as $phone) {
                    if (self::queueAlertDirect(
                        'sms',
                        $phone,
                        null,
                        $message,
                        $alertType,
                        array_merge($context, ['rule_id' => $alertRule->id])
                    )) {
                        $sentCount++;
                    }
                    usleep(100000); // 0.1 second delay
                }
            }

            // Log::info("[POCOR-9509] Threshold alerts queued ($label)", [
            //     'alert_queued' => $sentCount,
            //     'emails' => count($contacts['email'] ?? []),
            //     'sms' => count($contacts['phone'] ?? []),
            // ]);

            return [
                'sent' => true,
                'message' => "Successfully queued {$sentCount} alerts",
                'details' => [
                    'current_value' => $thresholdData['current'],
                    'threshold' => $alertRule->threshold,
                    'emails_sent' => count($contacts['email'] ?? []),
                    'sms_sent' => count($contacts['phone'] ?? []),
                    'subject' => $subject,
                    'message' => substr($message, 0, 200) . '...',
                ],
            ];
        } catch (\Throwable $e) {
            Log::error("[POCOR-9509] Failed to process threshold alert ($label)", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'sent' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'details' => null,
            ];
        }
    }

    /**
     * POCOR-9509: Get active alert rule
     *
     * @param string $featureName Feature name (e.g., 'StudentAttendance')
     * @param int|null $institutionId Optional institution filter
     * @return object|null Alert rule object
     */
    protected static function getActiveAlertRule(string $featureName, ?int $institutionId = null): ?object
    {
        $query = DB::table('alert_rules')
            ->where('feature', $featureName)
            ->join('alerts', 'alerts.name', '=', 'alert_rules.feature')
            ->where('alerts.frequency', '!=', 'Never')
            ->where('alert_rules.enabled', 1);

        return $query->select('alert_rules.*')->first();
    }

    /**
     * POCOR-9509: Универсальная проверка достижения порога.
     * Поддерживает как простые числа, так и сложные JSON-фильтры.
     */
    protected function isThresholdMet($thresholdRaw, array $currentData): bool
    {
        $current = isset($currentData['current']) ? (float)$currentData['current'] : 0;

        // Сценарий 1: Простое число (например, "3" или 1)
        if (is_numeric($thresholdRaw)) {
            return $current >= (float)$thresholdRaw;
        }

        // Сценарий 2: Сложный JSON
        $threshold = is_string($thresholdRaw) ? json_decode($thresholdRaw, true) : $thresholdRaw;

        // Если JSON невалиден или это не массив, не можем продолжать
        if (!is_array($threshold)) {
            return false;
        }

        // Извлекаем целевое значение и условие (по умолчанию 1 - "больше или равно")
        $targetValue = isset($threshold['value']) ? (float)$threshold['value'] : null;
        $condition = $threshold['condition'] ?? '1';

        // Проверка специфических фильтров (license_type, employment_type, staff_type и т.д.)
        $filters = [
            'license_type',
            'employment_type',
            'staff_type',
            'staff_leave_type',
            'category',
        ];

        foreach ($filters as $filter) {
            if (isset($threshold[$filter]) && isset($currentData[$filter])) {
                // Если в правиле указан конкретный тип/категория, а в данных он другой - порог не пройден
                if ($threshold[$filter] != $currentData[$filter]) {
                    return false;
                }
            }
        }

        // Проверка массивов (например, training_categories)
        if (isset($threshold['training_categories']) && isset($currentData['category_id'])) {
            if (!in_array($currentData['category_id'], (array)$threshold['training_categories'])) {
                return false;
            }
        }

        if (isset($threshold['workflow_steps']) && isset($currentData['status_id'])) {
            if (!in_array($currentData['status_id'], (array)$threshold['workflow_steps'])) {
                return false;
            }
        }

        if (isset($threshold['student_statuses']) && isset($currentData['status_id'])) {
            if (!in_array($currentData['status_id'], (array)$threshold['student_statuses'])) {
                return false;
            }
        }

        // Финальное сравнение числового значения
        if ($targetValue !== null) {
            // Условие 1: Больше или равно (стандарт для пропусков)
            if ($condition == '1') {
                return $current >= $targetValue;
            }
            // Условие 2: Строгое соответствие (для специфических статусов)
            if ($condition == '2') {
                return $current == $targetValue;
            }
        }

        return true;
    }

    /**
     * POCOR-9509: Get alert rule security roles
     *
     * @param int $ruleId Alert rule ID
     * @return array Array of role objects with 'id' and 'name'
     */
    protected static function getAlertRuleRoles(int $ruleId): array
    {
        return DB::table('alerts_roles')
            ->join('security_roles', 'security_roles.id', '=', 'alerts_roles.security_role_id')
            ->where('alerts_roles.alert_rule_id', $ruleId)
            ->select('security_roles.id', 'security_roles.name')
            ->get()
            ->toArray();
    }

    /**
     * POCOR-9509: Queue alert directly to alert_queue table
     *
     * @param string $channel 'email' or 'sms'
     * @param string $recipient Email or phone number
     * @param string|null $subject Email subject
     * @param string $message Message body
     * @param string $alertType Alert type/feature
     * @param array $payload Additional metadata
     * @return bool True if queued successfully
     */
    protected static function queueAlertDirect(
        string  $channel,
        string  $recipient,
        ?string $subject,
        string  $message,
        string  $alertType,
        array   $payload = []
    ): bool
    {
        try {
            $queued = false;
            $checksum = self::generateChecksum($subject . $recipient . $alertType . $channel, $message);
            $existingRecord = DB::table('alert_logs')
                ->where('feature', $alertType)
                ->where('method', $channel)
                ->where('destination', $recipient)
                ->where('checksum', $checksum)
                ->first();
            if (!$existingRecord) {
                $queued = DB::table('alert_queue')->insert([
                    'alert_type' => $alertType,
                    'channel' => $channel,
                    'recipient' => $recipient,
                    'subject' => $subject,
                    'message_body' => $message,
                    'payload' => json_encode($payload),
                    'status' => AlertLogs::STATUS_PENDING, //POCOR-9509: use constant
                    'retry_count' => 0,
                    'available_at' => now(),
                    'created' => now(),
                    'modified' => now(),
                ]);
            }

            return $queued;
        } catch (\Throwable $e) {
            Log::error('[POCOR-9509] Failed to queue alert', [
                'channel' => $channel,
                'recipient' => $recipient,
                'exception' => $e->getMessage(),
            ]);
            return false;
        }
    }


    private static function generateChecksum(?string $subject, ?string $message): string
    {
        $subject = mb_strtolower($subject);
        $message = mb_strtolower($message);
        return hash('sha256', "{$subject},{$message}");
    }

    /**
     * POCOR-9509: Must be implemented by using model
     *
     * Return the alert type string for this model
     * (e.g., 'StudentAttendance', 'StaffAbsence')
     *
     * @return string Alert type name
     */
    protected function getAlertTypeForAudit(): string
    {
        return $this->alertType ?? 'ThresholdAlert';
    }

    /**
     * POCOR-9509: Must be implemented by using model
     *
     * Return a short label for this alert type (for logging)
     *
     * @return string Audit label
     */
    abstract protected function getAuditLabel(): string;

    /**
     * POCOR-9509: Must be implemented by using model
     *
     * Return the current threshold value and related data
     *
     * @param array $context Model-specific context
     * @return array ['current' => int, ...otherData]
     */
    abstract protected function getThresholdData(array $context): array;

    /**
     * POCOR-9509: Must be implemented by using model
     *
     * Return placeholder data for the alert message
     *
     * @param array $context Model-specific context
     * @return array ['${placeholder.name}' => 'value', ...]
     */
    abstract protected function getSubjectPlaceholders(array $context): array;
}
