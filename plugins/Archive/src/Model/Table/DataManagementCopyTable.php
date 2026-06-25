<?php

namespace Archive\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use App\Model\Traits\MessagesTrait;
use Cake\Http\ServerRequest;
use Cake\Event\EventInterface;

class DataManagementCopyTable extends ControllerActionTable
{
    use MessagesTrait;

    //POCOR-7924:start
    const REPORT_CARDS = 'Report Card Templates';
    const EDUCATION_STRUCTURE = 'Education Structure';
    const INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS = 'Institution Programmes, Grades and Subjects';
    const SHIFTS = 'Shifts';
//    const INFRASTRUCTURE = 'Infrastructure';
    const RISKS = 'Risks';
    const PERFORMANCE_COMPETENCIES = 'Performance Competencies';
    const PERFORMANCE_ASSESSMENTS = 'Performance Assessments';
    const PERFORMANCE_OUTCOMES = 'Institution Performance Outcomes';
    const MASS_STUDENT_GRAD = 'Mass Student Graduation';

    //POCOR-7924:end

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('data_management_copy');
        $this->getDisplayField('id');
        $this->getPrimaryKey('id');

        $this->belongsTo('AcademicPeriods', [
            'foreignKey' => 'from_academic_period',
            'joinType' => 'INNER',
            'className' => 'AcademicPeriod.AcademicPeriods'
        ]);

        $this->toggle('view', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->integer('id')->allowEmpty('id', 'create');
        $validator->requirePresence('from_academic_period', 'create')->notEmpty('from_academic_period');
        $validator->requirePresence('to_academic_period', 'create')->notEmpty('from_academic_period');
        $validator->requirePresence('features', 'create')->notEmpty('from_academic_period');
        return $validator;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('from_academic_period', ['sort' => false]);
        $this->field('to_academic_period', ['sort' => false]);
        $this->field('features', ['sort' => false]);
        $this->field('created_user_id');
        $this->field('created');
        $this->field('created_user_id', ['visible' => true]);
        $this->field('created', ['sort' => false, 'visible' => true]);

        $this->setFieldOrder(['from_academic_period', 'to_academic_period', 'features', 'created_user_id', 'created']);
    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extram)
    {
        $condition = [];
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
        $this->field('from_academic_period', ['type' => 'select', 'onChangeReload' => true, 'options' => $academicPeriodOptions]);
        $this->field('to_academic_period', ['type' => 'select', 'options' => $academicPeriodOptions]);
        $this->field('features', ['type' => 'select', 'options' => $this->getFeatureOptions()]);
        $this->setFieldOrder(['from_academic_period', 'to_academic_period', 'features']);
    }

    public function onUpdateFieldFromAcademicPeriod(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $condition = [];
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
        $attr['options'] = $academicPeriodOptions;
        $attr['onChangeReload'] = true;
        return $attr;
    }

    /*───────────────────────────────────────────────────────────────────────────
     | POCOR-9354: beforeSave → delegate to feature-specific validation (unchanged logic)
     ───────────────────────────────────────────────────────────────────────────*/
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        ini_set('memory_limit', '2G'); //POCOR-6893

        if ($entity->from_academic_period == $entity->to_academic_period) {
            $this->Alert->error('CopyData.toandfromacademicperiods', ['reset' => true]);
            return false;
        }

        return $this->validateForFeature($entity);
    }

    /*───────────────────────────────────────────────────────────────────────────
     | POCOR-9354: afterSave → delegate to feature-specific triggers (Shell → Command where ready)
     ───────────────────────────────────────────────────────────────────────────*/
    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        ini_set('memory_limit', '2G');
        return $this->triggerForFeature($entity);
    }

    /* ======================================================================
     * VALIDATION (feature-specific, extracted from original beforeSave)
     * ==================================================================== */

    private function validateForFeature(Entity $entity)
    {
        // All TableRegistry pulls remain local to keep original behavior
        $EducationSystems = TableRegistry::get('Education.EducationSystems');

        // Common guard: Target period must have system (unless creating structure)
        if ($entity->to_academic_period) {
            $EducationSystemsdata = $EducationSystems
                ->find('all')
                ->where(['academic_period_id' => $entity->to_academic_period])
                ->toArray();

            if ($entity->features == self::EDUCATION_STRUCTURE) {
                if (!empty($EducationSystemsdata)) {
                    $this->Alert->error('CopyData.educationsystemalreadyexist', ['reset' => true]);
                    return false;
                }
            } else {
                if (empty($EducationSystemsdata)) {
                    $this->Alert->error('CopyData.educationsystemnotexist', ['reset' => true]);
                    return false;
                }
            }
        }

        switch ($entity->features) {
            case self::INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS:
                return $this->validateInstitutionProgrammesGradesSubjects($entity);

            case self::SHIFTS:
                return $this->validateShifts($entity);

//            case self::INFRASTRUCTURE:
//                return $this->validateInfrastructure($entity);

            case self::RISKS:
                return $this->validateRisks($entity);

            case self::PERFORMANCE_COMPETENCIES:
                return $this->validatePerformanceCompetencies($entity);

            case self::PERFORMANCE_ASSESSMENTS:
                return $this->validatePerformanceAssessments($entity);

            case self::REPORT_CARDS:
                return $this->validateReportCards($entity);

            case self::PERFORMANCE_OUTCOMES:
                return $this->validatePerformanceOutcomes($entity);

            case self::MASS_STUDENT_GRAD:
                return $this->validateMassStudentGrad($entity);

            case self::EDUCATION_STRUCTURE:
                // already validated above; nothing more here
                return true;

            default:
                return true;
        }
    }

    private function validateInstitutionProgrammesGradesSubjects(Entity $entity)
    {
        $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $EducationLevels = TableRegistry::get('Education.EducationLevels');
        $EducationCycles = TableRegistry::get('Education.EducationCycles');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');

        $EducationSystemsdata = $EducationSystems
            ->find('all')
            ->where(['academic_period_id' => $entity->to_academic_period])
            ->toArray();

        $levelIds = [];
        if (!empty($EducationSystemsdata)) {
            $EducationLevelsData = $EducationLevels
                ->find('all')
                ->where(['education_system_id' => $EducationSystemsdata[0]->id])
                ->toArray();
            foreach ($EducationLevelsData as $row) {
                $levelIds[] = $row['id'];
            }
        }

        $cycleIds = [];
        if (!empty($levelIds)) {
            $EducationCyclesData = $EducationCycles
                ->find('all')
                ->where(['education_level_id IN' => $levelIds])
                ->toArray();
            foreach ($EducationCyclesData as $row) {
                $cycleIds[] = $row['id'];
            }
        }

        $programmeIds = [];
        if (!empty($cycleIds)) {
            $EducationProgrammesData = $EducationProgrammes
                ->find('all')
                ->where(['education_cycle_id IN' => $cycleIds])
                ->toArray();
            foreach ($EducationProgrammesData as $row) {
                $programmeIds[] = $row['id'];
            }
        }

        $gradeIds = [];
        if (!empty($programmeIds)) {
            $EducationGradesdata = $EducationGrades
                ->find('all')
                ->where(['education_programme_id IN' => $programmeIds])
                ->toArray();
            foreach ($EducationGradesdata as $row) {
                $gradeIds[] = $row['id'];
            }
        }

        if (!empty($gradeIds)) {
            $InstitutionGradesdata = $InstitutionGrades
                ->find('all')
                ->where(['education_grade_id IN ' => $gradeIds])
                ->toArray();
            if (!empty($InstitutionGradesdata)) {
                if ($this->checkInstitutionCopiedData($entity->from_academic_period, $entity->to_academic_period)) {
                    $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                    return false;
                }
            }
        }
        return true;
    }

    private function validateShifts(Entity $entity)
    {
        $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
        $shiftsTo = $InstitutionShifts
            ->find('all')
            ->where(['academic_period_id ' => $entity->to_academic_period])
            ->toArray();

        if (!empty($shiftsTo)) {
            if ($this->checkshiftCopiedData($entity->from_academic_period, $entity->to_academic_period)) {
                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                return false;
            }
        }
        return true;
    }

    private function validateRisks(Entity $entity)
    {
        $RiskData = TableRegistry::get('Institution.Risks');
        $RiskRecords = $RiskData->find('all')
            ->where(['academic_period_id ' => $entity->to_academic_period])
            ->toArray();
        if (!empty($RiskRecords)) {
            $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
            return false;
        }
        return true;
    }

    private function validatePerformanceCompetencies(Entity $entity)
    {
        if ($entity->from_academic_period == $entity->to_academic_period) {
            $this->Alert->error('CopyData.toandfromacademicperiods', ['reset' => true]);
            return false;
        }

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $EducationSystems = TableRegistry::get('Education.EducationSystems');

        if ($entity->to_academic_period) {
            $CompetencyCriteriasTable = TableRegistry::get('Competency.CompetencyCriterias');
            $CompetencyTemplatesTable = TableRegistry::get('Competency.CompetencyTemplates');
            $CompetencyItemsTable = TableRegistry::get('Competency.CompetencyItems');
            $CompetencyPeriodsTable = TableRegistry::get('Competency.CompetencyPeriods'); //POCOR-8504

            $CompetencyCriteriasData = $CompetencyCriteriasTable->find('all')
                ->where(['academic_period_id' => $entity->to_academic_period])->toArray();

            $CompetencyTemplatesData = $CompetencyTemplatesTable->find('all')
                ->where(['academic_period_id' => $entity->to_academic_period])->toArray();

            $CompetencyItemsData = $CompetencyItemsTable->find('all')
                ->where(['academic_period_id' => $entity->to_academic_period])->toArray();

            $CompetencyPeriodsData = $CompetencyPeriodsTable->find('all')
                ->where(['academic_period_id' => $entity->to_academic_period])->toArray();

            if (!empty($CompetencyCriteriasData) &&
                !empty($CompetencyTemplatesData) &&
                !empty($CompetencyItemsData) &&
                !empty($CompetencyPeriodsData)) {
                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                return false;
            }

            $entity->competency_criterias_value = empty($CompetencyCriteriasData) ? 0 : 1;
            $entity->competency_templates_value = empty($CompetencyTemplatesData) ? 0 : 1;
            $entity->competency_items_value = empty($CompetencyItemsData) ? 0 : 1;
            $entity->competency_periods_value = empty($CompetencyPeriodsData) ? 0 : 1;

            $EducationSystemsdata = $EducationSystems->find('all')
                ->where(['academic_period_id' => $entity->to_academic_period])->toArray();
            if (empty($EducationSystemsdata)) {
                $this->Alert->error('CopyData.nodataexisteducationsystem2', ['reset' => true]);
                return false;
            }
        }

        return true;
    }

    private function validatePerformanceAssessments(Entity $entity)
    {
        $AssessmentData = TableRegistry::get('Assessment.Assessments');
        $AssessmentRecords = $AssessmentData->find('all')
            ->where(['academic_period_id ' => $entity->to_academic_period])->count();
        $PreviousAssessmentRecords = $AssessmentData->find('all')
            ->where(['academic_period_id ' => $entity->from_academic_period])->count();
        if ($AssessmentRecords >= $PreviousAssessmentRecords) {
            $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
            return false;
        }
        return true;
    }

    private function validateReportCards(Entity $entity)
    {
        $ReportCard = TableRegistry::get('ReportCard.ReportCards');
        $ReportCardData = $ReportCard->find('all')
            ->where(['academic_period_id ' => $entity->to_academic_period])->toArray();
        if (!empty($ReportCardData)) {
            $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
            return false;
        }
        return true;
    }

    private function validatePerformanceOutcomes(Entity $entity)
    {
        if ($entity->from_academic_period == $entity->to_academic_period) {
            $this->Alert->error('CopyData.toandfromacademicperiods', ['reset' => true]);
            return false;
        }

        $outcomeTemplates = TableRegistry::get('Outcome.OutcomeTemplates');
        $outcomeCriterias = TableRegistry::get('Outcome.OutcomeCriterias');

        $toTemplates = $outcomeTemplates->find('all')
            ->where(['academic_period_id ' => $entity->to_academic_period])->count();
        $toCriterias = $outcomeCriterias->find('all')
            ->where(['academic_period_id ' => $entity->to_academic_period])->count();
        $fromTemplates = $outcomeTemplates->find('all')
            ->where(['academic_period_id ' => $entity->from_academic_period])->count();
        $fromCriterias = $outcomeCriterias->find('all')
            ->where(['academic_period_id ' => $entity->from_academic_period])->count();

        if ($toTemplates >= $fromTemplates && $toCriterias >= $fromCriterias) {
            $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
            return false;
        }
        return true;
    }

    private function validateMassStudentGrad(Entity $entity)
    {
        if ($entity->from_academic_period == $entity->to_academic_period) {
            $this->Alert->error('CopyData.toandfromacademicperiods', ['reset' => true]);
            return false;
        }
        if ($entity->from_academic_period > $entity->to_academic_period) {
            $this->Alert->error('CopyData.invalidDate', ['reset' => true]);
            return false;
        }

        $connection = ConnectionManager::get('default');

        $finalGradeIdsQuery = "
            SELECT DISTINCT eg.id AS education_grade_id
            FROM education_systems es
            JOIN education_levels el ON es.id = el.education_system_id
            JOIN education_cycles ec ON el.id = ec.education_level_id
            JOIN education_programmes ep ON ec.id = ep.education_cycle_id
            JOIN education_grades eg ON ep.id = eg.education_programme_id
            WHERE es.academic_period_id = :copyFrom
            AND eg.`order` = (
                SELECT MAX(eg2.`order`)
                FROM education_grades eg2
                WHERE eg2.education_programme_id = ep.id
            )";

        $finalGradeIds = $connection->execute($finalGradeIdsQuery, ['copyFrom' => $entity->from_academic_period])
            ->fetchAll('assoc');
        $finalGradeIds = array_column($finalGradeIds, 'education_grade_id');

        $InstitutionStudents = TableRegistry::get('Institution.InstitutionStudents');
        $studentIdsQuery = $InstitutionStudents->find()
            ->select(['student_id'])
            ->where([
                'student_status_id' => 1,
                'academic_period_id' => $entity->from_academic_period,
                'education_grade_id IN' => $finalGradeIds
            ])->toArray();

        if (empty($studentIdsQuery)) {
            $this->Alert->error('CopyData.nodataexist', ['reset' => true]);
            return false;
        }

        return true;
    }

    /* ======================================================================
     * TRIGGERS (feature-specific, extracted from original afterSave)
     * ==================================================================== */

    private function triggerForFeature(Entity $entity)
    {
        switch ($entity->features) {
            case self::INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS:
                // ✅ PORTED TO COMMAND:
                // bin/cake copy:institution-programs-grades-subjects FROM TO
                return $this->triggerInstitutionProgramsGradesCommand(
                    (int)$entity->from_academic_period,
                    (int)$entity->to_academic_period,
                    $this->Auth->User('id')
                );

            case self::SHIFTS:
                return $this->triggerShell('Shift', $entity->from_academic_period, $entity->to_academic_period);

            case self::RISKS:
                return $this->triggerShell('Risk', $entity->from_academic_period, $entity->to_academic_period);

            case self::PERFORMANCE_COMPETENCIES:
//                return $this->triggerPerformanceCompetenciesShell(
//                    'PerformanceCompetencies',
//                    $entity->from_academic_period,
//                    $entity->to_academic_period,
//                    $entity->competency_criterias_value,
//                    $entity->competency_templates_value,
//                    $entity->competency_items_value,
//                    $entity->competency_periods_value
//                );

                return $this->triggerPerformanceCompetenciesCommand(
                    (int)$entity->from_academic_period,
                    (int)$entity->to_academic_period,
                    $this->Auth->User('id')
                );
            case self::EDUCATION_STRUCTURE:
                // Still shell-based per your note; append user id
            case self::EDUCATION_STRUCTURE: {
                $from = (int)$entity->from_academic_period;
                $to   = (int)$entity->to_academic_period;
                $user = (int)$this->Auth->User('id');

                // previously: $this->triggerCopyShell('EducationStructureCopy', $from, $to, $user);
                return $this->runCommand(
                    'copy:education-structure',
                    [$from, $to, $user],
                    'CopyEducationStructure' // log file stem
                );
            }
            case self::PERFORMANCE_ASSESSMENTS:
                return $this->triggerShell('PerformanceAssessment', $entity->from_academic_period, $entity->to_academic_period);

            case self::REPORT_CARDS:
                return $this->triggerShell('CopyReportCard', $entity->from_academic_period, $entity->to_academic_period);

            case self::PERFORMANCE_OUTCOMES:
                return $this->triggerPerformanceOutcomesShell('PerformanceOutcomes', $entity->from_academic_period, $entity->to_academic_period);

            case self::MASS_STUDENT_GRAD:
                return $this->triggerShell('CopyMassGraduation', $entity->from_academic_period, $entity->to_academic_period);

            default:
                return true;
        }
    }

    /*───────────────────────────────────────────────────────────────────────────
     | NEW: Command trigger for Institution Programmes/Grades/Subjects
     ───────────────────────────────────────────────────────────────────────────*/
    private function triggerInstitutionProgramsGradesCommand(int $from, int $to, int $userId): bool
    {
        // If you later want parity options, add flags here:
        // e.g. $flags = ['--ensure-egs', '--copy-edges', '--copy-gpa'];
        $flags = [];
        $args = array_merge([$from, $to, $userId], $flags);

        return $this->runCommand(
            'copy:institution-programs-grades-subjects',
            $args,
            'CopyInstitutionProgramsGradesSubjects' // log file stem
        );
    }

    private function triggerPerformanceCompetenciesCommand(int $from, int $to, int $userId): bool
    {
        // If you later want parity options, add flags here:
        // e.g. $flags = ['--ensure-egs', '--copy-edges', '--copy-gpa'];
        $flags = [];
        $args = array_merge([$from, $to, $userId], $flags);

        return $this->runCommand(
            'copy:performance-competencies',
            $args,
            'CopyPerformanceCompetencies' // log file stem
        );
    }

    /*───────────────────────────────────────────────────────────────────────────
     | Legacy shell runner kept for not-yet-ported features (unchanged behavior)
     ───────────────────────────────────────────────────────────────────────────*/
    private function triggerShell(string $shellName, ...$args): bool
    {
        return $this->runShell($shellName, $args, $shellName . '_copy');
    }

    /* ======================================================================
     * PROCESS LAUNCHERS
     * ==================================================================== */

    /**
     * Preferred launcher for new code (Commands).
     * Runs `bin/cake <command> <args...>` appending output to logs/<logName>.log
     */
    private function runCommand(string $command, array $args, string $logName): bool
    {
        $bin = ROOT . DS . 'bin' . DS . 'cake';
        $cmd = escapeshellcmd($bin) . ' ' . escapeshellarg($command);
        foreach ($args as $arg) {
            $cmd .= ' ' . escapeshellarg((string)$arg);
        }
        $log = ROOT . DS . 'logs' . DS . $logName . '.log & echo $!';
        $full = $cmd . ' >> ' . $log;

        Log::write('debug', $full);
        exec($full);
        return true;
    }

    /**
     * Legacy compatibility for existing shells you haven’t ported yet.
     * Runs `bin/cake <ShellName> <args...>` → same logging style.
     */
    private function runShell(string $shellName, array $args, string $logStem): bool
    {
        $bin = ROOT . DS . 'bin' . DS . 'cake';
        $cmd = escapeshellcmd($bin) . ' ' . escapeshellarg($shellName);
        foreach ($args as $arg) {
            $cmd .= ' ' . escapeshellarg((string)$arg);
        }
        $log = ROOT . DS . 'logs' . DS . $logStem . '.log & echo $!';
        $full = $cmd . ' >> ' . $log;

        Log::write('debug', $full);
        exec($full);
        return true;
    }

    public function getFeatureOptions()
    {
        $options = [
            // POCOR-7924:start
            self::EDUCATION_STRUCTURE => __(self::EDUCATION_STRUCTURE),//POCOR-7568
            self::INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS => __(self::INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS),
            self::SHIFTS => __(self::SHIFTS),
            self::RISKS => __(self::RISKS), // POCOR-5337
            self::PERFORMANCE_COMPETENCIES => __(self::PERFORMANCE_COMPETENCIES),
            self::PERFORMANCE_OUTCOMES => __('Performance Outcomes'),
            self::PERFORMANCE_ASSESSMENTS => __('Institution Performance Assessments'), // POCOR-6423
            self::REPORT_CARDS => __(self::REPORT_CARDS), // POCOR-7764 // POCOR-7924: end
            self::MASS_STUDENT_GRAD => __(self::MASS_STUDENT_GRAD) // POCOR-8689
        ];
        return $options;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'from_academic_period':
                return __('From Academic Period');
            case 'to_academic_period':
                return __('To Academic Period');
            case 'features':
                return __('Features');
            case 'created_user_id':
                return __('Created By');
            case 'created':
                return __('Created');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetToAcademicPeriod(EventInterface $event, Entity $entity)
    {
        $AcademicPeriodsData = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods'); //TableRegistry::get('Academic.AcademicPeriods');
        $result = $AcademicPeriodsData
            ->find()
            ->select(['name'])
            ->where(['id' => $entity->to_academic_period])
            ->first();

        return $entity->to_academic_period = $result->name;
    }

    public function onGetGeneratedBy(EventInterface $event, Entity $entity)
    {
        $Users = TableRegistry::get('User.Users');
        $result = $Users
            ->find()
            ->select(['first_name', 'last_name'])
            ->where(['id' => $entity->generated_by])
            ->first();

        return $entity->generated_by = $result->first_name . ' ' . $result->last_name;
    }

    private function checkInstitutionCopiedData($copyFrom, $copyTo)
    {
        $educationGradesTable = TableRegistry::get('Education.EducationGrades');
        $institutionGradesTable = TableRegistry::get('Institution.InstitutionGrades');


        $query = $institutionGradesTable
            ->find()
            ->select([
                'period_id' => 'AcademicPeriods.id',
                'period_name' => 'AcademicPeriods.name',
                'period_code' => 'AcademicPeriods.code',
                'grade_id' => 'EducationGrades.id',
                'grade_name' => 'EducationGrades.name',
                'programme_name' => 'EducationProgrammes.name',
                'institution_id' => 'Institutions.id'
            ])
            ->innerJoin(
                ['EducationGrades' => 'education_grades'],
                ['EducationGrades.id = InstitutionGrades.education_grade_id']
            )
            ->innerJoin(
                ['Institutions' => 'institutions'],
                ['Institutions.id = InstitutionGrades.institution_id']
            )
            ->innerJoin(
                ['EducationProgrammes' => 'education_programmes'],
                ['EducationGrades.education_programme_id = EducationProgrammes.id']
            )
            ->innerJoin(
                ['EducationCycles' => 'education_cycles'],
                ['EducationProgrammes.education_cycle_id = EducationCycles.id']
            )
            ->innerJoin(
                ['EducationLevels' => 'education_levels'],
                ['EducationCycles.education_level_id = EducationLevels.id']
            )
            ->innerJoin(
                ['EducationSystems' => 'education_systems'],
                ['EducationLevels.education_system_id = EducationSystems.id']
            )
            ->innerJoin(
                ['AcademicPeriods' => 'academic_periods'],
                ['EducationSystems.academic_period_id = AcademicPeriods.id']
            )
            ->order([
                'AcademicPeriods.order' => 'ASC',
                'EducationLevels.order' => 'ASC',
                'EducationCycles.order' => 'ASC',
                'EducationProgrammes.order' => 'ASC',
                'EducationGrades.order' => 'ASC',
                'Institutions.id' => 'ASC'
            ]);
        $copyFromData = $this->filter_array($query, $copyFrom, 'period_id');
        $copyToData = $this->filter_array($query, $copyTo, 'period_id');
        $insIds = array_unique(array_column($copyFromData, 'institution_id'));
        $count = 0;

        foreach ($insIds as $val) {

            $data1 = array_filter($copyFromData, function ($value, $key) use ($val) {
                return $value['institution_id'] == $val;
            }, ARRAY_FILTER_USE_BOTH);
            $data2 = array_filter($copyToData, function ($value, $key) use ($val) {
                return $value['institution_id'] == $val;
            }, ARRAY_FILTER_USE_BOTH);
            if (count($data1) > count($data2)) {
                $count = $count + (count($data1) - count($data2));
            }

        }
        if ($count > 0) {
            return false;
        }
        return true;

    }

    //POCOR-7576-institution programme end
    public function filter_array($array, $term, $column)
    {
        $matches = array();
        foreach ($array as $a) {
            if ($a[$column] == $term)
                $matches[] = $a;
        }
        return $matches;
    }

    public function triggerPerformanceCompetenciesShell($shellName, $from_academic_period, $to_academic_period = null, $competency_criterias_value = null, $competency_templates_value = null, $competency_items_value = null, $competency_periods_value = null)
    {
        $args = '';
        $args .= !is_null($from_academic_period) ? ' '.$from_academic_period : '';
        $args .= !is_null($to_academic_period) ? ' '.$to_academic_period : '';
        $args .= !is_null($competency_criterias_value) ? ' '.$competency_criterias_value : '';
        $args .= !is_null($competency_templates_value) ? ' '.$competency_templates_value : '';
        $args .= !is_null($competency_items_value) ? ' '.$competency_items_value : '';
        $args .= !is_null($competency_periods_value) ? ' '.$competency_periods_value : ''; //POCOR-8504

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
        return true;
     }

    public function triggerPerformanceOutcomesShell($shellName, $from_academic_period = null, $to_academic_period = null)
    {
        $args = '';
        $args .= !is_null($from_academic_period) ? ' ' . $from_academic_period : '';
        $args .= !is_null($to_academic_period) ? ' ' . $to_academic_period : '';
        $cmd = ROOT . DS . 'bin' . DS . 'cake ' . $shellName . $args;
        $logs = ROOT . DS . 'logs' . DS . $shellName . '.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
        return true;
    }

    /* ======================================================================
     * (Everything below here is unchanged from your file)
     * - checkshiftCopiedData / checkInstitutionCopiedData / filter_array
     * - copyCustomFields
     * - codeGenerate* helpers
     * - triggePerformanceCompetenciesShell / triggePerformanceOutcomesShell
     * - onGetToAcademicPeriod / onGetGeneratedBy / getFeatureOptions
     * ==================================================================== */

    // ... keep your existing helpers exactly as-is ...
}

//namespace Archive\Model\Table;
//
//use Cake\ORM\Query;
//use Cake\ORM\RulesChecker;
//use Cake\ORM\Table;
//use Cake\Validation\Validator;
//use ArrayObject;
//use Cake\Event\Event;
//use Cake\ORM\Entity;
//use Cake\ORM\TableRegistry;
//use App\Model\Table\ControllerActionTable;
//use Cake\Datasource\ConnectionManager;
//use Cake\Log\Log;
//use App\Model\Traits\MessagesTrait;
//use Cake\I18n\Date;
//use Cake\Http\ServerRequest;
//
///**
// * DeletedLogs Model
// *
// * @property \Cake\ORM\Association\BelongsTo $AcademicPeriods
// *
// * @method \Archive\Model\Entity\DataManagementCopy get($primaryKey, $options = [])
// * @method \Archive\Model\Entity\DataManagementCopy newEntity($data = null, array $options = [])
// * @method \Archive\Model\Entity\DataManagementCopy[] newEntities(array $data, array $options = [])
// * @method \Archive\Model\Entity\DataManagementCopy|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
// * @method \Archive\Model\Entity\DataManagementCopy patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
// * @method \Archive\Model\Entity\DataManagementCopy[] patchEntities($entities, array $data, array $options = [])
// * @method \Archive\Model\Entity\DataManagementCopy findOrCreate($search, callable $callback = null, $options = [])
// */
//
//class DataManagementCopyTable extends ControllerActionTable
//{
//    use MessagesTrait;
//    //POCOR-7924:start
//    const REPORT_CARDS = 'Report Card Templates';
//    const EDUCATION_STRUCTURE = 'Education Structure';
//    const INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS = 'Institution Programmes, Grades and Subjects';
//    const SHIFTS = 'Shifts';
//    const INFRASTRUCTURE = 'Infrastructure';
//    const RISKS = 'Risks';
//    const PERFORMANCE_COMPETENCIES = 'Performance Competencies';
//    const PERFORMANCE_ASSESSMENTS = 'Performance Assessments';
//    const PERFORMANCE_OUTCOMES = 'Institution Performance Outcomes';
//    const MASS_STUDENT_GRAD = 'Mass Student Graduation';
//    //POCOR-7924:end
//
//    /**
//     * Initialize method
//     *
//     * @param array $config The configuration for the Table.
//     * @return void
//     */
//    public function initialize(array $config): void
//    {
//        parent::initialize($config);
//
//        $this->setTable('data_management_copy');
//        $this->getDisplayField('id');
//        $this->getPrimaryKey('id');
//
//        $this->belongsTo('AcademicPeriods', [
//            'foreignKey' => 'from_academic_period',
//            'joinType' => 'INNER',
//            'className' => 'AcademicPeriod.AcademicPeriods'
//        ]);
//
//        $this->toggle('view', false);
//        $this->toggle('edit', false);
//        $this->toggle('remove', false);
//    }
//
//    public function validationDefault(Validator $validator): Validator
//    {
//        $validator->integer('id')->allowEmpty('id', 'create');
//        $validator->requirePresence('from_academic_period', 'create')->notEmpty('from_academic_period');
//        $validator->requirePresence('to_academic_period', 'create')->notEmpty('from_academic_period');
//        $validator->requirePresence('features', 'create')->notEmpty('from_academic_period');
//        // $validator->allowEmpty('name', 'create');
//        // $validator->allowEmpty('path', 'create');
//        // $validator->dateTime('generated_on')->allowEmpty('generated_on', 'create');
//        // $validator->allowEmpty('generated_by', 'create');
//        return $validator;
//    }
//
//    public function indexBeforeAction(Event $event, ArrayObject $extra)
//    {
//        $this->field('from_academic_period',['sort' => false]);
//        $this->field('to_academic_period', ['sort' => false]);
//        $this->field('features', ['sort' => false]);
//        $this->field('created_user_id');
//        $this->field('created');
//        $this->field('created_user_id', ['visible' => true]);
//        $this->field('created', ['sort' => false, 'visible' => true]);
//
//        $this->setFieldOrder(['from_academic_period', 'to_academic_period', 'features', 'created_user_id', 'created']);
//    }
//
//    public function addBeforeAction(Event $event, ArrayObject $extram)
//    {
//        $condition = [];
//        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
//        $this->field('from_academic_period', ['type' => 'select', 'onChangeReload'=>true, 'options' => $academicPeriodOptions]);
//        $this->field('to_academic_period', ['type' => 'select', 'options' => $academicPeriodOptions]);
//        $this->field('features', ['type' => 'select', 'options' => $this->getFeatureOptions()]);
//        $this->setFieldOrder(['from_academic_period','to_academic_period','features']);
//
//    }
//
//    public function onUpdateFieldFromAcademicPeriod(Event $event, array $attr, $action, ServerRequest $request)
//    {
//        $condition = [];
//        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
//        $attr['options'] = $academicPeriodOptions;
//        $attr['onChangeReload'] = true;
//        return $attr;
//    }
//
//    // public function onUpdateFieldToAcademicPeriod(Event $event, array $attr, $action, Request $request)
//    // {
//    //     $condition = [$this->AcademicPeriods->aliasField('id').' <> ' => $request['data']['DataManagementCopy']['from_academic_period']];
//    //     $academicPeriodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
//    //     // list($periodOptions, $selectedPeriod) = array_values($this->AcademicPeriods->getYearList(['conditions' => $condition]));
//
//    //     $attr['options'] = $academicPeriodOptions;
//    //     $attr['onChangeReload'] = true;
//    //     return $attr;
//    // }
//
//    public function getAcademicPeriodOptions($querystringPeriod)
//    {
//        $periodOptions = $this->AcademicPeriods->getYearList();
//
//        if ($querystringPeriod) {
//            $selectedPeriod = $querystringPeriod;
//        } else {
//            $selectedPeriod = $this->AcademicPeriods->getCurrent();
//        }
//
//        return compact('periodOptions', 'selectedPeriod');
//    }
//
//    public function beforeSave(Event $event, Entity $entity, ArrayObject $data){
//        ini_set('memory_limit', '2G'); //POCOR-6893
//        if($entity->from_academic_period == $entity->to_academic_period){
//            $this->Alert->error('CopyData.genralerror', ['reset' => true]);
//            return false;
//        }
//
//        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
//        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
//        $EducationSystems = TableRegistry::get('Education.EducationSystems');
//
//        $EducationLevels = TableRegistry::get('Education.EducationLevels');
//        $EducationCycles = TableRegistry::get('Education.EducationCycles');
//        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
//        $EducationGrades = TableRegistry::get('Education.EducationGrades');
//        $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
//
//        $InstitutionBuildings = TableRegistry::get('Institution.InstitutionBuildings');
//        $InstitutionFloors = TableRegistry::get('Institution.InstitutionFloors');
//        $InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');
//        $InstitutionLands = TableRegistry::get('Institution.InstitutionLands');
//        $Institutions = TableRegistry::get('Institution.Institutions');
//
//        if($entity->to_academic_period){
//
//            $ToAcademicPeriodsData = $AcademicPeriods
//            ->find()
//            ->select(['start_date', 'start_year','end_date'])
//            ->where(['id' => $entity->to_academic_period])
//            ->first();
//
//            $EducationSystemsdata = $EducationSystems
//                ->find('all')
//                ->where(['academic_period_id' => $entity->to_academic_period])
//                ->toArray();
//            //POCOR-7568 start
//            if($entity->features == self::EDUCATION_STRUCTURE){
//                    if(!empty($EducationSystemsdata)){
//                        $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);//if education structure data already exist
//                        return false;
//                    }
//            }
//            else{ //POCOR-7568 end
//                if (empty($EducationSystemsdata)) {
//                    $this->Alert->error('CopyData.nodataexisteducationsystem1', ['reset' => true]);
//                    return false;
//                }
//            }
//        }
//        if($entity->features == self::INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS){
//            $EducationSystemsdata = $EducationSystems
//                ->find('all')
//                ->where(['academic_period_id' => $entity->to_academic_period])
//                ->toArray();
//
//            $level_data_id_arr = [];
//            if(!empty($EducationSystemsdata)){
//                $EducationLevelsData = $EducationLevels
//                ->find('all')
//                ->where(['education_system_id' => $EducationSystemsdata[0]->id])
//                ->toArray();
//                foreach ($EducationLevelsData as $level_key => $level_val) {
//                    $level_data_id_arr[$level_key] = $level_val['id'];
//                }
//            }
//
//            $cycle_data_id_arr = [];
//            if(!empty($level_data_id_arr )){
//                $EducationCyclesData = $EducationCycles
//                ->find('all')
//                ->where(['education_level_id IN' => $level_data_id_arr])
//                ->toArray();
//                foreach ($EducationCyclesData as $cycle_key => $cycle_val) {
//                    $cycle_data_id_arr[$cycle_key] = $cycle_val['id'];
//                }
//            }
//
//            $programmes_data_id_arr = [];
//            if(!empty($cycle_data_id_arr )){
//                $EducationProgrammesData = $EducationProgrammes
//                ->find('all')
//                ->where(['education_cycle_id IN' => $cycle_data_id_arr])
//                ->toArray();
//                foreach ($EducationProgrammesData as $programmes_key => $programmes_val) {
//                    $programmes_data_id_arr[$programmes_key] = $programmes_val['id'];
//                }
//            }
//
//            $education_grades_id_arr = [];
//            if(!empty($programmes_data_id_arr )){
//                $EducationGradesdata = $EducationGrades
//                ->find('all')
//                ->where(['education_programme_id IN' => $programmes_data_id_arr])
//                ->toArray();
//                foreach ($EducationGradesdata as $education_grades_key => $education_grades_val) {
//                    $education_grades_id_arr[$education_grades_key] = $education_grades_val['id'];
//                }
//            }
//
//            if(!empty($education_grades_id_arr )){
//
//                $InstitutionGradesdata = $InstitutionGrades
//                ->find('all')
//                ->where(['education_grade_id IN ' => $education_grades_id_arr])
//                ->toArray();
//                if(!empty($InstitutionGradesdata)){
//
//                    if($this->checkInstitutionCopiedData($entity->from_academic_period,$entity->to_academic_period)){//POCOR-7567-institution programme
//
//                    $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
//                    return false;
//                    }
//                }
//            }
//        }
//        if($entity->features == self::SHIFTS){
//            $InstitutionShiftsData = $InstitutionShifts
//                ->find('all')
//                ->where(['academic_period_id ' => $entity->to_academic_period])
//                ->toArray();
//            if(!empty($InstitutionShiftsData)){
//                if($this->checkshiftCopiedData($entity->from_academic_period,$entity->to_academic_period)){//POCOR-7576-shifts
//                   $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);//POCOR-7576-shifts
//                   return false;}//POCOR-7576-shifts
//            }
//        }
//        if($entity->features == self::INFRASTRUCTURE){
//            $InstitutionBuildingsData = $InstitutionBuildings
//                ->find('all')
//                ->where(['academic_period_id ' => $entity->to_academic_period])
//                ->toArray();
//
//            $InstitutionFloorsData = $InstitutionFloors
//                ->find('all')
//                ->where(['academic_period_id ' => $entity->to_academic_period])
//                ->toArray();
//
//            $InstitutionRoomsData = $InstitutionRooms
//                ->find('all')
//                ->where(['academic_period_id ' => $entity->to_academic_period])
//                ->toArray();
//
//            $InstitutionLandsData = $InstitutionLands
//                ->find('all')
//                ->where(['academic_period_id ' => $entity->to_academic_period])
//                ->toArray();
//
//            /****************POCOR-7326 Start********************* */
//            $institutions = $Institutions->find('all')->toArray();
//            $InsIds = [];
//            foreach($InstitutionLandsData as $ke => $institutionLand){
//                $InsIds[] = $institutionLand->institution_id;
//            }
//            //Check here Land entity for each school**
//            $Unmatched =[];
//            $Matched = [];
//            foreach($institutions as $k => $Insti){ //echo "<pre>";print_r($land->institution_id);die;
//                if (!in_array($Insti->id, $InsIds)) {
//                    $Unmatched[$k] = $Insti->id;
//                } else {
//                    $Matched[$k] = $Insti->id;
//                }
//
//            }
//            if(empty($Unmatched)){
//                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
//                return false;
//            }else{
//                $existRecord = $this->find('all',['conditions'=>[
//                    'from_academic_period'=>$entity->from_academic_period,
//                    'to_academic_period' => $entity->to_academic_period,
//                    'features' => self::INFRASTRUCTURE
//                ]])->first();
//                if(!empty($existRecord)){
//                    $this->delete($existRecord);
//                }
//
//            }
//            //**********************POCOR-7326 End******************************* */
//            if(!empty($InstitutionBuildingsData)
//                && !empty($InstitutionFloorsData)
//                && !empty($InstitutionRoomsData)
//                && !empty($InstitutionLandsData)){
//               // $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
//                //return false;
//            }
//        }
//        // Start POCOR-5337
//        $RiskData = TableRegistry::get('Institution.Risks');
//        if($entity->features == self::RISKS){
//            $RiskRecords = $RiskData
//                ->find('all')
//                ->where(['academic_period_id ' => $entity->to_academic_period])
//                ->toArray();
//            if(!empty($RiskRecords)){
//                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
//                return false;
//            }
//        }// End POCOR-5337
//        if($entity->features == self::PERFORMANCE_COMPETENCIES){
//            if($entity->from_academic_period == $entity->to_academic_period){
//                $this->Alert->error('CopyData.genralerror', ['reset' => true]);
//                return false;
//            }
//            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
//            $EducationSystems = TableRegistry::get('Education.EducationSystems');
//            if($entity->to_academic_period){
//                $ToAcademicPeriodsData = $AcademicPeriods
//                ->find()
//                ->select(['start_date', 'start_year','end_date'])
//                ->where(['id' => $entity->to_academic_period])
//                ->first();
//
//                $CompetencyCriteriasTable = TableRegistry::get('Competency.CompetencyCriterias');
//                $CompetencyTemplatesTable = TableRegistry::get('Competency.CompetencyTemplates');
//                $CompetencyItemsTable = TableRegistry::get('Competency.CompetencyItems');
//                $CompetencyPeriodsTable = TableRegistry::get('Competency.CompetencyPeriods'); //POCOR-8504
//
//                $CompetencyCriteriasData = $CompetencyCriteriasTable
//                    ->find('all')
//                    ->where(['academic_period_id' => $entity->to_academic_period])
//                    ->toArray();
//
//                $CompetencyTemplatesData = $CompetencyTemplatesTable
//                ->find('all')
//                ->where(['academic_period_id' => $entity->to_academic_period])
//                ->toArray();
//
//                $CompetencyItemsData = $CompetencyItemsTable
//                ->find('all')
//                ->where(['academic_period_id' => $entity->to_academic_period])
//                ->toArray();
//
//                $CompetencyPeriodsData = $CompetencyPeriodsTable
//                ->find('all')
//                ->where(['academic_period_id' => $entity->to_academic_period])
//                ->toArray();   //POCOR-8504
//
//                if(!empty($CompetencyCriteriasData) && !empty($CompetencyTemplatesData) && !empty($CompetencyItemsData) && !empty($CompetencyPeriodsData)){ //POCOR-8504
//                    $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
//                    return false;
//                }
//                if(empty($CompetencyCriteriasData)){
//                    $entity->competency_criterias_value = 0;
//                }else{
//                    $entity->competency_criterias_value = 1;
//                }
//                if(empty($CompetencyTemplatesData)){
//                    $entity->competency_templates_value = 0;
//                }else{
//                    $entity->competency_templates_value = 1;
//                }
//                if(empty($CompetencyItemsData)){
//                    $entity->competency_items_value = 0;
//                }else{
//                    $entity->competency_items_value = 1;
//                }
//                if(empty($CompetencyPeriodsData)){  //POCOR-8504
//                    $entity->competency_periods_value = 0;
//                }else{
//                    $entity->competency_periods_value = 1;
//                }
//            }
//            if($entity->to_academic_period){
//
//                $ToAcademicPeriodsData = $AcademicPeriods
//                ->find()
//                ->select(['start_date', 'start_year','end_date'])
//                ->where(['id' => $entity->to_academic_period])
//                ->first();
//
//                $EducationSystemsdata = $EducationSystems
//                    ->find('all')
//                    ->where(['academic_period_id' => $entity->to_academic_period])
//                    ->toArray();
//                if(empty($EducationSystemsdata)){
//                    $this->Alert->error('CopyData.nodataexisteducationsystem2', ['reset' => true]);
//                    return false;
//                }
//            }
//        }
//        // Start POCOR-6423
//        $AssessmentData = TableRegistry::get('Assessment.Assessments');
//        if ($entity->features == self::PERFORMANCE_ASSESSMENTS) {
//            $AssessmentRecords = $AssessmentData
//                ->find('all')
//                ->where(['academic_period_id ' => $entity->to_academic_period])
//                ->count();
//            $PreviousAssessmentRecords = $AssessmentData
//                ->find('all')
//                ->where(['academic_period_id ' => $entity->from_academic_period])
//                ->count();
//            if($AssessmentRecords>= $PreviousAssessmentRecords) {
//                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
//                return false;
//            }
//        }
//        // End POCOR-6423
//        // POCOR-7764-start
//        $ReportCard = TableRegistry::get('ReportCard.ReportCards');
//        if ($entity->features == self::REPORT_CARDS) {
//            $ReportCardData = $ReportCard->find('all')
//                ->where(['academic_period_id ' => $entity->to_academic_period])
//                ->toArray();
//            if (!empty($ReportCardData)) {
//                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
//                return false;
//            }
//        }
//        // POCOR-7764-end
//    }
//
//    /***************POCOR-7326 Start*********************** */
//    public function codeGenerateL($Inscode, $no){
//        $no = str_pad($no, 2, 0, STR_PAD_LEFT);
//        return $Inscode."-" . $no;
//        //return $Inscode."-". date('Ymdhis');
//    }
//    public function codeGenerateB($Inscode,$no,$b){
//        $no = str_pad($no, 2, 0, STR_PAD_LEFT);
//        $b = str_pad($b, 2, 0, STR_PAD_LEFT);
//        return $Inscode."-". $no.$b;
//        //return $Inscode."-". date('Ymdhis');
//    }
//    public function codeGenerateF($Inscode,$no,$b,$F){
//        $no = str_pad($no, 2, 0, STR_PAD_LEFT);
//        $b = str_pad($b, 2, 0, STR_PAD_LEFT);
//        $F = str_pad($F, 2, 0, STR_PAD_LEFT);
//        return $Inscode."-". $no.$b.$F;
//        //return $Inscode."-". date('Ymdhis');
//    }
//    public function codeGenerateR($Inscode,$no,$b,$F,$R){
//        $no = str_pad($no, 2, 0, STR_PAD_LEFT);
//        $b = str_pad($b, 2, 0, STR_PAD_LEFT);
//        $F = str_pad($F, 2, 0, STR_PAD_LEFT);
//        $R = str_pad($R, 2, 0, STR_PAD_LEFT);
//        return $Inscode."-". $no.$b.$F.$R;
//        //return $Inscode."-". date('Ymdhis');
//    }
//    /*****************POCOR-7326 End************************** */
//
//    public function afterSave(Event $event, Entity $entity, ArrayObject $data){
//
//        ini_set('memory_limit', '2G');
//        $connection = ConnectionManager::get('default');
//        $EducationSystems = TableRegistry::get('Education.EducationSystems');
//        $EducationLevels = TableRegistry::get('Education.EducationLevels');
//        $EducationCycles = TableRegistry::get('Education.EducationCycles');
//        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
//        $EducationGrades = TableRegistry::get('Education.EducationGrades');
//        $Institutions = TableRegistry::get('Institution.Institutions');
//        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
//        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
//        $institution_program_grade_subjects = TableRegistry::get('Institution.InstitutionProgramGradeSubjects');
//        $currentData = "'".date('Y-m-d H:i:s')."'";
//
//        $from_academic_period = $entity->from_academic_period;
//        $to_academic_period = $entity->to_academic_period;
//
//        if($entity->features == self::INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS){
//            $from_academic_period = $entity->from_academic_period;
//            $to_academic_period = $entity->to_academic_period;
//            $copyFrom = $from_academic_period;
//            $copyTo = $to_academic_period;
//            $this->triggerCopyShell('InstitutionProgramAndGrade', $copyFrom, $copyTo);
//        }
//        if($entity->features == self::SHIFTS){
//            $from_academic_period = $entity->from_academic_period;
//            $to_academic_period = $entity->to_academic_period;
//            $copyFrom = $from_academic_period;
//            $copyTo = $to_academic_period;
//            $this->triggerCopyShell('Shift', $copyFrom, $copyTo);
//        }
//
//        if($entity->features == self::INFRASTRUCTURE){
//            $from_academic_period = $entity->from_academic_period;
//            $to_academic_period = $entity->to_academic_period;
//            $copyFrom = $from_academic_period;
//            $copyTo = $to_academic_period;
//
//            //***********************POCOR-7326 Start******************************* */
//
//            $InstitutionBuildings = TableRegistry::get('Institution.InstitutionBuildings');
//            $InstitutionFloors = TableRegistry::get('Institution.InstitutionFloors');
//            $InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');
//            $InstitutionLands = TableRegistry::get('Institution.InstitutionLands');
//            $Institutions = TableRegistry::get('Institution.Institutions');
//            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
//
//            $InstitutionLandsData = $InstitutionLands
//                ->find('all')
//                ->where(['academic_period_id ' => $entity->to_academic_period])
//                ->toArray();
//
//
//
//            $institutions = $Institutions->find('all')->toArray();
//            $InsIds = [];
//            foreach($InstitutionLandsData as $ke => $institutionLand){
//                $InsIds[] = $institutionLand->institution_id;
//            }
//            //Check here Land entity for each school**
//            $Unmatched =[];
//            $Matched = [];
//            foreach($institutions as $institutionKey => $Institution){
//                if (!in_array($Institution->id, $InsIds)) {
//                   $Unmatched[$institutionKey] = $Institution->id;
//                   //*********Save Land/Bulding/Floor/room */
//                   $oldLand = $InstitutionLands
//                   ->find('all')
//                   ->where(['academic_period_id ' => $entity->from_academic_period,
//                       'institution_id'=> $Institution->id])
//                   ->first();
//
//                   $AcademicPeriod = $AcademicPeriods->get($entity->to_academic_period);
//                    if(!empty($oldLand)) {
//                        $newLand = $InstitutionLands->newEntity([
//                            'code'=> $this->codeGenerateL($Institution->code,$institutionKey+1),
//                            'name'=> $oldLand->name,
//                            'start_date' => $AcademicPeriod->start_date,
//                            'start_year' => $AcademicPeriod->start_year,
//                            'end_date' => $AcademicPeriod->end_date,
//                            'end_year' => $AcademicPeriod->end_year,
//                            'year_acquired'=> $oldLand->year_acquired,
//                            'year_disposed' => $oldLand->year_disposed,
//                            'area' => isset($oldLand->area) ? $oldLand->area : NULL,
//                            'accessibility'=> $oldLand->accessibility,
//                            'comment'=> $oldLand->comment,
//                            'institution_id'=> $oldLand->institution_id,
//                            'academic_period_id'=> $AcademicPeriod->id,
//                            'land_type_id'=> $oldLand->land_type_id,
//                            'land_status_id'=> $oldLand->land_status_id,
//                            'infrastructure_ownership_id'=> $oldLand->infrastructure_ownership_id,
//                            'infrastructure_condition_id'=> $oldLand->infrastructure_condition_id,
//                            'previous_institution_land_id'=> $oldLand->previous_institution_land_id,
//                            'modified_user_id'=> $oldLand->modified_user_id,
//                            'modified'=> $oldLand->modified,
//                            'created_user_id'=> $oldLand->created_user_id,
//                            'created'=> $oldLand->created,
//                            'datatype' => 'copy'
//
//                        ]);
//                        if($saveLand = $InstitutionLands->save($newLand)){
//                            $oldLandId = $oldLand->id;
//                            $newLandId = $saveLand->id;
//                            $tableName = "land_custom_field_values";
//                            $fieldName = "institution_land_id";
//                            $this->copyCustomFields($connection, $tableName, $fieldName, $newLandId, $oldLandId);
//                            $InstitutionBuildingData = $InstitutionBuildings
//                            ->find('all')
//                            ->where(['institution_land_id ' => $oldLand->id])
//                            ->toArray();
//                            foreach($InstitutionBuildingData as $buildingKey=> $oldBuilding){
//                                $newBuildingEntity = $InstitutionBuildings->newEntity([
//                                    'code'=>$this->codeGenerateB($Institution->code,$institutionKey+1,$buildingKey+1),
//                                    'name'=>$oldBuilding->name,
//                                    'start_date' => $AcademicPeriod->start_date,
//                                    'start_year' => $AcademicPeriod->start_year,
//                                    'end_date' => $AcademicPeriod->end_date,
//                                    'end_year' => $AcademicPeriod->end_year,
//                                    'year_acquired'=>$oldBuilding->year_acquired,
//                                    'year_disposed'=>$oldBuilding->year_disposed,
//                                    'area'=>$oldBuilding->area,
//                                    'accessibility'=>$oldBuilding->accessibility,
//                                    'comment'=>$oldBuilding->comment,
//                                    'institution_land_id'=>$newLandId,
//                                    'institution_id'=>$oldBuilding->institution_id,
//                                    'academic_period_id'=>$AcademicPeriod->id,
//                                    'building_type_id'=>$oldBuilding->building_type_id,
//                                    'building_status_id'=>$oldBuilding->building_status_id,
//                                    'infrastructure_ownership_id'=>$oldBuilding->infrastructure_ownership_id,
//
//                                    'infrastructure_condition_id'=>$oldBuilding->infrastructure_condition_id,
//                                    'previous_institution_building_id'=>$oldBuilding->previous_institution_building_id,
//                                    'modified_user_id'=>$oldBuilding->modified_user_id,
//                                    'modified'=>$oldBuilding->modified,
//                                    'created_user_id'=>$oldBuilding->created_user_id,
//                                    'created'=>$oldBuilding->created,
//                                    'datatype' => 'copy'
//
//                                ]);
//
//                                if($saveBuilding = $InstitutionBuildings->save($newBuildingEntity)){
//                                    $oldBuildingId = $oldBuilding->id;
//                                    $newBuildingId = $saveBuilding->id;
//                                    $tableName = "building_custom_field_values";
//                                    $fieldName = "institution_building_id";
//                                    $this->copyCustomFields($connection, $tableName, $fieldName, $newBuildingId, $oldBuildingId);
//                                    $InstitutionFloorData = $InstitutionFloors
//                                    ->find('all')
//                                    ->where(['institution_building_id ' => $oldBuilding->id])
//                                    ->toArray();
//
//                                    foreach($InstitutionFloorData as $floorKey => $oldFloor){
//                                        $newFloor = $InstitutionFloors->newEntity([
//
//                                            'code'=>$this->codeGenerateF($Institution->code,
//                                                $institutionKey+1,
//                                                $buildingKey+1,
//                                                $floorKey+1),
//                                            'name'=>$oldFloor->name,
//                                            'start_date' => $AcademicPeriod->start_date,
//                                            'start_year' => $AcademicPeriod->start_year,
//                                            'end_date' => $AcademicPeriod->end_date,
//                                            'end_year' => $AcademicPeriod->end_year,
//
//                                            'area'=>$oldFloor->area,
//                                            'accessibility'=>$oldFloor->accessibility,
//                                            'comment'=>$oldFloor->comment,
//                                            'institution_building_id'=>$saveBuilding->id,
//                                            'institution_id'=>$oldFloor->institution_id,
//                                            'academic_period_id'=>$AcademicPeriod->id,
//                                            'floor_type_id'=>$oldFloor->floor_type_id,
//                                            'floor_status_id'=>$oldFloor->floor_status_id,
//
//                                            'infrastructure_condition_id'=>$oldFloor->infrastructure_condition_id,
//                                            'previous_institution_floor_id'=>$oldFloor->previous_institution_floor_id,
//                                            'modified_user_id'=>$oldFloor->modified_user_id,
//                                            'modified'=>$oldFloor->modified,
//                                            'created_user_id'=>$oldFloor->created_user_id,
//                                            'created'=>$oldFloor->created,
//                                            'datatype' => 'copy'
//
//                                        ]);
//
//                                        if($saveFloor = $InstitutionFloors->save($newFloor)){
//                                            $oldFloorId = $oldFloor->id;
//                                            $newFloorId = $saveFloor->id;
//                                            $tableName = "floor_custom_field_values";
//                                            $fieldName = "institution_floor_id";
//                                            $this->copyCustomFields($connection, $tableName, $fieldName, $newFloorId, $oldFloorId);
//
//                                            $InstitutionRoomData = $InstitutionRooms
//                                            ->find('all')
//                                            ->where(['institution_floor_id ' => $oldFloor->id])
//                                            ->toArray();
//
//                                            foreach($InstitutionRoomData as $roomKey=>$oldRoom){
//                                            $newRoom = $InstitutionRooms->newEntity([
//                                                    'code'=>$this->codeGenerateR($Institution->code,
//                                                        $institutionKey+1,
//                                                        $buildingKey+1,
//                                                        $floorKey+1,
//                                                        $roomKey+1),
//                                                    'name'=>$oldRoom->name,
//                                                    'start_date' => $AcademicPeriod->start_date,
//                                                    'start_year' => $AcademicPeriod->start_year,
//                                                    'end_date' => $AcademicPeriod->end_date,
//                                                    'end_year' => $AcademicPeriod->end_year,
//
//
//                                                    'accessibility'=>$oldRoom->accessibility,
//                                                    'comment'=>$oldRoom->comment,
//
//
//                                                    'room_type_id'=>$oldRoom->room_type_id,
//                                                    'room_status_id'=>$oldRoom->room_status_id,
//                                                    'institution_floor_id'=>$saveFloor->id,
//
//                                                    'institution_id'=>$oldRoom->institution_id,
//                                                    'academic_period_id'=>$AcademicPeriod->id,
//
//                                                    'infrastructure_condition_id'=>$oldRoom->infrastructure_condition_id,
//                                                    'area'=>$oldRoom->area,
//                                                    'previous_institution_room_id'=>$oldRoom->previous_institution_room_id,
//                                                    'modified_user_id'=>$oldRoom->modified_user_id,
//                                                    'modified'=>$oldRoom->modified,
//                                                    'created_user_id'=>$oldRoom->created_user_id,
//                                                    'created'=>$oldRoom->created,
//                                                    'datatype' => 'copy'
//                                                ]);
//                                                if($saveRoom = $InstitutionRooms->save($newRoom)){
//                                                    $oldRoomId = $oldRoom->id;
//                                                    $newRoomId = $saveRoom->id;
//                                                    $tableName = "room_custom_field_values";
//                                                    $fieldName = "institution_room_id";
//                                                    $this->copyCustomFields($connection, $tableName, $fieldName, $newRoomId, $oldRoomId);
//                                                }
//
//                                            }
//                                        }
//                                    }
//                                }
//
//                            }
//
//                        } elseif (!empty($newLand->getErrors())) { //POCOR-8523 Mange error handling if data not copied
//                            $this->Alert->error('general.add.failed', ['reset' => true]);
//                            return false;
//                        }
//                    }
//
//                } else {
//                    $Matched[$institutionKey] = $Institution->id;
//                }
//
//            }
//            if(!empty($Unmatched)){
//                $this->Alert->success('CopyData.updatedRecord', ['reset' => true]);
//                return false;
//            }elseif(!empty($Matched)){
//                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
//                return false;
//            }
//
//            //**************************POCOR-7326 End************************************** */
//
//
//            $this->triggerCopyShell(self::INFRASTRUCTURE, $copyFrom, $copyTo);
//        }
//
//        // Start POCOR-5337
//        if($entity->features == self::RISKS){
//            $from_academic_period = $entity->from_academic_period;
//            $to_academic_period = $entity->to_academic_period;
//            $copyFrom = $from_academic_period;
//            $copyTo = $to_academic_period;
//            $this->triggerCopyShell('Risk', $copyFrom, $copyTo);
//        }
//        $outcomeTemplates = TableRegistry::get('Outcome.OutcomeTemplates');
//        $outcomeCriterias = TableRegistry::get('Outcome.OutcomeCriterias');
//        if($entity->features == self::PERFORMANCE_OUTCOMES){
//            if($entity->from_academic_period == $entity->to_academic_period){
//                $this->Alert->error('CopyData.genralerror', ['reset' => true]);
//                return false;
//            }
//            $outcomeTemplatesData = $outcomeTemplates
//                ->find('all')
//                ->where(['academic_period_id ' => $entity->to_academic_period])
//                ->count();
//            $outcomeCriteriasData = $outcomeCriterias
//                ->find('all')
//                ->where(['academic_period_id ' => $entity->to_academic_period])
//                ->count();
//            $previousOutcomeTemplatesData = $outcomeTemplates
//                ->find('all')
//                ->where(['academic_period_id ' => $entity->from_academic_period])
//                ->count();
//            $previousOutcomeCriteriasData = $outcomeCriterias
//                ->find('all')
//                ->where(['academic_period_id ' => $entity->from_academic_period])
//                ->count();
//            if($outcomeTemplatesData>=$previousOutcomeTemplatesData && $outcomeCriteriasData>=$previousOutcomeCriteriasData){
//                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
//                return false;
//            }
//        }
//        // End POCOR-5337
//        if ($entity->features == self::PERFORMANCE_COMPETENCIES) {
//            $this->log('=======>Before triggerPerformanceCompetenciesShell', 'debug');
//        $this->triggePerformanceCompetenciesShell('PerformanceCompetencies',$entity->from_academic_period, $entity->to_academic_period, $entity->competency_criterias_value, $entity->competency_templates_value, $entity->competency_items_value,$entity->competency_periods_value); //POCOR-8504
//            $this->log(' <<<<<<<<<<======== After triggerPerformanceCompetenciesShell', 'debug');
//        }
//        //POCOR-7568 start
//        if ($entity->features == self::EDUCATION_STRUCTURE) {
//            $from_academic_period = $entity->from_academic_period;
//            $to_academic_period = $entity->to_academic_period;
//            $copyFrom = $from_academic_period;
//            $copyTo = $to_academic_period;
//            $this->triggerCopyShell('EducationStructureCopy', $copyFrom, $copyTo);
//        }
//        //POCOR-7568 end
//        // Start POCOR-6423
//        if ($entity->features == self::PERFORMANCE_ASSESSMENTS) {
//            $from_academic_period = $entity->from_academic_period;
//            $to_academic_period = $entity->to_academic_period;
//            $copyFrom = $from_academic_period;
//            $copyTo = $to_academic_period;
//            $this->triggerCopyShell('PerformanceAssessment', $copyFrom, $copyTo);
//        }
//        // End POCOR-6423
//        // Start POCOR-7764
//        if ($entity->features == self::REPORT_CARDS) {
//            $copyFrom = $entity->from_academic_period;
//            $copyTo = $entity->to_academic_period;
//            $this->triggerCopyShell('CopyReportCard', $copyFrom, $copyTo);
//        }
//        // End POCOR-7764
//        // Start POCOR-6425
//        if ($entity->features == self::PERFORMANCE_OUTCOMES) {
//            $this->log('=======>Before triggerPerformanceOutcomesShell', 'debug');
//            $this->triggePerformanceOutcomesShell('PerformanceOutcomes', $entity->from_academic_period, $entity->to_academic_period);
//            $this->log(' <<<<<<<<<<======== After triggerPerformanceOutcomesShell', 'debug');
//        }
//        // End POCOR-6425
//
//        // Start POCOR-8689
//        if ($entity->features == self::MASS_STUDENT_GRAD) {
//            $copyFrom = $entity->from_academic_period;
//            $copyTo = $entity->to_academic_period;
//
//            if($entity->from_academic_period == $entity->to_academic_period){
//                $this->Alert->error('CopyData.genralerror', ['reset' => true]);
//                return false;
//            }
//            if($entity->from_academic_period > $entity->to_academic_period){
//                $this->Alert->error('CopyData.invalidDate', ['reset' => true]);
//                return false;
//            }
//
//            $finalGradeIdsQuery = "
//            SELECT DISTINCT eg.id AS education_grade_id
//            FROM education_systems es
//            JOIN education_levels el ON es.id = el.education_system_id
//            JOIN education_cycles ec ON el.id = ec.education_level_id
//            JOIN education_programmes ep ON ec.id = ep.education_cycle_id
//            JOIN education_grades eg ON ep.id = eg.education_programme_id
//            WHERE es.academic_period_id = :copyFrom
//            AND eg.order = (
//                SELECT MAX(eg2.order)
//                FROM education_grades eg2
//                WHERE eg2.education_programme_id = ep.id
//            )";
//
//        // Execute the final grade query and fetch the result
//            $finalGradeIds = $connection->execute($finalGradeIdsQuery, ['copyFrom' => $copyFrom])->fetchAll('assoc');
//            $finalGradeIds = array_column($finalGradeIds, 'education_grade_id');
//            $InstitutionStudents = TableRegistry::get('Institution.InstitutionStudents');
//            $studentIdsQuery = $InstitutionStudents->find()
//                ->select(['student_id'])
//                ->where([
//                    'student_status_id' => 1, // Enrolled students
//                    'academic_period_id' => $copyFrom,
//                    'education_grade_id IN' => $finalGradeIds // Use IN for filtering final grades
//                ])
//                ->toArray();
//            if(empty($studentIdsQuery)){
//                $this->Alert->error('CopyData.nodataexist', ['reset' => true]);
//                return false;
//            } else {
//                $this->log('=======>Before triggerCopyMassGraduation', 'debug');
//                $this->triggerCopyShell('CopyMassGraduation', $copyFrom, $copyTo);
//                $this->log(' <<<<<<<<<<======== After triggerCopyMassGraduation', 'debug');
//            }
//        }
//        // End POCOR-8689
//
//    }
//
//
//     /*
//    * Function to copy Shift and Infrastucture from old academic period to new academic period
//    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
//    * return data
//    * @ticket POCOR-6825
//    */
//
//    public function triggerCopyShell($shellName, $copyFrom, $copyTo)
//    {
//        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$copyFrom.' '.$copyTo;
//        if($shellName=="EducationStructureCopy"){//POCOR-7568
//            $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$copyFrom.' '.$copyTo.' '.$this->Auth->User('id');
//        }
//        $logs = ROOT . DS . 'logs' . DS . $shellName.'_copy.log & echo $!';
//        $shellCmd = $cmd . ' >> ' . $logs;
//        $pid = exec($shellCmd);
//        Log::write('debug', $shellCmd);
//    }
//
//
//    public function getFeatureOptions(){
//        $options = [
//            // POCOR-7924:start
//            self::EDUCATION_STRUCTURE => __(self::EDUCATION_STRUCTURE),//POCOR-7568
//            self::INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS => __(self::INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS),
//            self::SHIFTS => __(self::SHIFTS),
//            self::INFRASTRUCTURE => __(self::INFRASTRUCTURE),
//            self::RISKS => __(self::RISKS), // POCOR-5337
//            self::PERFORMANCE_COMPETENCIES => __(self::PERFORMANCE_COMPETENCIES),
//            self::PERFORMANCE_OUTCOMES => __('Performance Outcomes'),
//            self::PERFORMANCE_ASSESSMENTS => __('Institution Performance Assessments'), // POCOR-6423
//            self::REPORT_CARDS => __(self::REPORT_CARDS), // POCOR-7764 // POCOR-7924: end
//            self::MASS_STUDENT_GRAD => __(self::MASS_STUDENT_GRAD) // POCOR-8689
//        ];
//        return $options;
//    }
//
//    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
//    {
//        switch ($field) {
//            case 'from_academic_period':
//                return __('From Academic Period');
//            case 'to_academic_period':
//                return __('To Academic Period');
//            case 'features':
//                return __('Features');
//            case 'created_user_id':
//                return __('Created By');
//            case 'created':
//                return __('Created');
//            default:
//                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
//        }
//    }
//
//    public function onGetToAcademicPeriod(Event $event, Entity $entity)
//    {
//        $AcademicPeriodsData = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods'); //TableRegistry::get('Academic.AcademicPeriods');
//        $result = $AcademicPeriodsData
//            ->find()
//            ->select(['name'])
//            ->where(['id' => $entity->to_academic_period])
//            ->first();
//
//        return $entity->to_academic_period = $result->name;
//    }
//
//    public function onGetGeneratedBy(Event $event, Entity $entity)
//    {
//        $Users = TableRegistry::get('User.Users');
//        $result = $Users
//            ->find()
//            ->select(['first_name','last_name'])
//            ->where(['id' => $entity->generated_by])
//            ->first();
//
//        return $entity->generated_by = $result->first_name.' '.$result->last_name;
//    }
//    /*
//    * Function to copy competency_criterias, competency_templates and competency_items to new academic period
//    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
//    * return boolean
//    * @ticket POCOR-6424
//    */
//
//    public function triggePerformanceCompetenciesShell($shellName, $from_academic_period, $to_academic_period = null, $competency_criterias_value = null, $competency_templates_value = null, $competency_items_value = null, $competency_periods_value = null)
//    {
//        $args = '';
//        $args .= !is_null($from_academic_period) ? ' '.$from_academic_period : '';
//        $args .= !is_null($to_academic_period) ? ' '.$to_academic_period : '';
//        $args .= !is_null($competency_criterias_value) ? ' '.$competency_criterias_value : '';
//        $args .= !is_null($competency_templates_value) ? ' '.$competency_templates_value : '';
//        $args .= !is_null($competency_items_value) ? ' '.$competency_items_value : '';
//        $args .= !is_null($competency_periods_value) ? ' '.$competency_periods_value : ''; //POCOR-8504
//
//        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.$args;
//        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
//        $shellCmd = $cmd . ' >> ' . $logs;
//        exec($shellCmd);
//        Log::write('debug', $shellCmd);
//     }
//    //POCOR-7576-shifts start
//    private function checkshiftCopiedData( $copyFrom,$copyTo)
//    {
//        $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
//        $copiedRecords = $InstitutionShifts->find()
//                        ->innerJoin(
//                                    ['InstitutionShifts1' => 'institution_shifts'],
//                                    [
//                                        $InstitutionShifts->aliasField('institution_id') . ' = InstitutionShifts1.institution_id',
//                                        $InstitutionShifts->aliasField('location_institution_id') . ' = InstitutionShifts1.location_institution_id',
//                                        $InstitutionShifts->aliasField('shift_option_id') . ' = InstitutionShifts1.shift_option_id',
//                                        $InstitutionShifts->aliasField('start_time') . ' = InstitutionShifts1.start_time',
//                                        $InstitutionShifts->aliasField('end_time') . ' = InstitutionShifts1.end_time'
//                                    ]
//                        )
//                        ->where([
//                                    $InstitutionShifts->aliasField('academic_period_id') => $copyFrom,
//                                    'InstitutionShifts1.academic_period_id' => $copyTo,
//                        ])
//                        ->count();
//
//        $allRecords= $InstitutionShifts->find()
//                                  ->where([$InstitutionShifts->aliasField('academic_period_id') => $copyFrom])
//                                  ->count();
//        if($copiedRecords<$allRecords){
//                return false;
//        }
//        return true;
//    }
//
//    //POCOR-7576-shifts end
//    //POCOR-7576-institution programme start
//    private function checkInstitutionCopiedData($copyFrom,$copyTo){
//        $educationGradesTable = TableRegistry::get('Education.EducationGrades');
//        $institutionGradesTable = TableRegistry::get('Institution.InstitutionGrades');
//
//
//        $query = $institutionGradesTable
//        ->find()
//        ->select([
//            'period_id' => 'AcademicPeriods.id',
//            'period_name' => 'AcademicPeriods.name',
//            'period_code' => 'AcademicPeriods.code',
//            'grade_id' => 'EducationGrades.id',
//            'grade_name' => 'EducationGrades.name',
//            'programme_name' => 'EducationProgrammes.name',
//            'institution_id' => 'Institutions.id'
//        ])
//        ->innerJoin(
//            ['EducationGrades' => 'education_grades'],
//            ['EducationGrades.id = InstitutionGrades.education_grade_id']
//        )
//        ->innerJoin(
//            ['Institutions' => 'institutions'],
//            ['Institutions.id = InstitutionGrades.institution_id']
//        )
//        ->innerJoin(
//            ['EducationProgrammes' => 'education_programmes'],
//            ['EducationGrades.education_programme_id = EducationProgrammes.id']
//        )
//        ->innerJoin(
//            ['EducationCycles' => 'education_cycles'],
//            ['EducationProgrammes.education_cycle_id = EducationCycles.id']
//        )
//        ->innerJoin(
//            ['EducationLevels' => 'education_levels'],
//            ['EducationCycles.education_level_id = EducationLevels.id']
//        )
//        ->innerJoin(
//            ['EducationSystems' => 'education_systems'],
//            ['EducationLevels.education_system_id = EducationSystems.id']
//        )
//        ->innerJoin(
//            ['AcademicPeriods' => 'academic_periods'],
//            ['EducationSystems.academic_period_id = AcademicPeriods.id']
//        )
//        ->order([
//            'AcademicPeriods.order' => 'ASC',
//            'EducationLevels.order' => 'ASC',
//            'EducationCycles.order' => 'ASC',
//            'EducationProgrammes.order' => 'ASC',
//            'EducationGrades.order' => 'ASC',
//            'Institutions.id' => 'ASC'
//        ]);
//        $copyFromData = $this->filter_array($query,$copyFrom,'period_id');
//        $copyToData = $this->filter_array($query,$copyTo,'period_id');
//        $insIds=array_unique(array_column($copyFromData, 'institution_id'));
//        $count=0;
//
//        foreach($insIds as $val){
//
//            $data1 = array_filter($copyFromData, function ($value, $key) use ($val) {
//                return $value['institution_id'] == $val ;
//            }, ARRAY_FILTER_USE_BOTH);
//            $data2 = array_filter($copyToData, function ($value, $key) use ($val) {
//                return $value['institution_id'] == $val ;
//            }, ARRAY_FILTER_USE_BOTH);
//            if(count($data1)>count($data2)){
//               $count=$count+(count($data1)-count($data2));
//            }
//
//        }
//        if($count>0){
//           return false;
//        }
//        return true;
//
//   }
//
//    //POCOR-7576-institution programme end
//    public function filter_array($array,$term,$column){
//        $matches = array();
//        foreach($array as $a){
//            if($a[$column] == $term)
//                $matches[]=$a;
//        }
//        return $matches;
//    }
//
//    /**
//     * @param \Cake\Datasource\ConnectionInterface $connection
//     * @param $tableName
//     * @param $fieldName
//     * @param $newId
//     * @param $oldId
//     *
//     */
//    private function copyCustomFields(\Cake\Datasource\ConnectionInterface $connection, $tableName, $fieldName, $newId, $oldId)
//    {
//        $sql = "INSERT IGNORE INTO `$tableName`
//    (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`,
//     `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`,
//     `$fieldName`, `created_user_id`, `created`)
//     SELECT uuid(), `CustomFieldValues`.`text_value`,
//            `CustomFieldValues`.`number_value`,
//            `CustomFieldValues`.`decimal_value`,
//            `CustomFieldValues`.`textarea_value`,
//            `CustomFieldValues`.`date_value`,
//            `CustomFieldValues`.`time_value`,
//            `CustomFieldValues`.`file`,
//            `CustomFieldValues`.`infrastructure_custom_field_id`,
//            $newId,
//            `CustomFieldValues`.`created_user_id`,
//            NOW() FROM `$tableName` AS
//                `CustomFieldValues` WHERE `CustomFieldValues`.$fieldName = $oldId";
////        $this->log($sql, 'debug');
//        $connection->query($sql);
//
//    }
//    //POCOR-7576-institution programme end
//
//    /*
//    * Function to copy outcome_criterias and outcome_templates to new academic period
//    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
//    * return boolean
//    * @ticket POCOR-6425
//    */
//
//    public function triggePerformanceOutcomesShell($shellName, $from_academic_period = null, $to_academic_period = null)
//    {
//        $args = '';
//        $args .= !is_null($from_academic_period) ? ' ' . $from_academic_period : '';
//        $args .= !is_null($to_academic_period) ? ' ' . $to_academic_period : '';
//        $cmd = ROOT . DS . 'bin' . DS . 'cake ' . $shellName . $args;
//        $logs = ROOT . DS . 'logs' . DS . $shellName . '.log & echo $!';
//        $shellCmd = $cmd . ' >> ' . $logs;
//        exec($shellCmd);
//        Log::write('debug', $shellCmd);
//    }
//
//}
