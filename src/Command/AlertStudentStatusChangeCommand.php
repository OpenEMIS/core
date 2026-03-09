<?php

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\Query;

/**
 * Command to send alerts for student status changes.
 */
class AlertStudentStatusChangeCommand extends AlertCommandBase
{
    protected $entityId = 0;
    protected $student;

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
        $this->Users = $this->fetchTable('User.Users');
        $this->InstitutionStudents = $this->fetchTable('Institution.Institutions');
        if (!$this->prepareContext($args, $io)) {
            return static::CODE_SUCCESS;
        }

        return $this->runFeatureAlert('StudentStatusChange');
    }

    /**
     * Get pending student records to alert on for status changes.
     *
     * @param string $featureKey Feature key
     * @return array List of student entries to alert
     */
    protected function getPendingItems(string $featureKey): array
    {
        $thresholdValue = $this->rule['threshold'] ?? '{}';
        $threshold = json_decode($thresholdValue, true);
        $statusCategory = $threshold['statuses'];
        $where = [
            'InstitutionStudents.id' => $this->entityId,
            'Statuses.id IN' => $statusCategory,
        ];

        $query = $this->Users->find();
        $query->contain(['Statuses'])
            ->where($where);
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
                $student_guardians->aliasField('student_id = ') . 'Users.id'
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
        $query->select([
            'status_name' => 'Statuses.name',
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
            'guardian_name' => "CONCAT(`guardians`.`first_name`, ' ', `guardians`.`last_name`)",
            'guardian_relation' => $guardian_relations->aliasField('name'),
            'guardian_contact' => $guardian_contacts->aliasField('value'),
        ])
        ;

        return $query;
    }

    public function prepareContext(Arguments $args, ConsoleIo $io): bool
    {
        $this->setIo($io);
        $this->userId = (int)$args->getOption('user_id');
        $this->ruleId = (int)$args->getOption('rule_id');
        $this->processId = (int)$args->getOption('process_id');
        $this->entityId = $args->getOption('entity_id');
        $ruleId = $this->ruleId;


        if (!$this->userId ||
            !$this->ruleId ||
            !$this->processId ||
            !$this->entityId
        ) {
            $io->error("Missing required option for student status change command. entity_id (Student User ID) is required.");
            return false;
        }
        try {
            $this->student = $this->Users->get($this->entityId);
            $this->studentId = $this->student->id; // Assign student ID
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $io->error("Student with ID {$this->entityId} not found.");
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
     * Map placeholders for a student status change alert.
     *
     * @param \Cake\Datasource\EntityInterface|array $item
     * @return array<string, string>
     */
    protected function fillPlaceholders($item): array
    {
        return [
            '${student.name}' => $item['student_name'] ?? '',
            '${student.openemis_no}' => $item['student_openemis_no'] ?? '',
            '${student.first_name}' => $item['student_first_name'] ?? '',
            '${student.middle_name}' => $item['student_middle_name'] ?? '',
            '${student.third_name}' => $item['student_third_name'] ?? '',
            '${student.last_name}' => $item['student_last_name'] ?? '',
            '${student.preferred_name}' => $item['student_preferred_name'] ?? '',
            '${student.email}' => $item['student_email'] ?? '',
            '${student.postal_code}' => $item['student_postal_code'] ?? '',
            '${student.date_of_birth}' => $item['student_date_of_birth'] ?? '',
            '${student.status}' => $item['status_name'] ?? '', // Status name from Users.Statuses
            '${guardian.name}' => $item['guardian_name'] ?? '',
            '${guardian.relation}' => $item['guardian_relation'] ?? '',
            '${guardian.contact}' => $item['guardian_contact'] ?? '',
        ];
    }

    public function getOptionParser(): ConsoleOptionParser
    {
        $parser = parent::getOptionParser();

        $parser->addOption('entity_id', [
            'help' => 'Specify the entity ID (Student User ID) for targeted alerts.',
            'required' => true,
            'short' => 'e'
        ]);


        return $parser;
    }
}
