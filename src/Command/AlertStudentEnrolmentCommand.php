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
        $shortSubject = mb_strimwidth((string)$subject, 0, 100, '...');
        $shortMessage = mb_strimwidth((string)$message, 0, 100, '...');

        $this->logMsg("✅ Alert {$feature} logged via {$method} to {$recipient}. Subject: {$shortSubject} Message: {$shortMessage}");
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
        $thresholdValue = $this->rule['threshold'] ?? '{}';
        $threshold = json_decode($thresholdValue, true);
        $workflowCategory = $threshold['workflow_steps'];

        $where = [
            'StudentEnrolment.id' => $this->enrolmentId,
            'Statuses.id IN' => $workflowCategory,
        ];
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
            $this->studentId = $this->enrolment->student_id;
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
            '${enrolment_status}' => $item['status']['name'] ?? '',

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
//            '${institution.fax}' => $item['institution']['fax'] ?? '',
            '${institution.email}' => $item['institution']['email'] ?? '',
            '${institution.website}' => $item['institution']['website'] ?? '',
        ];
    }


    public function getOptionParser(): ConsoleOptionParser
    {
        $parser = parent::getOptionParser();

        $parser->addOption('enrolment_id', [
            'help' => 'Specify the Enrolment ID for targeted alerts.',
            'required' => true,
            'short' => 'a'
        ]);
        $parser->addOption('status_id', [
            'help' => 'Specify the Status ID for targeted alerts.',
            'required' => false,
            'short' => 't'
        ]);

        return $parser;
    }


}
