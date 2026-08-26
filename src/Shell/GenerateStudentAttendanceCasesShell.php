<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\I18n\FrozenTime;
use Exception;

class GenerateStudentAttendanceCasesShell extends Shell
{
    const PROCESS_NAME = 'GenerateStudentAttendanceCases';
    const FEATURE = 'StudentAttendances';
    const LOOKBACK_DAYS = 30;

    public function initialize(): void
    {
        parent::initialize();
        $this->SystemProcesses = $this->fetchTable('SystemProcesses');
        $this->WorkflowRules = $this->fetchTable('Workflow.WorkflowRules');
        $this->InstitutionStudentAbsences = $this->fetchTable('Institution.InstitutionStudentAbsences');
        $this->InstitutionCaseRecords = $this->fetchTable('Cases.InstitutionCaseRecords');
        $this->InstitutionCases = $this->fetchTable('Cases.InstitutionCases');
    }

    public function main()
    {
        $mypid = getmypid();

        if (!empty($this->SystemProcesses->getRunningProcesses(self::PROCESS_NAME))) {
            $this->out('A previous run of ' . self::PROCESS_NAME . ' is still marked as running. Skipping this run (' . FrozenTime::now() . ')');
            return;
        }

        $systemProcessId = $this->SystemProcesses->addProcess(self::PROCESS_NAME, $mypid, self::PROCESS_NAME);
        $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING);
        $this->out('Starting ' . self::PROCESS_NAME . ' (' . FrozenTime::now() . ')');

        try {
            $processed = $this->generateCases();
            $this->out('Candidates processed: ' . $processed);
            $this->SystemProcesses->updateProcess($systemProcessId, FrozenTime::now(), $this->SystemProcesses::COMPLETED, $processed);
        } catch (Exception $e) {
            $this->out('Error in ' . self::PROCESS_NAME . ': ' . $e->getMessage());
            $this->SystemProcesses->updateProcess($systemProcessId, FrozenTime::now(), $this->SystemProcesses::ERROR);
        }

        $this->out('End ' . self::PROCESS_NAME . ' (' . FrozenTime::now() . ')');
    }

    // Safety-net batch pass: finds InstitutionStudentAbsences records that already satisfy a
    // configured StudentAttendances WorkflowRule but have no linked Case yet (e.g. the live
    // Institution.CaseBehavior save-hook never fired for them), and routes them through the
    // same case-creation entry point the live hook uses, so the two paths stay consistent.
    public function generateCases()
    {
        $feature = self::FEATURE;
        $processed = 0;

        $workflowRules = $this->WorkflowRules->find()
            ->where(['feature' => $feature])
            ->disableHydration()
            ->all();

        if ($workflowRules->isEmpty()) {
            $this->out('No Workflow Rules configured for ' . $feature);
            return $processed;
        }

        $sinceDate = FrozenTime::now()->subDays(self::LOOKBACK_DAYS)->format('Y-m-d');

        foreach ($workflowRules as $workflowRule) {
            $rule = json_decode($workflowRule['rule'], true);
            if (empty($rule['where']['absence_type_id']) || empty($rule['where']['days_absent'])) {
                continue;
            }
            $absenceTypeId = $rule['where']['absence_type_id'];
            $daysAbsent = (int)$rule['where']['days_absent'];

            $candidates = $this->InstitutionStudentAbsences->find()
                ->select(['id' => $this->InstitutionStudentAbsences->aliasField('id')])
                ->innerJoin(
                    ['InstitutionStudentAbsenceDays' => 'institution_student_absence_days'],
                    ['InstitutionStudentAbsenceDays.id = ' . $this->InstitutionStudentAbsences->aliasField('institution_student_absence_day_id')]
                )
                ->leftJoin(
                    ['ExistingLinks' => 'institution_case_records'],
                    [
                        'ExistingLinks.record_id = ' . $this->InstitutionStudentAbsences->aliasField('id'),
                        'ExistingLinks.feature' => $feature
                    ]
                )
                ->where([
                    $this->InstitutionStudentAbsences->aliasField('absence_type_id') => $absenceTypeId,
                    $this->InstitutionStudentAbsences->aliasField('date') . ' >=' => $sinceDate,
                    'InstitutionStudentAbsenceDays.absent_days >=' => $daysAbsent,
                    'ExistingLinks.institution_case_id IS' => null
                ])
                ->enableHydration(false)
                ->all();

            foreach ($candidates as $candidate) {
                $absenceEntity = $this->InstitutionStudentAbsences->get($candidate['id']);
                $this->InstitutionCases->autoLinkRecordWithCases($absenceEntity);
                $processed++;
            }
        }

        return $processed;
    }
}
