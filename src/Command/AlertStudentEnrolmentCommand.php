<?php

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\I18n\FrozenDate;
use Cake\Console\ConsoleOptionParser;

/**
 * Command to send alerts for staff leave reminders.
 */
class AlertStudentEnrolmentCommand extends AlertCommandBase
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
        $this->logMsg("✅ Alert {$feature} logged via {$method} to {$recipient}. Message: {$message}");

    }

    /**
     * Main execute() entry point.
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $this->loadModel('Institution.Students');
        $this->loadModel('Institution.StudentEnrolment');
        if (!$this->prepareContext($args, $io)) {
            return static::CODE_SUCCESS;
        }

        return $this->runFeatureAlert('StudentEnrolment');
    }

    /**
     * Get pending leave records to alert on.
     *
     * @param string $featureKey Feature key
     * @return array List of leave entries to alert
     */
    protected function getPendingItems(string $featureKey): array
    {
        $where = [
            'StudentEnrolment.id' => $this->enrolmentId,
        ];
        $this->logMsg("Where: " . print_r($where, true));
        return $this->StudentEnrolment->find()
            ->contain(['Users',
                'Statuses',
                'AcademicPeriods',
                'Institutions'])
            ->where($where)->toArray();
    }

    public function prepareContext(Arguments $args, ConsoleIo $io): bool
    {
        $this->setIo($io);
        $this->userId = (int)$args->getOption('user_id');
        $this->ruleId = (int)$args->getOption('rule_id');
        $this->processId = (int)$args->getOption('process_id');
        $this->enrolmentId = (int)$args->getOption('enrolment_id');
        $ruleId = $this->ruleId;


        if (!$this->userId ||
            !$this->ruleId ||
            !$this->processId ||
            !$this->enrolmentId
        ) {
            $io->error("Missing required option");
            return false;
        }
        try {
            $this->enrolment = $this->StudentEnrolment->get($this->enrolmentId);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $io->error("Enrolment with ID {$this->enrolmentId} not found.");
            return false;
        }
        try {
            $this->rule = $this->AlertRules->get($ruleId, ['contain' => ['SecurityRoles']]);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $io->error("Alert rule with ID {$ruleId} not found.");
            return false;
        }

        if (empty($this->rule->security_roles)) {
            $io->out("No roles assigned to alert rule ID {$ruleId}. Skipping.");
            return false;
        }

        $this->contacts = $this->getStudentAssociatedContactList($this->rule->security_roles, $this->enrolment->student_id);

        if (empty($this->contacts['email']) && empty($this->contacts['phone'])) {
            $io->out("No contacts found for alert rule ID {$ruleId}. Skipping.");
            return false;
        }
        $this->logMsg(print_r($this->contacts, true));
        return true;
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
            '${academic_period.name}' => $item['academic_period']['name'] ?? '',
            '${start_date}' => $item['start_date'] ?? '',
            '${end_date}' => $item['end_date'] ?? '',
//            '${day_difference}' => (string)$dayDiff,

            '${student.name}' => $item['user']['name'] ?? '',
            '${student.openemis_no}' => $item['user']['openemis_no'] ?? '',
            '${student.first_name}' => $item['user']['first_name'] ?? '',
            '${student.middle_name}' => $item['user']['middle_name'] ?? '',
            '${student.third_name}' => $item['user']['third_name'] ?? '',
            '${student.last_name}' => $item['user']['last_name'] ?? '',
            '${student.preferred_name}' => $item['user']['preferred_name'] ?? '',
            '${student.email}' => $item['user']['email'] ?? '',
            '${student.address}' => $item['user']['address'] ?? '',
            '${student.postal_code}' => $item['user']['postal_code'] ?? '',
            '${student.date_of_birth}' => $item['user']['date_of_birth'] ?? '',

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

    /**
     *  Function to get the list of the workflow steps by a given workflow model's model and the workflow status code
     *
     *  @param string $model The name of the model e.g. Institution.InstitutionSurveys
     *  @param string $code The code of the workflow status
     *  @return array The list of workflow steps id
     */
//    protected function getApprovedStepIds()
//    {
//        $WorkflowModelsTable = TableRegistry::getTableLocator()->get('Workflow.WorkflowModels');
//        $ids = $WorkflowModelsTable
//            ->find('all')
//            ->matching('Workflows.WorkflowSteps')
//            ->where([
//                $WorkflowModelsTable->aliasField('model') => 'Institution.StaffLeave',
//                'WorkflowSteps.name' => 'Approved'
//            ])
//            ->distinct(['WorkflowSteps.id'])
//            ->select(['id' => 'WorkflowSteps.id'])
//            ->toArray();
//        $distinctIds = array_column($ids, 'id');
//        return array_unique($distinctIds);
//    }

    public function getOptionParser(): ConsoleOptionParser
    {
        $parser = parent::getOptionParser();

        $parser->addOption('enrolment_id', [
            'help' => 'Specify the Enrolment ID for targeted alerts.',
            'required' => true,
            'short' => 'a'
        ]);

        return $parser;
    }

    /*
 * POCOR-9100
 */
//    private static function sendAlert($Enrolment, $recipient_id): void
//    {
//        $school_name = $Enrolment['institution']['name'];
//        $student_name = $Enrolment['user']['first_name'] . " " . $Enrolment['user']['last_name'];
//        $academic_year = $Enrolment['academic_period']['start_year'];
//        $grade_name = $Enrolment['education_grade']['name'];
//
//        $AlertsTable = TableRegistry::getTableLocator()->get('Alert.Alerts');
//        $key = "StudentEnrolment";
//
//        $AlertsTable->triggerStudentEnrolmentFeatureShell($key, $school_name, $student_name, $academic_year, $grade_name, $recipient_id);
//    }

//        Log::debug('Processing student Enrolment alert...');
//        $AlertRulesTable = TableRegistry::getTableLocator()->get('Alert.AlertRules');
//        $AlertRule = $AlertRulesTable
//            ->find('all')
//            ->select([
//                'id' => $AlertRulesTable->aliasField('id'),
//                'threshold' => $AlertRulesTable->aliasField('threshold')
//            ])
//            ->where([
//                $AlertRulesTable->aliasField('feature') => 'StudentEnrolment',
//                $AlertRulesTable->aliasField('enabled') => 1
//            ])
//            ->disableHydration()
//            ->first();
//
//        if (empty($AlertRule)) {
//            Log::debug('No enabled alert rule found for StudentEnrolment.');
//            return;
//        }
//
//        $thresholdValue = json_decode($AlertRule['threshold'], true);
//
//        if (empty($thresholdValue) || $thresholdValue['workflow_steps'][0] != 1) {
//            Log::debug('Alert threshold condition not met for StudentEnrolment.');
//            return;
//        }
//
//        $AlertRolesTable = TableRegistry::getTableLocator()->get('Alert.AlertsRoles');
//        $AlertRoles = $AlertRolesTable
//            ->find('all')
//            ->select(['security_role_id' => $AlertRolesTable->aliasField('security_role_id')])
//            ->where([$AlertRolesTable->aliasField('alert_rule_id') => $AlertRule['id']])
//            ->disableHydration()
//            ->toArray();
//
//        if (empty($AlertRoles)) {
//            Log::debug('No alert roles found for the alert rule.');
//            return;
//        }
//
//        $securityRoleIds = array_map(function ($role) {
//            return $role['security_role_id'];
//        }, $AlertRoles);
//
//        if (empty($securityRoleIds)) {
//            Log::debug('No security role IDs mapped for alert rule.');
//            return;
//        }
//
//        if (!in_array(self::ROLE_GUARDIAN, $securityRoleIds) && !in_array(self::ROLE_STUDENT, $securityRoleIds)) {
//            Log::debug('Neither guardian nor student roles are configured for alerts.');
//            return;
//        }
//
//        $WorkflowSteps = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
//        $stepEntity = $WorkflowSteps->find()
//            ->matching('Workflows.WorkflowModels')
//            ->where([$WorkflowSteps->aliasField('id') => $entity->status_id])
//            ->disableHydration()
//            ->first();
//
//        if (empty($stepEntity)) {
//            Log::debug('No matching workflow step found for Enrolment status.');
//            return;
//        }
//
////        if ($stepEntity['name'] !== 'Approved') {
//        if ($stepEntity['name'] !== $stepEntity['name']) {
//            Log::debug('Workflow step is not approved. Current step: ' . $stepEntity['name']);
//            return;
//        }
//        else {
//            Log::debug('Workflow step is ' . $stepEntity['name']);
//        }
//
//        $StudentEnrolmentsTable = TableRegistry::getTableLocator()->get('Institution.StudentEnrolment');
//        $Enrolment = $StudentEnrolmentsTable
//            ->find('all')
//            ->contain(['Users', 'AcademicPeriods', 'Institutions', 'EducationGrades'])
//            ->where([$StudentEnrolmentsTable->aliasField('id') => $entity->id])
//            ->first();
//
//        if (in_array(self::ROLE_GUARDIAN, $securityRoleIds)) {
//            $StudentGuardians = TableRegistry::getTableLocator()->get('GuardianNav.StudentGuardians');
//            $guardians = $StudentGuardians
//                ->find('all')
//                ->where([$StudentGuardians->aliasField('student_id') => $entity->student_id])
//                ->disableHydration()
//                ->toArray();
//
//            if (!empty($guardians)) {
//                foreach ($guardians as $guardian) {
//                    self::sendAlert($Enrolment, $guardian['guardian_id']);
//                }
//            } else {
//                Log::debug('No guardians found for student ID: ' . $entity->student_id);
//            }
//        }
//
//        if (in_array(self::ROLE_STUDENT, $securityRoleIds)) {
//            self::sendAlert($Enrolment, $entity->student_id);
//        }
}
