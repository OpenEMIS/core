<?php

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\ORM\TableRegistry;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\Query;

// POCOR-9391

/**
 * Command to send alerts for staff leave reminders.
 */
class AlertStudentAbsenceCommand extends AlertCommandBase
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
        $this->StudentAbsencesPeriodDetails = $this->fetchTable('Institution.StudentAbsencesPeriodDetails');
        $this->Students = $this->fetchTable('Institution.Students');
        $this->Institutions = $this->fetchTable('Institution.Institutions');
        //POCOR-9509: removed User.Users override — Security.Users (set in AlertCommandBase::initialize()) has findRecipientList; User.Users does not
//        $io->out('start');
        if (!$this->prepareContext($args, $io)) {
            return static::CODE_SUCCESS;
        }

        return $this->runFeatureAlert('StudentAttendance');
    }

    /**
     * Get pending leave records to alert on.
     *
     * @param string $featureKey Feature key
     * @return array List of leave entries to alert
     */
    protected function getPendingItems(string $featureKey): array
    {
//        $this->io->out(__FUNCTION__);
        $threshold = (int)($this->rule->threshold ?? 1);

        $query = $this->StudentAbsencesPeriodDetails->find()
            ->contain([
                'Users' => ['MainNationalities', 'MainIdentityTypes', 'Genders'],
                'Institutions'
            ])
            ->where([
                'student_id' => $this->studentId,
                'academic_period_id' => $this->academicPeriodId,
                'absence_type_id IN' => [1,2]
            ])
            ->order(['date' => 'ASC'])
            ->disableHydration(); // for performance
//        $this->io->out(__LINE__);

        $absences = $query->toArray();


//        $this->io->out(__LINE__);
        $uniqueDates = [];
        foreach ($absences as $absence) {
            if (!empty($absence['date'])) {
                $stringAbsenceDate = $absence['date']->format('Y-m-d');
//                $this->io->out(print_r([__LINE__ => $stringAbsenceDate], true));
                $uniqueDates[$stringAbsenceDate] = true;
            }
        }
        $total_days = count($uniqueDates);
        if ($total_days < $threshold) {
            return [];
        }
//        $this->io->out(__LINE__);
        $first = $absences[0];
        $answer = [[
            'academic_period_name' => '', // Can load if needed
            'student_name' => $first['user']['first_name'] . ' ' . $first['user']['last_name'],
            'student_openemis_no' => $first['user']['openemis_no'],
            'student_first_name' => $first['user']['first_name'],
            'student_middle_name' => $first['user']['middle_name'],
            'student_third_name' => $first['user']['third_name'],
            'student_last_name' => $first['user']['last_name'],
            'student_preferred_name' => $first['user']['preferred_name'],
            'student_email' => $first['user']['email'],
            'student_address' => $first['user']['address'],
            'student_postal_code' => $first['user']['postal_code'],
            'student_date_of_birth' => $first['user']['date_of_birth'],
            'student_identity_number' => $first['user']['identity_number'],
            'institution_name' => $first['institution']['name'],
            'institution_code' => $first['institution']['code'],
            'institution_address' => $first['institution']['address'],
            'institution_postal_code' => $first['institution']['postal_code'],
            'institution_contact_person' => $first['institution']['contact_person'],
            'institution_telephone' => $first['institution']['telephone'],
            'institution_email' => $first['institution']['email'],
            'institution_website' => $first['institution']['website'],
            'user.gender.name' => $first['user']['gender']['name'] ?? '',
            'user.main_nationality.name' => $first['user']['main_nationality']['name'] ?? '',
            'user.main_identity_type.name' => $first['user']['main_identity_type']['name'] ?? '',
            'total_days' => count($uniqueDates),
            'total_times' => count($absences),
        ]];
//        $this->io->out(print_r([__FUNCTION__ => $answer], true));
        return $answer;
    }

    public function prepareContext(Arguments $args, ConsoleIo $io): bool
    {
        $this->setIo($io);
//        $io->out(__FUNCTION__ . print_r($args, true));
        $this->userId = (int)$args->getOption('user_id');
//        $io->out($this->userId);
        $this->ruleId = (int)$args->getOption('rule_id');
//        $io->out($this->ruleId);
        $this->processId = (int)$args->getOption('process_id');
//        $io->out($this->processId);

        $this->studentId = (int)$args->getOption('student_id');
        $this->institutionId = (int)$args->getOption('institution_id');
        $this->institutionClassId = (int)$args->getOption('institution_class_id');
        $this->academicPeriodId = (int)$args->getOption('academic_period_id');
        $this->period = (int)$args->getOption('period');
        $this->subjectId = (int)$args->getOption('subject_id');

        if (!$this->userId ) {
            $this->userId = 1;
        }
        if (!$this->ruleId ) {
            $io->error("Missing required options (rule_id");
            return false;
        }
        if (!$this->processId ) {
            $io->error("Missing required options (process_id)");
            return false;
        }
        if (!$this->studentId || !$this->academicPeriodId) {
            $io->error("Missing required options (student_id, academic_period_id)");
            return false;
        }

        try {
            $this->rule = $this->AlertRules->get($this->ruleId, ['contain' => ['SecurityRoles']]);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $io->error("Alert rule with ID {$this->ruleId} not found.");
            return false;
        }

        if (empty($this->rule->security_roles)) {
            $io->error("No roles assigned to alert rule ID {$this->ruleId}. Skipping.");
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
        $threshold = (int)($this->rule->threshold ?? 1);
        $answer = [
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
            '${student.gender}' => $item['user.gender.name'] ?? '',
            '${student.identity_number}' => $item['student_identity_number'] ?? '',
            '${student.main_nationality}' => $item['user.main_nationality.name'] ?? '',
            '${institution.name}' => $item['institution_name'] ?? '',
            '${institution.code}' => $item['institution_code'] ?? '',
            '${institution.address}' => $item['institution_address'] ?? '',
            '${institution.postal_code}' => $item['institution_postal_code'] ?? '',
            '${institution.contact_person}' => $item['institution_contact_person'] ?? '',
            '${institution.telephone}' => $item['institution_telephone'] ?? '',
            '${institution.email}' => $item['institution_email'] ?? '',
            '${institution.website}' => $item['institution_website'] ?? '',
            '${total_days}' => $item['total_days'] ?? 0,
            '${total_times}' => $item['total_times'] ?? 0,
            '${threshold}' => $threshold ?? 0,
        ];
//        $this->io->out(print_r([__FUNCTION__ => $answer],true));
        return $answer;
    }



    public function getOptionParser(): ConsoleOptionParser
    {
        $parser = parent::getOptionParser();

        return $parser
            ->addOption('student_id', ['help' => 'Student ID', 'required' => true, 'short' => 's'])
            ->addOption('institution_id', ['help' => 'Institution ID', 'required' => true, 'short' => 'i'])
            ->addOption('institution_class_id', ['help' => 'Institution Class ID', 'required' => true, 'short' => 'c'])
            ->addOption('academic_period_id', ['help' => 'Academic Period ID', 'required' => true, 'short' => 'a'])
            ->addOption('period', ['help' => 'Period ID', 'required' => true, 'short' => 'p'])
            ->addOption('subject_id', ['help' => 'Subject ID', 'required' => true, 'short' => 'j'])
            ->addOption('date', ['help' => 'Date', 'short' => 'd']);
    }


}
