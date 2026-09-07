<?php
namespace Institution\Model\Table;

use ArrayObject;
use DatePeriod;
use DateInterval;
use Cake\I18n\Date;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Core\Configure;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\Exception\RecordNotFoundException;

class InstitutionStudentAbsencesTable extends ControllerActionTable
{
    use OptionsTrait;
    private $_fieldOrder = ['absence_type_id', 'academic_period_id', 'class', 'student_id', 'full_day', 'date'];

    private $absenceList;
    private $absenceCodeList;

    private $workflowRuleEvents = [
        [
            'value' => 'Workflow.onAssignToHomeRoomTeacher',
            'text' => 'Assign to Home Room Teacher',
            'description' => 'Triggering this rule will assign the case to the respective Home Room Teacher',
            'method' => 'onAssignToHomeRoomTeacher',
            'roleCode' => 'HOMEROOM_TEACHER'
        ],
        [
            'value' => 'Workflow.onAssignToSecondaryTeacher',
            'text' => 'Assign to Secondary Teacher',
            'description' => 'Triggering this rule will assign the case to the respective Secondary Teacher',
            'method' => 'onAssignToSecondaryTeacher',
            'roleCode' => 'HOMEROOM_TEACHER'
        ],
        [
            'value' => 'Workflow.onAssignToPrincipal',
            'text' => 'Assign to Principal',
            'description' => 'Triggering this rule will assign the case to Principal',
            'method' => 'onAssignToPrincipal',
            'roleCode' => 'PRINCIPAL'
        ],
        [
            'value' => 'Workflow.onAssignToMoeadmin',
            'text' => 'Assign to MOE ADMIN',
            'description' => 'Triggering this rule will assign the case to MOE ADMIN',
            'method' => 'onAssignToMoeadmin',
            'roleCode' => 'MOE_ADMIN'
        ]
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('Institution.Absence');

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
        $this->belongsTo('InstitutionStudentAbsenceDays', ['className' => 'Institution.InstitutionStudentAbsenceDays', 'foreignKey' =>'institution_student_absence_day_id']);

        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('AcademicPeriod.Period');

        if (!in_array('Cases', (array) Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Institution.Case');
        }
        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['add', 'edit', 'delete']
        ]);
        if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Risk.Risks');
        }

        $this->absenceList = $this->AbsenceTypes->getAbsenceTypeList();
        $this->absenceCodeList = $this->AbsenceTypes->getCodeList();

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('index', false);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.InstitutionStudentRisks.calculateRiskValue'] = 'institutionStudentRiskCalculateRiskValue';
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        $events['InstitutionCase.onSetCustomCaseTitle'] = 'onSetCustomCaseTitle';
        $events['InstitutionCase.onSetLinkedRecordsCheckCondition'] = 'onSetLinkedRecordsCheckCondition';
        $events['InstitutionCase.onSetCustomCaseSummary'] = 'onSetCustomCaseSummary';
        $events['InstitutionCase.onSetCaseRecord'] = 'onSetCaseRecord';
        $events['StudentAbsencesPeriodDetails.afterSave'] = ['callable' => 'afterSave']; //POCOR-7205
        $events['InstitutionCase.onBuildCustomQuery'] = 'onBuildCustomQuery';
        $events['InstitutionCase.onIncludeCustomExcelFields'] = 'onIncludeCustomExcelFields';
        $events['InstitutionCase.onSetFilterToolbarElement'] = 'onSetFilterToolbarElement';
        $events['InstitutionCase.onCaseIndexBeforeQuery'] = 'onCaseIndexBeforeQuery';

        // workflow rule events
        $events['Workflow.getRuleEvents'] = 'getWorkflowRuleEvents';
        foreach($this->workflowRuleEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        if ($toolbarButtons->offsetExists('back')) {
            $encodedParams = $this->request->params['pass'][1];
            $backUrl = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'StudentAttendances',
                'index',
                $encodedParams
            ];

            $toolbarButtons['back']['url'] = $backUrl;
        }
    }

    public function getWorkflowRuleEvents(EventInterface $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowRuleEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function onAssignToHomeRoomTeacher(EventInterface $event, Entity $caseEntity, Entity $linkedRecordEntity, ArrayObject $extra)
    {
        $Students = TableRegistry::getTableLocator()->get('Institution.Students');
        $ClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $Classes = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $Cases = TableRegistry::getTableLocator()->get('Cases.InstitutionCases');

        $classTeachers = $Students->find()
            ->select([
                'homeroom_staff_id' => $Classes->aliasField('staff_id'),
            ])
            ->innerJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                $ClassStudents->aliasField('student_id = ') . $Students->aliasField('student_id'),
                $ClassStudents->aliasField('institution_id = ') . $Students->aliasField('institution_id'),
                $ClassStudents->aliasField('education_grade_id = ') . $Students->aliasField('education_grade_id'),
                $ClassStudents->aliasField('student_status_id = ') . $Students->aliasField('student_status_id'),
                $ClassStudents->aliasField('academic_period_id = ') . $Students->aliasField('academic_period_id')
            ])
            ->innerJoin([$Classes->alias() => $Classes->table()], [
                $Classes->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
            ])
            ->where([
                $Students->aliasField('student_id') => $linkedRecordEntity->student_id,
                $Students->aliasField('institution_id') => $linkedRecordEntity->institution_id,
                $Students->aliasField('academic_period_id') => $linkedRecordEntity->academic_period_id,
                $Students->aliasField('start_date <= ') => $linkedRecordEntity->date,
                $Students->aliasField('end_date >= ') => $linkedRecordEntity->date
            ])
            ->first();

        if (!empty($classTeachers)) {
            $staffId = $classTeachers->homeroom_staff_id;

            if (!empty($staffId)) {
                $caseEntity->assignee_id = $staffId;
                $extra['assigneeFound'] = true;
                $Cases->save($caseEntity);
            }
        }
    }

    public function onAssignToSecondaryTeacher(EventInterface $event, Entity $caseEntity, Entity $linkedRecordEntity, ArrayObject $extra)
    {
        $Students = TableRegistry::getTableLocator()->get('Institution.Students');
        $ClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $Classes = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $ClassesSecondaryStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionClassesSecondaryStaff');
        $Cases = TableRegistry::getTableLocator()->get('Cases.InstitutionCases');

        $classTeachers = $Students->find()
            ->select([
                'secondary_staff_id' => $ClassesSecondaryStaff->aliasField('secondary_staff_id')
            ])
            ->innerJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                $ClassStudents->aliasField('student_id = ') . $Students->aliasField('student_id'),
                $ClassStudents->aliasField('institution_id = ') . $Students->aliasField('institution_id'),
                $ClassStudents->aliasField('education_grade_id = ') . $Students->aliasField('education_grade_id'),
                $ClassStudents->aliasField('student_status_id = ') . $Students->aliasField('student_status_id'),
                $ClassStudents->aliasField('academic_period_id = ') . $Students->aliasField('academic_period_id')
            ])
            ->innerJoin([$Classes->alias() => $Classes->table()], [
                $Classes->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
            ])
            ->innerJoin([$ClassesSecondaryStaff->alias() => $ClassesSecondaryStaff->table()], [
                $ClassesSecondaryStaff->aliasField('institution_class_id = ') . $Classes->aliasField('id')
            ])
            ->where([
                $Students->aliasField('student_id') => $linkedRecordEntity->student_id,
                $Students->aliasField('institution_id') => $linkedRecordEntity->institution_id,
                $Students->aliasField('academic_period_id') => $linkedRecordEntity->academic_period_id,
                $Students->aliasField('start_date <= ') => $linkedRecordEntity->date,
                $Students->aliasField('end_date >= ') => $linkedRecordEntity->date
            ])
            ->first();

        if (!empty($classTeachers)) {
            $staffId = $classTeachers->secondary_staff_id;

            if (!empty($staffId)) {
                $caseEntity->assignee_id = $staffId;
                $extra['assigneeFound'] = true;
                $Cases->save($caseEntity);
            }
        }
    }

    public function onAssignToPrincipal(EventInterface $event, Entity $caseEntity, Entity $linkedRecordEntity, ArrayObject $extra)
    {
        $InstitutionPositions = TableRegistry::getTableLocator()->get('Institution.InstitutionPositions');
        $Cases = TableRegistry::getTableLocator()->get('Cases.InstitutionCases');

        $institutionPrincipal = $InstitutionPositions->find()
            ->select([
                'principal_id' => 'InstitutionStaff.staff_id'
            ])
            ->matching('InstitutionStaff')
            ->matching('StaffPositionTitles')
            ->where([
                'InstitutionStaff.institution_id' => $linkedRecordEntity->institution_id,
                'StaffPositionTitles.name ' => 'Principal'
            ])
            ->first();

        if (!empty($institutionPrincipal)) {
            $staffId = $institutionPrincipal->principal_id;

            if (!empty($staffId)) {
                $caseEntity->assignee_id = $staffId;
                $extra['assigneeFound'] = true;
                $Cases->save($caseEntity);
            }
        }
    }

    public function onAssignToMoeadmin(EventInterface $event, Entity $caseEntity, Entity $linkedRecordEntity, ArrayObject $extra)
    {
        $InstitutionPositions = TableRegistry::getTableLocator()->get('Institution.InstitutionPositions');
        $Cases = TableRegistry::getTableLocator()->get('Cases.InstitutionCases');

        $institutionMoeAdmin = $InstitutionPositions->find()
            ->select([
                'moeadmin_id' => 'InstitutionStaff.staff_id'
            ])
            ->matching('InstitutionStaff')
            ->matching('StaffPositionTitles')
            ->where([
                'InstitutionStaff.institution_id' => $linkedRecordEntity->institution_id,
                'StaffPositionTitles.name ' => 'MOE ADMIN'
            ])
            ->first();

        if (!empty($institutionMoeAdmin)) {
            $staffId = $institutionMoeAdmin->moeadmin_id;

            if (!empty($staffId)) {
                $caseEntity->assignee_id = $staffId;
                $extra['assigneeFound'] = true;
                $Cases->save($caseEntity);
            }
        }
    }

    private function addInstitutionStudentAbsenceDayRecord($entity, $startDate, $endDate)
    {
        $startDate = $startDate->setTime(0,0,0); // POCOR-9392
        $endDate   = $endDate->setTime(0,0,0); // POCOR-9392
        $entityStart = clone $startDate;
        $entityStart->subDay(1);
        $entityEnd = clone $endDate;
        $entityEnd->addDay(1);
        $InstitutionStudentAbsenceDays = $this->InstitutionStudentAbsenceDays;
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday'
        ];

        $start = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('first_day_of_week');
        $daysPerWeek = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('days_per_week') - 1;
        $workingDays = [];
        for ($a = $daysPerWeek; $a >= 0; $a--) {
            $key = (($start + $a) % 7);
            $workingDays[$key] = $key;
        }
        $days = array_diff_key($days, $workingDays);
        $s = clone $entityStart;
        $tmp = clone $s;
        $tmp->subDay(1);
        $changeStart = false;
        while (in_array($tmp->format('l'), $days)) {
            $tmp->subDay(1);
            $changeStart = true;
        }
        if ($changeStart) {
            $s = $tmp;
        }
        $e = clone $entityEnd;
        $tmp = clone $e;
        $tmp->addDay(1);
        $changeEnd = false;
        while (in_array($tmp->format('l'), $days)) {
            $tmp->addDay(1);
            $changeEnd = true;
        }
        if ($changeEnd) {
            $e = $tmp;
        }

        $consecutiveRecords = $InstitutionStudentAbsenceDays
            ->find('inDateRange', [
                'start_date' => $s,
                'end_date' => $e
            ])
            ->where([
                $InstitutionStudentAbsenceDays->aliasField('student_id') => $entity->student_id,
                // $InstitutionStudentAbsenceDays->aliasField('absence_type_id') => $entity->absence_type_id, // POCOR-7357
                $InstitutionStudentAbsenceDays->aliasField('institution_id') => $entity->institution_id,
                $InstitutionStudentAbsenceDays->aliasField('start_date') => $startDate,
                $InstitutionStudentAbsenceDays->aliasField('end_date') => $endDate
            ])
            ->order([$InstitutionStudentAbsenceDays->aliasField('start_date')]);
        $count = $consecutiveRecords->count();

        switch ($count) {
            // There is no record, we will add the entry
            case 0:
                $i = 0;
                $s = clone $startDate;
                $daysAbsent = 0;
                // POCOR-9392 start
// safety: if inputs are reversed or null, bail early
                if (!$startDate || !$endDate || $startDate->gt($endDate)) {
                    $daysAbsent = 0;
                } else {
                    $s = clone $startDate;
                    $end = clone $endDate;

                    // optional: normalize to midnight to avoid time-of-day off-by-one
                    // $s = $s->setTime(0,0,0);
                    // $end = $end->setTime(0,0,0);

                    // optional: hard cap based on range length to prevent accidental infinite loops
                    $maxSteps = $s->diffInDays($end) + 2; // inclusive range + small buffer
                    $steps = 0;

                    do {
                        if (!in_array($s->format('l'), $days, true)) {
                            $daysAbsent++;
                        }
                        $s = $s->addDay(1);   // ← REASSIGN for immutable dates
                        if (++$steps > $maxSteps) { break; } // safety fuse
                    } while ($s->lte($end));
                }
                // POCOR-9392 end
                $dayEntity = $InstitutionStudentAbsenceDays->newEntity([
                    'student_id'      => $entity->student_id,
                    'institution_id'  => $entity->institution_id,
                    'absence_type_id' => $entity->absence_type_id,
                    'absent_days'     => $daysAbsent,
                    'start_date'      => $startDate,
                    'end_date'        => $endDate
                ]);

                $dayEntity = $InstitutionStudentAbsenceDays->save($dayEntity);
                $this->updateAll(['institution_student_absence_day_id' => $dayEntity->id], ['id' => $entity->id]);
                break;
            // When there is one record found
            case 1:
                $i = 0;
                $recordEntity = $consecutiveRecords->first();
                $recordStartDate = $recordEntity->start_date;
                $recordStartDate   = $recordStartDate->setTime(0,0,0); // POCOR-9392
                $recordEndDate = $recordEntity->end_date;
                $recordEndDate   = $recordEndDate->setTime(0,0,0); // POCOR-9392

                if ($startDate->lt($recordStartDate)) {
                    $recordStartDate = $startDate;
                } elseif ($startDate->gt($recordEndDate)) {
                    $recordEndDate = $endDate;
                }

                $s = clone $recordStartDate;
                $daysAbsent = 0;
                do {
                    if (!in_array($s->format('l'), $days)) {
                        $daysAbsent++;
                    }
                    $s->addDay(1);
                    if ($i++ == 7) {
                        break;
                    }
                } while ($s->lte($recordEndDate));
                //POCOR-7035[START]

                // $dayEntity = $InstitutionStudentAbsenceDays->patchEntity($recordEntity, [
                //     'start_date' => $recordStartDate,
                //     'end_date' => $recordEndDate,
                //     'absence_type_id' => $entity->absence_type_id,
                //     'absent_days' => $daysAbsent
                // ]);
                // $dayEntity = $InstitutionStudentAbsenceDays->save($dayEntity);
                $InstitutionStudentAbsenceDays->updateAll(['absence_type_id' => $entity->absence_type_id], ['student_id' => $entity->student_id, 'institution_id'=>$entity->institution_id, 'start_date'=>$startDate, 'end_date'=>$endDate]);

                //POCOR-7035[END]
                $this->updateAll(['institution_student_absence_day_id' => $recordEntity->id], ['id' => $entity->id]);
                break;
            // When there is two records found, it means this record happen to fall in between the two record
            case 2:
                $i = 0;
                $recordEntities = $consecutiveRecords->toArray();
                $recordStartDate = $recordEntities[0]->start_date;
                $recordEndDate = $recordEntities[1]->end_date;

                $recordsId = [$recordEntities[0]->id, $recordEntities[1]->id];

                $s = clone $recordStartDate;
                $daysAbsent = 0;
                do {
                    if (!in_array($s->format('l'), $days)) {
                        $daysAbsent++;
                    }
                    $s->addDay(1);
                    if ($i++ == 7) {
                        break;
                    }
                } while ($s->lte($recordEndDate));

                //POCOR-7035[START]

                // $dayEntity = $InstitutionStudentAbsenceDays->newEntity([
                //     'student_id' => $entity->student_id,
                //     'institution_id' => $entity->institution_id,
                //     'absence_type_id' => $entity->absence_type_id,
                //     'absent_days' => $daysAbsent,
                //     'start_date' => $recordStartDate,
                //     'end_date' => $recordEndDate
                // ]);
                // $dayEntity = $InstitutionStudentAbsenceDays->save($dayEntity);
                $InstitutionStudentAbsenceDays->updateAll(['absence_type_id' => $entity->absence_type_id],
                    ['student_id' => $entity->student_id,
                        'institution_id'=>$entity->institution_id,
                        'start_date'=>$startDate,
                        'end_date'=>$endDate]);

                //POCOR-7035[END]
//                $this->updateAll(['institution_student_absence_day_id' => $dayEntity->id], ['institution_student_absence_day_id IN ' => $recordsId]); // POCOR-9392 no day entity
//                $this->updateAll(['institution_student_absence_day_id' => $dayEntity->id], ['id' => $entity->id]); // POCOR-9392 no day entity
                break;
        }
    }

    //POCOR-7205
    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        // $InstitutionStudentAbsenceDays = $this->InstitutionStudentAbsenceDays;
        // $startDate = $entity->start_date;
        // $endDate = $entity->end_date;
        // $fullDay = $entity->full_day;

        // if ($fullDay && $entity->isNew()) {
        //     $this->addInstitutionStudentAbsenceDayRecord($entity, $startDate, $endDate);
        // }

        // $InstitutionStudentAbsenceDays = $this->InstitutionStudentAbsenceDays;
        $startDate = $entity->date;
        $endDate = $entity->date;

        //POCOR-7035[START]
        // if ($entity->isNew()) {
        //     $this->addInstitutionStudentAbsenceDayRecord($entity, $startDate, $endDate);
        // }
        $this->addInstitutionStudentAbsenceDayRecord($entity, $startDate, $endDate);
        //POCOR-7035[END]
    }

    public function onSetCustomCaseTitle(EventInterface $event, Entity $entity)
    {
        $recordEntity = $this->get($entity->id, [
            'contain' => ['Users', 'AbsenceTypes', 'Institutions']
        ]);
        $title = '';
        $title .= $recordEntity->user->name.' '.__('from').' '.$recordEntity->institution->code_name.' '.__('with').' '.$recordEntity->absence_type->name;

        return $title;
    }

    public function onSetFilterToolbarElement(EventInterface $event, ArrayObject $params, $institutionId)
    {

        $requestQuery = $params['query'];
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $InstitutionEducationGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');

        // academic_period_id
        if (empty($requestQuery['academic_period_id'])) {
            $requestQuery['academic_period_id'] = $AcademicPeriods->getCurrent();
        }
        $selectedAcademicPeriod = $requestQuery['academic_period_id'];
        $academicPeriodOptions = $AcademicPeriods->getYearList();

        // education_grade_id
        if (empty($requestQuery['education_grade_id'])) {
            $firstInstitutionEducationGradesResult = $InstitutionEducationGrades
                ->find()
                ->select([
                    'id' => 'EducationGrades.id',
                    'name' => 'EducationGrades.name'
                ])
                ->contain(['EducationGrades'])
                ->where(['institution_id' => $institutionId])
                ->group('education_grade_id')
                ->order(['education_grade_id'])
                ->first();

            if (!empty($firstInstitutionEducationGradesResult)) {
                //$requestQuery['education_grade_id'] = $firstInstitutionEducationGradesResult->id;
                  $requestQuery['education_grade_id'] = 'all';
            } else {
                $requestQuery['education_grade_id'] = -1;
            }
        }

        $selectedEducationGrades = $requestQuery['education_grade_id'];
        $result = $InstitutionEducationGrades
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
            ->select([
                'id' => 'EducationGrades.id',
                'name' => 'EducationGrades.name'
            ])
            ->contain(['EducationGrades'])
            ->where(['institution_id' => $institutionId])
            ->group('education_grade_id')
            ->all();

        if (!$result->isEmpty()) {
            $gradeList    = $result->toArray();
            $allGradeList = ["0" => 'All'];
            $educationGradesOptions = $allGradeList + $gradeList;

        } else {
            $educationGradesOptions = ['-1' => __('No Grades')];
        }

        // institution_class_id
        if (empty($requestQuery['institution_class_id'])) {

            $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
            $firstInstitutionClassIdResult = $InstitutionClasses
                ->find('byGrades', ['education_grade_id' => $selectedEducationGrades])
                ->select([
                    'id' => $InstitutionClasses->aliasField('id'),
                    'name' => $InstitutionClasses->aliasField('name')
                ])
                ->where([
                    [$InstitutionClasses->aliasField('academic_period_id') => $selectedAcademicPeriod],
                    [$InstitutionClasses->aliasField('institution_id') => $institutionId]
                ])
                ->order([$InstitutionClasses->aliasField('id')])
                ->first();

            if (!empty($firstInstitutionClassIdResult)) {
                // $requestQuery['institution_class_id'] = $firstInstitutionClassIdResult->id;
                $requestQuery['institution_class_id'] = 'all';
            } else {
                $requestQuery['institution_class_id'] = -1;
            }
        }

        if ($selectedEducationGrades != -1) {

            $selectedClassId = $requestQuery['institution_class_id'];
            $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');

            $result = $InstitutionClasses
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'name'
                ])
                ->find('byGrades', ['education_grade_id' => $selectedEducationGrades])
                ->select([
                    'id' => $InstitutionClasses->aliasField('id'),
                    'name' => $InstitutionClasses->aliasField('name')
                ])
                ->where([
                    [$InstitutionClasses->aliasField('academic_period_id') => $selectedAcademicPeriod],
                    [$InstitutionClasses->aliasField('institution_id') => $institutionId]
                ])
                ->all();

            if (!$result->isEmpty()) {
                $classList = $result->toArray();

                $allClassList = ["0" => 'All'];
                $institutionClassOptions = $allClassList + $classList;
            } else {
                $institutionClassOptions = ['-1' => __('No Classes')];
            }
        } else {
            $selectedClassId = -1;
            $institutionClassOptions = ['-1' => __('No Classes')];
        }

        $params['element'] = ['filter' => ['name' => 'Cases.StudentAbsences/controls', 'order' => 2]];

        $params['options'] = [
            'selectedAcademicPeriod' => $selectedAcademicPeriod,
            'academicPeriodOptions' => $academicPeriodOptions,
            'selectedEducationGrades' => $selectedEducationGrades,
            'educationGradesOptions' => $educationGradesOptions,
            'selectedClassId' => $selectedClassId,
            'institutionClassOptions' => $institutionClassOptions
        ];
    }

    public function onCaseIndexBeforeQuery(EventInterface $event, $requestQuery, Query $query)
    {
        // if (isset($requestQuery['institution_class_id']) && $requestQuery['institution_class_id'] != -1) {
            if (!empty($requestQuery)) {
            $institutionClassId = $requestQuery['institution_class_id'];
            $educationGradeId = $requestQuery['education_grade_id'];

            $academicPeriodId = $requestQuery['academic_period_id'];

            $InstitutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
            $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
            $academicPeriodId = !is_null($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $AcademicPeriods->getCurrent();
            if (isset($requestQuery['institution_class_id']) && $requestQuery['institution_class_id'] != 0) {
                $conditions[] = [$InstitutionClassStudents->aliasField('institution_class_id') => $institutionClassId];
            }
            if (isset($requestQuery['education_grade_id']) && $requestQuery['education_grade_id'] != 0) {
                $conditions[] = [$InstitutionClassStudents->aliasField('education_grade_id') => $educationGradeId];
            }
            $periodEntity = $AcademicPeriods
                ->find()
                ->select([
                    $AcademicPeriods->aliasField('start_date'),
                    $AcademicPeriods->aliasField('end_date')
                ])
                ->where([$AcademicPeriods->aliasField('id') => $academicPeriodId])
                ->first();

            if (!is_null($periodEntity)) {
                $startDate = $periodEntity->start_date->format('Y-m-d');
                $endDate = $periodEntity->end_date->format('Y-m-d');
            }

            $result = $InstitutionClassStudents
                ->find('list', [
                    'keyField' => 'student_id',
                    'valueField' => 'student_id'
                ])
                ->select([$InstitutionClassStudents->aliasField('student_id')])
                ->where([
                    $conditions,
                    $InstitutionClassStudents->aliasField('academic_period_id') => $academicPeriodId
                ])
                ->all();
            if (!$result->isEmpty() && isset($startDate)) {
                $studentList = $result->toArray();

                $query
                    ->innerJoin(
                        [$this->alias() => $this->table()],
                        [$this->aliasField('id = ') . 'LinkedRecords.record_id']
                    )
                    ->where([
                        $this->aliasField('student_id IN ') => $studentList,
                        $this->aliasField('date >= ') => $startDate,
                        $this->aliasField('date <= ') => $endDate
                    ]);
            } else {
                $query->where(['1 = 0']);
            }
        } else {
            $query->where(['1 = 0']);
        }
    }

    public function onSetCustomCaseSummary(EventInterface $event, int $id)
    {
        try {
            $recordEntity = $this->get($id, [
                'contain' => ['Users', 'AbsenceTypes', 'Institutions']
            ]);
            //POCOR-4864 start
            $StudentAbsenceTable=TableRegistry::getTableLocator()->get('institution_student_absence_details');
            $StudentAbsenceReason=TableRegistry::getTableLocator()->get('student_absence_reasons');

            $StudentAbsenceTableRecord= $StudentAbsenceTable->find()
                    ->select([$StudentAbsenceTable->aliasField('comment'),
                         $StudentAbsenceTable->aliasField('student_absence_reason_id'),
                       ])
                    ->where([$StudentAbsenceTable->aliasField('student_id')=>$recordEntity->student_id ,
                    $StudentAbsenceTable->aliasField('institution_id')=>$recordEntity->institution_id ,
                    $StudentAbsenceTable->aliasField('academic_period_id')=>$recordEntity->academic_period_id,
                    $StudentAbsenceTable->aliasField('institution_class_id')=>$recordEntity->institution_class_id ,
                    $StudentAbsenceTable->aliasField('education_grade_id')=>  $recordEntity->education_grade_id ,
                    $StudentAbsenceTable->aliasField('date')=>    $recordEntity->date,
                    $StudentAbsenceTable->aliasField('absence_type_id')=> $recordEntity->absence_type_id ,
                    $StudentAbsenceTable->aliasField('created')=> $recordEntity->created
                    ])->first();
            if(isset($StudentAbsenceTableRecord->student_absence_reason_id)){
            $StudentAbsenceReasonRecord=$StudentAbsenceReason->get($StudentAbsenceTableRecord->student_absence_reason_id);
            }
            //POCOR-4864 ends
            $days = [
                0 => 'Sunday',
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday'
            ];
            $start = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('first_day_of_week');
            $daysPerWeek = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('days_per_week') - 1;

            $ConfigItem = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
            $format = $ConfigItem->value('date_format');
            // $startDate = $recordEntity->start_date->format($format);
            // $endDate = $recordEntity->end_date->format($format);
            $date = $recordEntity->date->format($format);

            $workingDays = [];
            for ($a = $daysPerWeek; $a >= 0; $a--) {
                $key = (($start + $a) % 7);
                $workingDays[$key] = $key;
            }

            $daysAbsent = 1;

            // $days = array_diff_key($days, $workingDays);
            // $daysAbsent = 0;

            // $s = clone $recordEntity->start_date;
            // $daysAbsent = 0;
            // do {
            //     if (!in_array($s->format('l'), $days)) {
            //         $daysAbsent++;
            //     }
            //     $s->addDay(1);
            // } while ($s->lte($recordEntity->end_date));

            //POCOR-4864 start
            $title = '';
            $title .=  __($recordEntity->absence_type->name) . ' - ('. $date .')  ' ;//POCOR-4864
            $data=[];
            $data['title']=$title;
            $data['comment']= $StudentAbsenceTableRecord->comment;
            $data['reason']= $StudentAbsenceReasonRecord->name;
            return [$data, true];
            //POCOR-4864 start end
        } catch (RecordNotFoundException $e) {
            return [__('Absence Record Deleted'), false];
        }
    }

    public function onSetCaseRecord(EventInterface $event, ArrayObject $extra)
    {
        $recordId = $extra['record_id'];
        $feature = $extra['feature'];
        $title = $extra['title'];
        $statusId = $extra['status_id'];
        $assigneeId = $extra['assignee_id'];
        $institutionId = $extra['institution_id'];
        $workflowRuleId = $extra['workflow_rule_id'];
        $institutionStudentAbsenceDayId = $this->get($recordId)->institution_student_absence_day_id;

        $recordIds = $this->find()->select([$this->aliasField('id')])->where([$this->aliasField('institution_student_absence_day_id') => $institutionStudentAbsenceDayId])->toArray();

        $linkedRecords = [];

        $records = [];

        foreach ($recordIds as $record) {
            $records[] = $record->id;
            $linkedRecords[] = [
                'record_id' => $record->id,
                'feature' => $feature
            ];
        }
        $InstitutionCases = TableRegistry::getTableLocator()->get('Cases.InstitutionCases');

        $caseData = [
            'case_number' => '',
            'title' => $title,
            'status_id' => $statusId,
            'assignee_id' => $assigneeId,
            'institution_id' => $institutionId,
            'workflow_rule_id' => $workflowRuleId, // required by workflow behavior to get the correct workflow
            'linked_records' => $linkedRecords
        ];

        return $caseData;
    }

    public function onSetLinkedRecordsCheckCondition(EventInterface $event, Query $query, array $where)
    {
        $record = $this->get($where['id']);
        $institutionStudentAbsenceDayId = $record->institution_student_absence_day_id;
        $absentDays = 0;
        if ($institutionStudentAbsenceDayId) {
            $absentDayRecord = $this->InstitutionStudentAbsenceDays->get($institutionStudentAbsenceDayId);
            $absentDays = $absentDayRecord->absent_days;
        }

        if ($where['absence_type_id'] == $record->absence_type_id && $absentDays == $where['days_absent']) {
            return true;
        }

        return false;
    }

    public function onBuildCustomQuery(EventInterface $event, $query)
    {
        $query
            ->select([
                'absent_days' => 'InstitutionStudentAbsenceDays.absent_days',
                'absence_type' => 'AbsenceTypes.name',
                'openemis_no' => 'Users.openemis_no',
                'first_name' => 'Users.first_name',
                'middle_name' => 'Users.middle_name',
                'third_name' => 'Users.third_name',
                'last_name' => 'Users.last_name',
                'preferred_name' => 'Users.preferred_name'
             ])
            ->innerJoinWith('InstitutionCaseRecords.StudentAttendances.Users')
            ->innerJoinWith('InstitutionCaseRecords.StudentAttendances.AbsenceTypes')
            ->innerJoinWith('InstitutionCaseRecords.StudentAttendances.InstitutionStudentAbsenceDays')
            ->group(['WorkflowTransitions.id','InstitutionCaseRecords.institution_case_id']);

        return $query;
    }

    public function onIncludeCustomExcelFields(EventInterface $event, $newFields)
    {
        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.full_name',
            'field' => 'full_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStudentAbsenceDays.absent_days',
            'field' => 'absent_days',
            'type' => 'string',
            'label' => __('Number of Days')
        ];

        $newFields[] = [
            'key' => 'AbsenceTypes.name',
            'field' => 'absence_type',
            'type' => 'string',
            'label' => __('Absence Type')
        ];

        return $newFields;
    }

    public function getSearchableFields(EventInterface $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'student_id';
    }

    // public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    // {
    //     if (isset($data['absence_type_id']) && !empty($data['absence_type_id'])) {
    //         $absenceTypeId = $data['absence_type_id'];
    //         $absenceTypeCode = $this->absenceCodeList[$absenceTypeId];
    //         switch ($absenceTypeCode) {
    //             case 'UNEXCUSED':
    //                 $data['student_absence_reason_id'] = 0;
    //                 break;

    //             case 'LATE':
    //                 $data['full_day'] = 0;
    //                 break;
    //         }
    //     }

    //     if (isset($data['full_day']) && !empty($data['full_day'])) {
    //         $fullDay = $data['full_day'];
    //         if ($fullDay == 1) {
    //             $data['start_time'] = null;
    //             $data['end_time'] = null;
    //         }
    //     }
    // }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        $this->setValidationCode('start_date.ruleNoOverlappingAbsenceDate', 'Institution.Absences');
        $this->setValidationCode('start_date.ruleInAcademicPeriod', 'Institution.Absences');
        $this->setValidationCode('end_date.ruleCompareDateReverse', 'Institution.Absences');
        $this->setValidationCode('end_date.ruleInAcademicPeriod', 'Institution.Absences');


        $codeList = array_flip($this->absenceCodeList);
        $validator->setProvider('custom', $this);
        $validator
            ->add('date', [
                // 'ruleCompareJoinDate' => [
                //     'rule' => ['compareJoinDate', 'student_id'],
                //     'on' => 'create'
                // ],
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'on' => 'create'
                ],
                'checkIfSchoolIsClosed' => [
                    'rule' => function ($value, $context) {
                        $CalendarEventDates = TableRegistry::getTableLocator()->get('CalendarEventDates');
                        $startDate = new Date($context['data']['date']);
                        $endDate = new Date($value);
                        $institutionId = $context['data']['institution_id'];

                        if ($startDate == $endDate) {
                            $isSchoolClosed = $CalendarEventDates->isSchoolClosed($startDate, $institutionId);
                            if ($isSchoolClosed) {
                                $message = __('School closed on this date');
                                return $message;
                            } else {
                                return true;
                            }
                        } else {
                            $endDate = $endDate->modify('+1 day');
                            $interval = new DateInterval('P1D');

                            $datePeriod = new DatePeriod($startDate, $interval, $endDate);

                            $records = [];
                            foreach ($datePeriod as $key => $date) {
                                $isSchoolClosed = $CalendarEventDates->isSchoolClosed($date, $institutionId);
                                if ($isSchoolClosed) {
                                    $records[$date->format('d-m-Y')] = 'closed';
                                } else {
                                    $records[$date->format('d-m-Y')] = 'open';
                                }
                            }

                            if (in_array('closed', $records)) {
                                $message = __('Some dates fall on school closed');
                                return $message;
                            } else {
                                return true;
                            }
                        }
                    }
                ]
            ]);
            // ->add('start_date', [
            //     'ruleCompareJoinDate' => [
            //         'rule' => ['compareJoinDate', 'student_id'],
            //         'on' => 'create'
            //     ],
            //     'ruleNoOverlappingAbsenceDate' => [
            //         'rule' => ['noOverlappingAbsenceDate', $this]
            //     ],
            //     'ruleInAcademicPeriod' => [
            //         'rule' => ['inAcademicPeriod', 'academic_period_id', []],
            //         'on' => 'create'
            //     ]
            // ])
            // ->add('end_date', [
            //     'ruleCompareJoinDate' => [
            //         'rule' => ['compareJoinDate', 'student_id'],
            //         'on' => 'create'
            //     ],
            //     'ruleCompareDateReverse' => [
            //         'rule' => ['compareDateReverse', 'start_date', true]
            //     ],
            //     'ruleInAcademicPeriod' => [
            //         'rule' => ['inAcademicPeriod', 'academic_period_id', []],
            //         'on' => 'create'
            //     ],
            //     'checkIfSchoolIsClosed' => [
            //         'rule' => function ($value, $context) {
            //             $CalendarEventDates = TableRegistry::getTableLocator()->get('CalendarEventDates');
            //             $startDate = new Date($context['data']['start_date']);
            //             $endDate = new Date($value);

            //             if ($startDate == $endDate) {
            //                 $isSchoolClosed = $CalendarEventDates->isSchoolClosed($startDate);
            //                 if ($isSchoolClosed) {
            //                     $message = __('School closed on this date');
            //                     return $message;
            //                 } else {
            //                     return true;
            //                 }
            //             } else {
            //                 $endDate = $endDate->modify('+1 day');
            //                 $interval = new DateInterval('P1D');

            //                 $datePeriod = new DatePeriod($startDate, $interval, $endDate);

            //                 $records = [];
            //                 foreach ($datePeriod as $key => $date) {
            //                     $isSchoolClosed = $CalendarEventDates->isSchoolClosed($date);
            //                     if ($isSchoolClosed) {
            //                         $records[$date->format('d-m-Y')] = 'closed';
            //                     } else {
            //                         $records[$date->format('d-m-Y')] = 'open';
            //                     }
            //                 }

            //                 if (in_array('closed', $records)) {
            //                     $message = __('Some dates fall on school closed');
            //                     return $message;
            //                 } else {
            //                     return true;
            //                 }
            //             }
            //         }
            //     ],
            // ])
            // ->allowEmpty('start_time', function ($context) {
            //     if (array_key_exists('full_day', $context['data'])) {
            //         return $context['data']['full_day'];
            //     }
            //     return false;
            // })
            // ->requirePresence('start_time', function ($context) {
            //     if (array_key_exists('full_day', $context['data'])) {
            //         return !$context['data']['full_day'];
            //     }
            //     return false;
            // })
            // ->add('start_time', [
            //     'ruleInInstitutionShift' => [
            //         'rule' => ['inInstitutionShift', 'academic_period_id'],
            //         'on' => 'create'
            //     ]
            // ])
            // ->allowEmpty('end_time', function ($context) {
            //     if (array_key_exists('full_day', $context['data'])) {
            //         return $context['data']['full_day'];
            //     }
            //     return false;
            // })
            // ->requirePresence('end_time', function ($context) {
            //     if (array_key_exists('full_day', $context['data'])) {
            //         return !$context['data']['full_day'];
            //     }
            //     return false;
            // })
            // ->add('end_time', [
            //     'ruleCompareAbsenceTimeReverse' => [
            //         'rule' => ['compareAbsenceTimeReverse', 'start_time', true]
            //     ],
            //     'ruleInInstitutionShift' => [
            //         'rule' => ['inInstitutionShift', 'academic_period_id'],
            //         'on' => 'create'
            //     ]
            // ])
            ;
        return $validator;
    }

    public function onGetSecurityUserId(EventInterface $event, Entity $entity)
    {
        if (isset($entity->user->name_with_id)) {
            return $entity->user->name_with_id;
        }
    }

    public function onGetFullday(EventInterface $event, Entity $entity)
    {
        $fullDayOptions = $this->getSelectOptions('general.yesno');
        return $fullDayOptions[$entity->full_day];
    }

    public function onGetAbsenceTypeId(EventInterface $event, Entity $entity)
    {
        return __($entity->absence_type->name);
    }

    // public function onGetStudentAbsenceReasonId(EventInterface $event, Entity $entity)
    // {
    //     if ($entity->student_absence_reason_id == 0) {
    //         return '<i class="fa fa-minus"></i>';
    //     }
    // }

    public function onGetStudentId(EventInterface $event, Entity $entity)
    {
        if (isset($entity->user->name_with_id)) {
            if ($this->action == 'view') {
                return $event->subject()->Html->link($entity->user->name_with_id, [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'StudentUser',
                    'view',
                    $this->paramsEncode(['id' => $entity->user->id])
                ]);
            } else {
                return $entity->user->name_with_id;
            }
        }
    }

    public function afterAction(EventInterface $event)
    {
        $this->setFieldOrder($this->_fieldOrder);
        $this->fields['institution_student_absence_day_id']['visible'] = false;
    }

    public function indexBeforeAction(EventInterface $event)
    {
        $absenceTypeOptions = $this->absenceList;

        $this->field('date');
        $this->field('absence_type_id', [
            'options' => $absenceTypeOptions
        ]);

        $this->fields['student_id']['sort'] = ['field' => 'Users.first_name']; // POCOR-2547 adding sort
        $this->fields['full_day']['visible'] = false;
        $this->fields['start_date']['visible'] = false;
        $this->fields['end_date']['visible'] = false;
        $this->fields['start_time']['visible'] = false;
        $this->fields['end_time']['visible'] = false;
        $this->fields['comment']['visible'] = false;

        // $this->_fieldOrder = ['date', 'student_id', 'absence_type_id', 'student_absence_reason_id'];
        $this->_fieldOrder = ['date', 'student_id', 'absence_type_id'];
    }

    public function viewAfterAction(EventInterface $event, Entity $entity)
    {
        // Temporary fix for error on view page
        unset($this->_fieldOrder[1]); // Academic period not in use in view page
        unset($this->_fieldOrder[2]); // Class not in use in view page
        $this->setFieldOrder($this->_fieldOrder);
        // End fix

        $absenceTypeOptions = $this->absenceList;
        $this->field('absence_type_id', [
            'options' => $absenceTypeOptions
        ]);

        if ($entity->full_day == 1) {
            $this->fields['start_time']['visible'] = false;
            $this->fields['end_time']['visible'] = false;
        }
    }

    //POCOR-9594-1: per-request cache — value is constant per request
    private ?int $_dailyAttendanceConfig = null;

    public function institutionStudentRiskCalculateRiskValue(EventInterface $event, ArrayObject $params)
    {
        $institutionId    = $params['institution_id'];
        $studentId        = $params['student_id'];
        $academicPeriodId = $params['academic_period_id'];

        $period      = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')->get($academicPeriodId);
        $startDate   = $period->start_date->format('Y-m-d');
        $endDate     = $period->end_date->format('Y-m-d');
        $absenceTypeId = TableRegistry::getTableLocator()->get('Risk.Risks')
            ->getCriteriasDetails($params['criteria_name'])['absence_type_id'];
        $dailyConfig = $this->getDailyAttendanceConfig();

        //POCOR-9594-1 --start
        // Attendance is now recorded in institution_student_absence_details
        // (period-by-period marking), not institution_student_absences - counting
        // from $this (the old table) always returned 0 for any student whose
        // attendance was recorded after the move, so the threshold check below
        // never fired and Risk > View stayed blank.
        //
        // calculate_daily_attendance governs how period-level absences roll up
        // into a "day counts as absent" decision:
        //  - 1 (once): any single absent period on a date counts that whole day
        //  - 2 (all):  a day only counts if the number of absent periods meets
        //    the per-grade threshold (attendance_per_day) active for that date
        if ($dailyConfig == 1) {
            $absenceResultsCount = $this->countAbsentDaysOnce($institutionId, $studentId, $absenceTypeId, $startDate, $endDate);
        } else {
            $gradeId          = $this->getStudentGradeFromAbsences($studentId, $institutionId, $academicPeriodId);
            $attendancePerDay = $this->getMinAttendancePerDay($gradeId, $academicPeriodId, $startDate, $endDate);
            $absenceResultsCount = $this->countAbsentDaysAll($institutionId, $studentId, $absenceTypeId, $startDate, $endDate, $attendancePerDay);
        }
        //POCOR-9594-1 --end

        return $absenceResultsCount;
    }

    //POCOR-9594-1 --start
    // config=1 — any period absence on a date counts that day
    private function countAbsentDaysOnce(int $institutionId, int $studentId, int $absenceTypeId, string $startDate, string $endDate): int
    {
        $row = \Cake\Datasource\ConnectionManager::get('default')->execute(
            'SELECT COUNT(DISTINCT date) AS absent_days
             FROM institution_student_absence_details
             WHERE institution_id = ? AND student_id = ? AND absence_type_id = ?
               AND subject_id = 0 AND date >= ? AND date <= ?',
            [$institutionId, $studentId, $absenceTypeId, $startDate, $endDate]
        )->fetch('assoc');
        return (int)($row['absent_days'] ?? 0);
    }

    // config=2 — day counts only when period absences >= attendancePerDay
    private function countAbsentDaysAll(int $institutionId, int $studentId, int $absenceTypeId, string $startDate, string $endDate, int $attendancePerDay): int
    {
        $row = \Cake\Datasource\ConnectionManager::get('default')->execute(
            'SELECT COUNT(*) AS absent_days
             FROM (
                 SELECT date
                 FROM institution_student_absence_details
                 WHERE institution_id = ? AND student_id = ? AND absence_type_id = ?
                   AND subject_id = 0 AND date >= ? AND date <= ?
                 GROUP BY date
                 HAVING COUNT(*) >= ?
             ) AS qualifying_dates',
            [$institutionId, $studentId, $absenceTypeId, $startDate, $endDate, $attendancePerDay]
        )->fetch('assoc');
        return (int)($row['absent_days'] ?? 0);
    }

    // MIN across overlapping DAY-type mark types active in the date window
    // (date_enabled/date_disabled can overlap — MIN = stricter: lower threshold = more days qualify)
    private function getMinAttendancePerDay(?int $gradeId, int $academicPeriodId, string $startDate, string $endDate): int
    {
        if (!$gradeId) {
            return 1;
        }
        $row = \Cake\Datasource\ConnectionManager::get('default')->execute(
            'SELECT MIN(smt.attendance_per_day) AS min_apd
             FROM student_attendance_mark_types smt
             JOIN student_attendance_types sat ON sat.id = smt.student_attendance_type_id
             JOIN student_mark_type_statuses smts ON smts.student_attendance_mark_type_id = smt.id
             JOIN student_mark_type_status_grades smtsg ON smtsg.student_mark_type_status_id = smts.id
             WHERE smtsg.education_grade_id = ? AND smts.academic_period_id = ?
               AND sat.code = ?
               AND smts.date_enabled <= ? AND smts.date_disabled >= ?',
            [$gradeId, $academicPeriodId, 'DAY', $endDate, $startDate]
        )->fetch('assoc');
        return max(1, (int)($row['min_apd'] ?? 1));
    }

    // read education_grade_id from the absence records themselves — correct even when
    // a class spans multiple grades, since each absence row carries its own grade
    private function getStudentGradeFromAbsences(int $studentId, int $institutionId, int $academicPeriodId): ?int
    {
        $row = \Cake\Datasource\ConnectionManager::get('default')->execute(
            'SELECT education_grade_id
             FROM institution_student_absence_details
             WHERE student_id = ? AND institution_id = ? AND academic_period_id = ?
             ORDER BY date DESC LIMIT 1',
            [$studentId, $institutionId, $academicPeriodId]
        )->fetch('assoc');
        return $row ? (int)$row['education_grade_id'] : null;
    }

    // read calculate_daily_attendance from config (1=once, 2=all periods); cached per instance
    private function getDailyAttendanceConfig(): int
    {
        if ($this->_dailyAttendanceConfig === null) {
            $this->_dailyAttendanceConfig = (int)(TableRegistry::getTableLocator()->get('Configuration.ConfigItems')
                ->find()
                ->select(['value'])
                ->where(['code' => 'calculate_daily_attendance'])
                ->first()['value'] ?? 1);
        }
        return $this->_dailyAttendanceConfig;
    }
    //POCOR-9594-1 --end

    public function getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName)
    {
        $period      = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')->get($academicPeriodId);
        $startDate   = $period->start_date->format('Y-m-d');
        $endDate     = $period->end_date->format('Y-m-d');
        $absenceTypeId = TableRegistry::getTableLocator()->get('Risk.Risks')
            ->getCriteriasDetails($criteriaName)['absence_type_id'];
        $dateFormat  = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value('date_format');
        $dailyConfig = $this->getDailyAttendanceConfig();

        //POCOR-9594-1 --start
        // Same table move + same calculate_daily_attendance awareness as
        // institutionStudentRiskCalculateRiskValue() above — this feeds the
        // "reference" column shown directly on the Risk View page, so it needs
        // to agree with which dates actually triggered the risk value.
        $gradeId          = $this->getStudentGradeFromAbsences($studentId, $institutionId, $academicPeriodId);
        $attendancePerDay = $dailyConfig == 1
            ? 1
            : $this->getMinAttendancePerDay($gradeId, $academicPeriodId, $startDate, $endDate);

        $qualifyingDates = $this->getQualifyingAbsentDates(
            $institutionId, $studentId, $absenceTypeId, $startDate, $endDate, $attendancePerDay
        );

        $reference = '';
        foreach ($qualifyingDates as $date) {
            $reference .= ' (' . (new \Cake\I18n\Date($date))->format($dateFormat) . ') <br/>';
        }
        return $reference;
        //POCOR-9594-1 --end
    }

    //POCOR-9594-1: returns sorted qualifying absent dates — dates where period absence count >= attendancePerDay
    private function getQualifyingAbsentDates(int $institutionId, int $studentId, int $absenceTypeId, string $startDate, string $endDate, int $attendancePerDay): array
    {
        $rows = \Cake\Datasource\ConnectionManager::get('default')->execute(
            'SELECT date
             FROM institution_student_absence_details
             WHERE institution_id = ? AND student_id = ? AND absence_type_id = ?
               AND subject_id = 0 AND date >= ? AND date <= ?
             GROUP BY date
             HAVING COUNT(*) >= ?
             ORDER BY date ASC',
            [$institutionId, $studentId, $absenceTypeId, $startDate, $endDate, $attendancePerDay]
        )->fetchAll('assoc');

        return array_column($rows, 'date');
    }

    public function getModelAlertData($threshold)
    {
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $currentAcademicPeriodId = $AcademicPeriods->getCurrent();
        $currentPeriod = $AcademicPeriods->get($currentAcademicPeriodId);

        // Fetch raw data grouped by absence record, including the `date`
        $rawAbsences = $this->find()
            ->select([
                'institution_id',
                'student_id',
                'absence_type_id',
                'date',
                'Institutions.id',
                'Institutions.name',
                'Institutions.code',
                'Institutions.address',
                'Institutions.postal_code',
                'Institutions.contact_person',
                'Institutions.telephone',
                'Institutions.email',
                'Institutions.website',
                'Users.id',
                'Users.openemis_no',
                'Users.first_name',
                'Users.middle_name',
                'Users.third_name',
                'Users.last_name',
                'Users.preferred_name',
                'Users.email',
                'Users.address',
                'Users.postal_code',
                'Users.date_of_birth',
                'Users.identity_number',
                'Users.photo_name',
                'Users.photo_content',
                'MainNationalities.name',
                'MainIdentityTypes.name',
                'Genders.name'
            ])
            ->contain(['Institutions', 'Users', 'Users.Genders', 'Users.MainNationalities', 'Users.MainIdentityTypes'])
            ->matching('AbsenceTypes', function ($q) {
                return $q->where([
                    'code' => 'UNEXCUSED'
                ]);
            })
            ->where([
                'date >=' => $currentPeriod->start_date->format('Y-m-d'),
                'date <=' => $currentPeriod->end_date->format('Y-m-d'),
            ])
            ->disableHydration()
            ->toArray();

        // Group the results in PHP
        $grouped = [];

        foreach ($rawAbsences as $row) {
            $key = $row['institution_id'] . '_' . $row['student_id'] . '_' . $row['absence_type_id'];

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'institution_id' => $row['institution_id'],
                    'student_id' => $row['student_id'],
                    'absence_type_id' => $row['absence_type_id'],
                    'total_times' => 0,
                    'dates' => [],
                    'institution' => $row['Institutions'],
                    'user' => $row['Users'],
                    'gender' => $row['Genders']['name'] ?? null,
                    'nationality' => $row['MainNationalities']['name'] ?? null,
                    'identity_type' => $row['MainIdentityTypes']['name'] ?? null,
                ];
            }

            $grouped[$key]['total_times'] += 1;
            $grouped[$key]['dates'][$row['date']] = true;
        }

        // Final result: count unique days
        $finalResults = [];

        foreach ($grouped as $item) {
            $item['total_days'] = count($item['dates']);
            unset($item['dates']); // clean up

            if ($item['total_times'] >= $threshold) {
                $finalResults[] = $item;
            }
        }

        return $finalResults;
    }
}
