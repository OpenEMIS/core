<?php

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;

/**
 * Command to send alerts for staff leave reminders.
 */
class AlertStaffEmploymentCommand extends AlertCommandBase
{
    const CONDITION_DAYS_BEFORE = 1;
    const CONDITION_DAYS_AFTER = 2;
    /**
     * Log alert (SMS or Email) into alert logs.
     *
     * @param string $method Message method (sms/email)
     * @param string $feature Feature name
     * @param string $recipient Recipient identifier
     * @param string $subject Subject text
     * @param string $message Body text
     */
    public function logAlert($method, $feature, $recipient, $subject, $message)
    {
        $this->AlertLogs->insertAlertLog($method, $feature, $recipient, $subject, $message);
    }

    /**
     * Main execute() entry point.
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        if (!$this->prepareContext($args, $io)) {
            return static::CODE_SUCCESS;
        }

        return $this->runFeatureAlert('StaffEmployment');
    }

    /**
     * Get pending leave records to alert on.
     *
     * @param string $featureKey Feature key
     * @return array List of leave entries to alert
     */
    protected function getPendingItems(string $featureKey): array
    {
        $this->loadModel('Staff.EmploymentStatuses');

        $thresholdArray = json_decode($this->rule['threshold'], true);
        $value = (int)($thresholdArray['value'] ?? 0);
        $statusTypeId = (int) ($thresholdArray['employment_type'] ?? 0);
        $condition = (int) ($thresholdArray['condition'] ?? 0);
        $this->logMsg(print_r($thresholdArray, true));

        if (!$statusTypeId || !$value || !in_array($condition, [self::CONDITION_DAYS_BEFORE, self::CONDITION_DAYS_AFTER], true)) {
            return [];
        }
        $this->logMsg(print_r($condition, true));

        $statusDateField = $this->EmploymentStatuses->aliasField('status_date');

        $dateCondition = match ($condition) {
            self::CONDITION_DAYS_BEFORE => "DATEDIFF($statusDateField, NOW()) BETWEEN 0 AND $value",
            self::CONDITION_DAYS_AFTER  => "DATEDIFF(NOW(), $statusDateField) BETWEEN 0 AND $value",
            default => null
        };
        $this->logMsg(print_r($condition, true));
        $this->logMsg(print_r($dateCondition, true));
        if (!$dateCondition) {
            return [];
        }

        $alertData = $this->EmploymentStatuses->find()
            ->contain(['Users', 'EmploymentStatusTypes'])
            ->where([
                $this->EmploymentStatuses->aliasField('status_type_id') => $statusTypeId,
                "$statusDateField IS NOT NULL",
                $dateCondition
            ])
            ->enableHydration(false);

        $this->logMsg($alertData->sql());
        return $alertData->toArray();
    }

    /**
     * Map placeholders for a leave alert.
     *
     * @param \Cake\Datasource\EntityInterface|array $item
     * @return array<string, string>
     */
    protected function fillPlaceholders($item): array
    {
        // Calculate day difference
        $today = FrozenDate::now();
        $leaveEndDate = isset($item['date_to']) ? new FrozenDate($item['date_to']) : null;
        $dayDiff = $leaveEndDate ? $today->diffInDays($leaveEndDate, false) : '';

        // This is assuming your rule is available here
        $thresholdValue = $this->rule['threshold'] ?? '{}';
        $threshold = json_decode($thresholdValue, true);

        return [
            '${threshold.value}' => $threshold['value'] ?? '',
            '${staff_leave_type.name}' => $item['staff_leave_type']['name'] ?? '',
            '${date_from}' => $item['date_from'] ?? '',
            '${date_to}' => $item['date_to'] ?? '',
            '${day_difference}' => (string)$dayDiff,

            '${user.openemis_no}' => $item['user']['openemis_no'] ?? '',
            '${user.first_name}' => $item['user']['first_name'] ?? '',
            '${user.middle_name}' => $item['user']['middle_name'] ?? '',
            '${user.third_name}' => $item['user']['third_name'] ?? '',
            '${user.last_name}' => $item['user']['last_name'] ?? '',
            '${user.preferred_name}' => $item['user']['preferred_name'] ?? '',
            '${user.email}' => $item['user']['email'] ?? '',
            '${user.address}' => $item['user']['address'] ?? '',
            '${user.postal_code}' => $item['user']['postal_code'] ?? '',
            '${user.date_of_birth}' => $item['user']['date_of_birth'] ?? '',

            '${institution.name}' => $item['institution']['name'] ?? '',
            '${institution.code}' => $item['institution']['code'] ?? '',
            '${institution.address}' => $item['institution']['address'] ?? '',
            '${institution.postal_code}' => $item['institution']['postal_code'] ?? '',
            '${institution.contact_person}' => $item['institution']['contact_person'] ?? '',
            '${institution.telephone}' => $item['institution']['telephone'] ?? '',
            '${institution.fax}' => $item['institution']['fax'] ?? '',
            '${institution.email}' => $item['institution']['email'] ?? '',
            '${institution.website}' => $item['institution']['website'] ?? '',
        ];
    }

}
