<?php
namespace App\Command;

use \App\Command\AlertCommandBase;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\Command;
use Cake\I18n\FrozenTime;
use Cake\I18n\FrozenDate;

class AlertRetirementWarningCommand extends AlertCommandBase
{
    protected string $featureName = 'RetirementWarning';
    protected string $processName = 'AlertRetirementWarning';
    /**
     * Log alert (SMS or Email) into alert logs.
     *
     * @param string $method Message method (sms/email)
     * @param string $feature Feature name
     * @param string $recipient Recipient identifier
     * @param string $subject Subject text
     * @param string $message Body text
     */
    public function logAlert($method, $feature, $recipient, $subject, $message): void
    {
        $this->AlertLogs->insertAlertLog($method, $feature, $recipient, $subject, $message);
        $shortSubject = mb_strimwidth((string)$subject, 0, 100, '...');
        $shortMessage = mb_strimwidth((string)$message, 0, 100, '...');

        $this->logMsg("✅ Alert {$feature} logged via {$method} to {$recipient}. Subject: {$shortSubject} Message: {$shortMessage}");

    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        if (!$this->prepareContext($args, $io)) {
            return static::CODE_SUCCESS;
        }

        return $this->runFeatureAlert('StaffLeave');
    }

    private function getStaffApproachingRetirement(FrozenTime $warningDate): array
    {
        $StaffTable = $this->getTableLocator()->get('Institution.Staff');

        return $StaffTable->find('all')
            ->select(['id', 'user_id', 'retirement_date', 'institution_id'])
            ->where([
                'retirement_date IS NOT' => null,
                'retirement_date <=' => $warningDate
            ])
            ->toArray();
    }

    protected function getPendingItems(string $featureKey): array
    {
        $this->Staff = $this->fetchTable('Institution.Staff');
        $userId = $this->userId;
        $isSuperAdmin = $this->Users->get($userId)->super_admin;
        $threshold = json_decode($this->rule['threshold'], true);
        $yearBefore = (int)($threshold['value'] ?? 60); // Default to 60 years if not set

        $targetDate = FrozenDate::now()->modify("-{$yearBefore} years")->format('Y-m-d');

        $conditions = [
            'Users.date_of_birth <=' => $targetDate,
//            'Users.date_of_death IS' => null,
            'StaffStatuses.code' => 'ASSIGNED'
        ];

        if (!$isSuperAdmin) {
            $institutionIds = $this->SecurityGroupUsers->getInstitutionsByUser($userId);
            $conditions['Staff.institution_id IN'] = $institutionIds;
        }

//        $this->logMsg("Retirement alert WHERE clause: " . print_r($conditions, true));

        return $this->Staff->find()
            ->contain(['Users', 'Institutions', 'StaffStatuses'])
            ->where($conditions)
            ->toArray();
    }


    /**
     * Map placeholders for a leave alert.
     *
     * @param \Cake\Datasource\EntityInterface|array $item
     * @return array<string, string>
     */
    protected function fillPlaceholders($item): array
    {
        $today = FrozenDate::now();
        $birthDate = isset($item['user']['date_of_birth']) ? new FrozenDate($item['user']['date_of_birth']) : null;
        $age = $birthDate ? $birthDate->diff($today)->y : '';

        $thresholdValue = $this->rule['threshold'] ?? '{}';
        $threshold = json_decode($thresholdValue, true);
//        $this->logMsg(print_r($item, true));
        return [
            '${threshold.value}' => $threshold['value'] ?? '',
            '${age}' => (string)$age,

            '${openemis_no}' => $item['user']['openemis_no'] ?? '',
            '${first_name}' => $item['user']['first_name'] ?? '',
            '${middle_name}' => $item['user']['middle_name'] ?? '',
            '${third_name}' => $item['user']['third_name'] ?? '',
            '${last_name}' => $item['user']['last_name'] ?? '',
            '${preferred_name}' => $item['user']['preferred_name'] ?? '',
            '${email}' => $item['user']['email'] ?? '',
            '${address}' => $item['user']['address'] ?? '',
            '${postal_code}' => $item['user']['postal_code'] ?? '',
            '${date_of_birth}' => $item['user']['date_of_birth'] ?? '',

            '${institution.name}' => $item['institution']['name'] ?? '',
            '${institution.code}' => $item['institution']['code'] ?? '',
            '${institution.address}' => $item['institution']['address'] ?? '',
            '${institution.postal_code}' => $item['institution']['postal_code'] ?? '',
            '${institution.contact_person}' => $item['institution']['contact_person'] ?? '',
            '${institution.telephone}' => $item['institution']['telephone'] ?? '',
//          '${institution.fax}' => $item['institution']['fax'] ?? '',
            '${institution.email}' => $item['institution']['email'] ?? '',
            '${institution.website}' => $item['institution']['website'] ?? ''
        ];
    }
}
