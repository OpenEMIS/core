<?php

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

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
        $shortSubject = mb_strimwidth((string)$subject, 0, 100, '...');
        $shortMessage = mb_strimwidth((string)$message, 0, 100, '...');

        $this->logMsg("✅ Alert {$feature} logged via {$method} to {$recipient}. Subject: {$shortSubject} Message: {$shortMessage}");

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

        $this->EmploymentStatuses = $this->fetchTable('Staff.EmploymentStatuses');

        $thresholdArray = json_decode($this->rule['threshold'], true);
        $value = (int)($thresholdArray['value'] ?? 0);
        $statusTypeId = (int)($thresholdArray['employment_type'] ?? 0);
        $condition = (int)($thresholdArray['condition'] ?? 0);

        if (!$statusTypeId || !$value || !in_array($condition, [self::CONDITION_DAYS_BEFORE, self::CONDITION_DAYS_AFTER], true)) {
            return [];
        }

        $statusDateField = $this->EmploymentStatuses->aliasField('status_date');

        $dateCondition = match ($condition) {
            self::CONDITION_DAYS_BEFORE => "DATEDIFF($statusDateField, NOW()) BETWEEN 0 AND $value",
            self::CONDITION_DAYS_AFTER => "DATEDIFF(NOW(), $statusDateField) BETWEEN 0 AND $value",
            default => null
        };
        if (!$dateCondition) {
            return [];
        }

        $where = [
            $this->EmploymentStatuses->aliasField('status_type_id') => $statusTypeId,
            "$statusDateField IS NOT NULL",
            $dateCondition
        ];

        $userId = $this->userId;
        $isSuperAdmin = $this->Users->get($userId)->super_admin;

        if (!$isSuperAdmin) {
            $institutionIds = $this->SecurityGroupUsers->getInstitutionsByUser($userId);
            $where['InstitutionStaff.institution_id IN'] = $institutionIds; // POCOR-9213
        }
        $this->logMsg("Employment alert WHERE clause: " . print_r($where, true));


        $alertData = $this->EmploymentStatuses->find()
            ->contain(['Users', 'EmploymentStatusTypes'])
            ->select([
                'institution_id' => 'InstitutionStaff.institution_id',
                'institution_name' => 'Institutions.name',
                'institution_code' => 'Institutions.code',
                'institution_address' => 'Institutions.address',
                'institution_postal_code' => 'Institutions.postal_code',
                'institution_contact_person' => 'Institutions.contact_person',
                'institution_telephone' => 'Institutions.telephone',
             //   'institution_fax' => 'Institutions.fax',
                'institution_email' => 'Institutions.email',
                'institution_website' => 'Institutions.website'
            ])
            ->innerJoin(['InstitutionStaff' => 'institution_staff'],
                ['InstitutionStaff.staff_id = EmploymentStatuses.staff_id',
                ])
            ->innerJoin(['Institutions' => 'institutions'],
                ['InstitutionStaff.institution_id = Institutions.id',
                ])
            ->innerJoin(['StaffStatuses' => 'staff_statuses'],
                ['InstitutionStaff.staff_status_id = StaffStatuses.id',
                    'StaffStatuses.code = "ASSIGNED"'
                ])
            ->where($where)
            ->distinct($this->EmploymentStatuses->aliasField('staff_id'))
            ->enableAutoFields()
            ->disableHydration();
        $alertData = $alertData->toArray();
//        $this->logMsg(print_r($alertData, true));
        return $alertData;
    }

    /**
     * Map placeholders for a leave alert.
     *
     * @param \Cake\Datasource\EntityInterface|array $item
     * @return array<string, string>
     */
    protected function fillPlaceholders($item): array
    {

        // This is assuming your rule is available here
        $thresholdValue = $this->rule['threshold'] ?? '{}';
        $threshold = json_decode($thresholdValue, true);

        return [
            '${threshold.value}' => $threshold['value'] ?? '',
            '${employment_type.name}' => $item['employment_status_type']['name'] ?? '',
            '${employment_date}' => $item['status_date'] ?? '',
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

            '${institution.name}' => $item['institution_name'] ?? '',
            '${institution.code}' => $item['institution_code'] ?? '',
            '${institution.address}' => $item['institution_address'] ?? '',
            '${institution.postal_code}' => $item['institution_postal_code'] ?? '',
            '${institution.contact_person}' => $item['institution_contact_person'] ?? '',
            '${institution.telephone}' => $item['institution_telephone'] ?? '',
          //  '${institution.fax}' => $item['institution_fax'] ?? '',
            '${institution.email}' => $item['institution_email'] ?? '',
            '${institution.website}' => $item['institution_website'] ?? '',
        ];
    }

}
