<?php

namespace App\Command;

use App\Command\AlertCommandBase;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;

/**
 * Command to send alerts for staff leave reminders.
 */
class AlertStaffLeaveCommand extends AlertCommandBase
{
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

        return $this->runFeatureAlert('StaffLeave');
    }

    /**
     * Get pending leave records to alert on.
     *
     * @param string $featureKey Feature key
     * @return array List of leave entries to alert
     */
    protected function getPendingItems(string $featureKey): array
    {
        $this->StaffLeave = $this->fetchTable('Institution.StaffLeave');
        $approvedStatusIds = $this->getApprovedStepIds();
//        $this->logMsg("Approved Statis: " . print_r($approvedStatusIds, true));
//        $this->logMsg(print_r($approvedStatusIds,true));
        $threshold = json_decode($this->rule['threshold'], true);
        $daysBefore = (int)($threshold['value'] ?? 1); // default to 1 day
        $targetDate = FrozenDate::now()->addDays($daysBefore)->format('Y-m-d');
        $staff_leave_type = $threshold['staff_leave_type'];
        $userId = $this->userId;
        $isSuperAdmin = $this->Users->get($userId)->super_admin;
        $where = [
            'StaffLeave.status_id IN' => $approvedStatusIds,
            'StaffLeave.date_to' => $targetDate,
            'StaffLeave.staff_leave_type_id' => $staff_leave_type,
        ];
        if(!$isSuperAdmin){
            $institutionIds = $this->SecurityGroupUsers->getInstitutionsByUser($userId);
            $where['StaffLeave.institution_id IN'] = $institutionIds;
        }
//        $this->logMsg("Where: " . print_r($where, true));

        return $this->StaffLeave->find()
            ->matching('StaffLeaveTypes')
            ->contain(['Users',
                'Statuses',
                'StaffLeaveTypes',
                'Institutions'])
            ->where($where)->toArray();
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
            '${employment_period}' => (string)$dayDiff,

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
           // '${institution.fax}' => $item['institution']['fax'] ?? '',
            '${institution.email}' => $item['institution']['email'] ?? '',
            '${institution.website}' => $item['institution']['website'] ?? '',
        ];
    }

    /**
     *  Function to get the list of the workflow steps by a given workflow model's model and the workflow status code
     *
     *  @param string $model The name of the model e.g. Institution.InstitutionSurveys
     *  @param string $code The code of the workflow status
     *  @return array The list of workflow steps id
     */
    protected function getApprovedStepIds()
    {
        $WorkflowModelsTable = TableRegistry::getTableLocator()->get('Workflow.WorkflowModels');
        $ids = $WorkflowModelsTable
            ->find('all')
            ->matching('Workflows.WorkflowSteps')
            ->where([
                $WorkflowModelsTable->aliasField('model') => 'Institution.StaffLeave',
                'WorkflowSteps.name' => 'Approved'
            ])
            ->distinct(['WorkflowSteps.id'])
            ->select(['id' => 'WorkflowSteps.id'])
            ->toArray();
        $distinctIds = array_column($ids, 'id');
        return array_unique($distinctIds);
    }
}
