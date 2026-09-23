<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;
use ArrayObject;
use Workflow\Model\Behavior\WorkflowBehavior;
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
        // POCOR-7626: fallback source when a student has no formal institution_student_absences
        // record - the daily Attendance grid writes here instead (see generateCases() below).
        $this->InstitutionStudentAbsenceDetails = $this->fetchTable('institution_student_absence_details');
        $this->CaseTypes = $this->fetchTable('Cases.CaseTypes');
        $this->CasePriorities = $this->fetchTable('Cases.CasePriorities');
        $this->Users = $this->fetchTable('Security.Users');
        $this->Institutions = $this->fetchTable('Institution.Institutions');
        $this->AbsenceTypes = $this->fetchTable('Institution.AbsenceTypes');
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

            $processed += $this->generateCasesFromAttendanceDetails($feature, $absenceTypeId, $daysAbsent, $sinceDate, $workflowRule['id']);
        }

        return $processed;
    }

    // POCOR-7626: fallback pass - institution_student_absences (the formal "Student Absences"
    // screen) is a separate, rarely-used table from the daily Attendance grid staff actually
    // mark absences in day to day (which writes institution_student_absence_details instead, one
    // row per class period). Only runs for a student when they have no formal absence record at
    // all for this type in range, so it never double-counts against the primary pass above.
    // Confirmed with user: any period absent that day counts the whole day; fallback only, does
    // not replace the primary institution_student_absences source.
    private function generateCasesFromAttendanceDetails($feature, $absenceTypeId, $daysAbsent, $sinceDate, $workflowRuleId)
    {
        $processed = 0;
        $dateField = $this->InstitutionStudentAbsenceDetails->aliasField('date');

        // institution_student_absence_details has no single-column id - it's keyed by the
        // composite (student_id, institution_id, academic_period_id, institution_class_id,
        // date, period, subject_id). We aggregate per student anyway, so there's nothing to
        // anchor a record_id to beyond the student themselves.
        $detailCandidates = $this->InstitutionStudentAbsenceDetails->find()
            ->select([
                'student_id' => $this->InstitutionStudentAbsenceDetails->aliasField('student_id'),
                'institution_id' => $this->InstitutionStudentAbsenceDetails->aliasField('institution_id'),
                'academic_period_id' => "MAX({$this->InstitutionStudentAbsenceDetails->aliasField('academic_period_id')})",
                'latest_date' => "MAX({$dateField})",
                'days_absent' => "COUNT(DISTINCT {$dateField})"
            ])
            ->where([
                $this->InstitutionStudentAbsenceDetails->aliasField('absence_type_id') => $absenceTypeId,
                $dateField . ' >=' => $sinceDate
            ])
            ->group([
                $this->InstitutionStudentAbsenceDetails->aliasField('student_id'),
                $this->InstitutionStudentAbsenceDetails->aliasField('institution_id')
            ])
            ->having(['days_absent >=' => $daysAbsent])
            ->enableHydration(false)
            ->all();

        foreach ($detailCandidates as $detail) {
            $hasFormalRecord = $this->InstitutionStudentAbsences->find()
                ->where([
                    $this->InstitutionStudentAbsences->aliasField('student_id') => $detail['student_id'],
                    $this->InstitutionStudentAbsences->aliasField('absence_type_id') => $absenceTypeId,
                    $this->InstitutionStudentAbsences->aliasField('date') . ' >=' => $sinceDate
                ])
                ->count();
            if ($hasFormalRecord > 0) {
                continue;
            }

            // Anchor record_id on -student_id (never positive, so it can never collide with a
            // real institution_student_absences.id used by the primary pass above under the
            // same feature) - this both dedupes future runs and identifies "this student's
            // fallback attendance case" without needing a row id this table doesn't have.
            $recordId = -1 * (int)$detail['student_id'];

            $alreadyLinked = $this->InstitutionCaseRecords->find()
                ->where(['record_id' => $recordId, 'feature' => $feature])
                ->count();
            if ($alreadyLinked > 0) {
                continue;
            }

            if ($this->createCaseFromAttendanceDetail($feature, $detail, $recordId, $absenceTypeId, $workflowRuleId)) {
                $processed++;
            }
        }

        return $processed;
    }

    private function createCaseFromAttendanceDetail($feature, $detail, $recordId, $absenceTypeId, $workflowRuleId)
    {
        $student = $this->Users->get($detail['student_id']);
        $institution = $this->Institutions->get($detail['institution_id']);
        $absenceType = $this->AbsenceTypes->get($absenceTypeId);

        $title = $student->name . ' ' . __('from') . ' ' . $institution->code_name . ' ' . __('with') . ' ' . $absenceType->name;

        $defaultCaseTypeId = $this->CaseTypes->find()->where(['name' => 'Students'])->first();
        $defaultCasePriorityId = $this->CasePriorities->find()->where(['name' => 'Medium'])->first();

        $caseData = [
            'case_number' => '',
            'title' => $title,
            'description' => $title,
            'case_type_id' => $defaultCaseTypeId ? $defaultCaseTypeId->id : null,
            'case_priority_id' => $defaultCasePriorityId ? $defaultCasePriorityId->id : null,
            'status_id' => WorkflowBehavior::STATUS_OPEN,
            'assignee_id' => WorkflowBehavior::AUTO_ASSIGN,
            'institution_id' => $detail['institution_id'],
            'workflow_rule_id' => $workflowRuleId,
            'linked_records' => [[
                'record_id' => $recordId,
                'feature' => $feature
            ]]
        ];

        $newEntity = $this->InstitutionCases->newEntity([]);
        $newEntity = $this->InstitutionCases->patchEntity($newEntity, $caseData, ['validate' => false]);
        $saved = $this->InstitutionCases->save($newEntity);

        if ($saved) {
            // POCOR-7626: without this, the case only ever gets whoever WorkflowCaseBehavior's
            // generic auto-assign happens to match first (e.g. System Administrator) - the
            // primary autoLinkRecordWithCases() path dispatches the rule's configured Rule
            // Events (e.g. "Assign to Principal") to override that with the real intended
            // assignee; this fallback path was missing that step entirely. Handlers like
            // onAssignToPrincipal only read institution_id/student_id/academic_period_id/date
            // off the "linked record" entity, so a synthetic one built from the aggregated
            // detail row works the same as a real InstitutionStudentAbsences entity.
            $syntheticLinkedRecord = new Entity([
                'institution_id' => $detail['institution_id'],
                'student_id' => $detail['student_id'],
                'academic_period_id' => $detail['academic_period_id'],
                'date' => $detail['latest_date']
            ], ['markNew' => false]);

            $workflowRuleEntity = $this->WorkflowRules->get($workflowRuleId, ['contain' => ['WorkflowRuleEvents']]);
            if (!empty($workflowRuleEntity->workflow_rule_events)) {
                $ruleExtra = new ArrayObject(['assigneeFound' => false]);
                foreach ($workflowRuleEntity->workflow_rule_events as $ruleEvent) {
                    $this->InstitutionStudentAbsences->dispatchEvent(
                        $ruleEvent->event_key,
                        [$newEntity, $syntheticLinkedRecord, $ruleExtra],
                        $this->InstitutionStudentAbsences
                    );
                    if ($ruleExtra['assigneeFound']) {
                        break;
                    }
                }
            }
        }

        return (bool)$saved;
    }
}
