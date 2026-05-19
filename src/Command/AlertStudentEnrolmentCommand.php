<?php

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\I18n\FrozenDate;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\Query; // POCOR-9320
use Cake\ORM\TableRegistry; // POCOR-9320

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
//        $this->logMsg("✅ Alert {$feature} logged via {$method} to {$recipient}. Subject: {$subject} Message: {$message}");
    }

    /**
     * Main execute() entry point.
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $this->Students = $this->fetchTable('Institution.Students');
        $this->StudentEnrolment = $this->fetchTable('Institution.StudentEnrolment');
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
        // POCOR-9320 start
        $query = $this->StudentEnrolment->find();
        $query->contain(['Users',
            'Statuses',
            'AcademicPeriods',
            'Institutions',
            'EducationGrades'])
            ->where($where)
//            ->group($this->StudentEnrolment->aliasField('student_id'))
        ;
        $query = $this->addStudentGuardianFields($query);

        return $query->toArray();
    }

    private function addStudentGuardianFields(Query $query)
    {
        $guardians = TableRegistry::getTableLocator()->get('User.Users');
        $student_guardians = TableRegistry::getTableLocator()->get('Student.StudentGuardians');
        $guardian_relations = TableRegistry::getTableLocator()->get('Student.GuardianRelations');
        $guardian_contacts = TableRegistry::getTableLocator()->get('User.Contacts');
        $guardians->setAlias('guardians');
        $student_guardians->setAlias('student_guardians');
        $guardian_relations->setAlias('guardian_relations');
        $guardian_contacts->setAlias('guardian_contacts');
        $query
            ->leftJoin([$student_guardians->getAlias() => $student_guardians->getTable()], [
                $student_guardians->aliasField('student_id = ') . $this->StudentEnrolment->aliasField('student_id')
            ])
            ->leftJoin([$guardians->getAlias() => $guardians->getTable()], [
                $guardians->aliasField('id = ') . $student_guardians->aliasField('guardian_id')
            ])
            ->leftJoin([$guardian_relations->getAlias() => $guardian_relations->getTable()], [
                $guardian_relations->aliasField('id = ') . $student_guardians->aliasField('guardian_relation_id')
            ])
            ->leftJoin([$guardian_contacts->getAlias() => $guardian_contacts->getTable()], [
                $guardian_contacts->aliasField('security_user_id = ') . $guardians->aliasField('id'),
            ])
            ->orderAsc($guardian_relations->aliasField('order'))
            ->orderDesc($guardian_contacts->aliasField('preferred'));
        $query = $query->enableAutoFields();
        $this->logMsg($query->sql());
        $query->select([
            'academic_period_name' => 'AcademicPeriods.name',
            'status_name' => 'Statuses.name',
            'start_date' => 'StudentEnrolment.start_date',
            'end_date' => 'StudentEnrolment.end_date',
            'enrolment_status' => 'Statuses.name',
            'student_name' => "CONCAT(`Users`.`first_name`, ' ', `Users`.`last_name`)",
            'student_openemis_no' => 'Users.openemis_no',
            'student_first_name' => 'Users.first_name',
            'student_middle_name' => 'Users.middle_name',
            'student_third_name' => 'Users.third_name',
            'student_last_name' => 'Users.last_name',
            'student_preferred_name' => 'Users.preferred_name',
            'student_email' => 'Users.email',
            'student_postal_code' => 'Users.postal_code',
            'student_date_of_birth' => 'Users.date_of_birth',
            'institution_name' => 'Institutions.name',
            'institution_code' => 'Institutions.code',
            'institution_address' => 'Institutions.address',
            'institution_postal_code' => 'Institutions.postal_code',
            'institution_contact_person' => 'Institutions.contact_person',
            'institution_telephone' => 'Institutions.telephone',
            'institution_email' => 'Institutions.email',
            'institution_website' => 'Institutions.website',
            'grade_name' => 'EducationGrades.name',
            'guardian_name' => "CONCAT(`guardians`.`first_name`, ' ', `guardians`.`last_name`)",
            'guardian_relation' => $guardian_relations->aliasField('name'),
            'guardian_contact' => $guardian_contacts->aliasField('value'),
        ])
        ;

        return $query;
        // POCOR-9320 end
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
        // POCOR-9320 calculate fields
//        $this->logMsg(print_r($item, true));
        return [
            '${academic_period.name}' => $item['academic_period_name'] ?? '',
            '${start_date}' => $item['start_date'] ?? '',
            '${end_date}' => $item['end_date'] ?? '',
            '${enrolment_status}' => $item['enrolment_status'] ?? '',
            '${student.name}' => $item['student_name'] ?? '',
            '${student.openemis_no}' => $item['student_openemis_no'] ?? '',
            '${student.first_name}' => $item['student_first_name'] ?? '',
            '${student.middle_name}' => $item['student_middle_name'] ?? '',
            '${student.third_name}' => $item['student_third_name'] ?? '',
            '${student.last_name}' => $item['student_last_name'] ?? '',
            '${student.preferred_name}' => $item['student_preferred_name'] ?? '',
            '${student.email}' => $item['student_email'] ?? '',
            '${student.address}' => $item['student_address'] ?? '',
            '${student.postal_code}' => $item['student_postal_code'] ?? '',
            '${student.date_of_birth}' => $item['student_date_of_birth'] ?? '',

            '${institution.name}' => $item['institution_name'] ?? '',
            '${institution.code}' => $item['institution_code'] ?? '',
            '${institution.address}' => $item['institution_address'] ?? '',
            '${institution.postal_code}' => $item['institution_postal_code'] ?? '',
            '${institution.contact_person}' => $item['institution_contact_person'] ?? '',
            '${institution.telephone}' => $item['institution_telephone'] ?? '',
            '${institution.email}' => $item['institution_email'] ?? '',
            '${institution.website}' => $item['institution_website'] ?? '',
            '${grade.name}' => $item['grade_name'] ?? '', // POCOR-9320 start
            '${guardian.name}' => $item['guardian_name'] ?? '',
            '${guardian.relation}' => $item['guardian_relation'] ?? '',
            '${guardian.contact}' => $item['guardian_contact'] ?? '', // POCOR-9320 end
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
