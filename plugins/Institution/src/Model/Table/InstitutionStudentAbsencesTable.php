<?php
namespace Institution\Model\Table;

use ArrayObject;
use DatePeriod;
use DateInterval;
use Cake\I18n\Date;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
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

    public function initialize(array $config)
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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.InstitutionStudentRisks.calculateRiskValue'] = 'institutionStudentRiskCalculateRiskValue';
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        $events['InstitutionCase.onSetCustomCaseTitle'] = 'onSetCustomCaseTitle';
        $events['InstitutionCase.onSetLinkedRecordsCheckCondition'] = 'onSetLinkedRecordsCheckCondition';
        $events['InstitutionCase.onSetCustomCaseSummary'] = 'onSetCustomCaseSummary';
        $events['InstitutionCase.onSetCaseRecord'] = 'onSetCaseRecord';
        $events['Model.afterSaveCommit'] = ['callable' => 'afterSaveCommit', 'priority' => '9'];
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

    public function viewBeforeAction(Event $event, ArrayObject $extra)
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

    public function getWorkflowRuleEvents(Event $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowRuleEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function onAssignToHomeRoomTeacher(Event $event, Entity $caseEntity, Entity $linkedRecordEntity, ArrayObject $extra)
    {
        $Students = TableRegistry::get('Institution.Students');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $Cases = TableRegistry::get('Cases.InstitutionCases');

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

    public function onAssignToSecondaryTeacher(Event $event, Entity $caseEntity, Entity $linkedRecordEntity, ArrayObject $extra)
    {
        $Students = TableRegistry::get('Institution.Students');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $ClassesSecondaryStaff = TableRegistry::get('Institution.InstitutionClassesSecondaryStaff');
        $Cases = TableRegistry::get('Cases.InstitutionCases');

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

    public function onAssignToPrincipal(Event $event, Entity $caseEntity, Entity $linkedRecordEntity, ArrayObject $extra)
    {
        $InstitutionPositions = TableRegistry::get('Institution.InstitutionPositions');
        $Cases = TableRegistry::get('Cases.InstitutionCases');

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
    
    public function onAssignToMoeadmin(Event $event, Entity $caseEntity, Entity $linkedRecordEntity, ArrayObject $extra)
    {
        $InstitutionPositions = TableRegistry::get('Institution.InstitutionPositions');
        $Cases = TableRegistry::get('Cases.InstitutionCases');

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

        $start = TableRegistry::get('Configuration.ConfigItems')->value('first_day_of_week');
        $daysPerWeek = TableRegistry::get('Configuration.ConfigItems')->value('days_per_week') - 1;
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
                $InstitutionStudentAbsenceDays->aliasField('absence_type_id') => $entity->absence_type_id,
                $InstitutionStudentAbsenceDays->aliasField('student_id') => $entity->student_id,
                $InstitutionStudentAbsenceDays->aliasField('institution_id') => $entity->institution_id
            ])
            ->order([$InstitutionStudentAbsenceDays->aliasField('start_date')]);
        $count = $consecutiveRecords->count();

        switch ($count) {
            // There is no record, we will add the entry
            case 0:
                $s = clone $startDate;
                $daysAbsent = 0;
                do {
                    if (!in_array($s->format('l'), $days)) {
                        $daysAbsent++;
                    }
                    $s->addDay(1);
                } while ($s->lte($endDate));

                $dayEntity = $InstitutionStudentAbsenceDays->newEntity([
                    'student_id' => $entity->student_id,
                    'institution_id' => $entity->institution_id,
                    'absence_type_id' => $entity->absence_type_id,
                    'absent_days' => $daysAbsent,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);

                $dayEntity = $InstitutionStudentAbsenceDays->save($dayEntity);
                $this->updateAll(['institution_student_absence_day_id' => $dayEntity->id], ['id' => $entity->id]);
                break;
            // When there is one record found
            case 1:
                $recordEntity = $consecutiveRecords->first();
                $recordStartDate = $recordEntity->start_date;
                $recordEndDate = $recordEntity->end_date;

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
                } while ($s->lte($recordEndDate));

                $dayEntity = $InstitutionStudentAbsenceDays->patchEntity($recordEntity, [
                    'start_date' => $recordStartDate,
                    'end_date' => $recordEndDate,
                    'absent_days' => $daysAbsent
                ]);
                $dayEntity = $InstitutionStudentAbsenceDays->save($dayEntity);
                $this->updateAll(['institution_student_absence_day_id' => $dayEntity->id], ['id' => $entity->id]);
                break;
            // When there is two records found, it means this record happen to fall in between the two record
            case 2:
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
                } while ($s->lte($recordEndDate));

                $dayEntity = $InstitutionStudentAbsenceDays->newEntity([
                    'student_id' => $entity->student_id,
                    'institution_id' => $entity->institution_id,
                    'absence_type_id' => $entity->absence_type_id,
                    'absent_days' => $daysAbsent,
                    'start_date' => $recordStartDate,
                    'end_date' => $recordEndDate
                ]);
                $dayEntity = $InstitutionStudentAbsenceDays->save($dayEntity);
                $this->updateAll(['institution_student_absence_day_id' => $dayEntity->id], ['institution_student_absence_day_id IN ' => $recordsId]);
                $this->updateAll(['institution_student_absence_day_id' => $dayEntity->id], ['id' => $entity->id]);
                break;
        }
    }

    public function afterSaveCommit(Event $event, Entity $entity, ArrayObject $options)
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

        if ($entity->isNew()) {
            $this->addInstitutionStudentAbsenceDayRecord($entity, $startDate, $endDate);
        }
    }

    public function onSetCustomCaseTitle(Event $event, Entity $entity)
    {
        $recordEntity = $this->get($entity->id, [
            'contain' => ['Users', 'AbsenceTypes', 'Institutions']
        ]);
        $title = '';
        $title .= $recordEntity->user->name.' '.__('from').' '.$recordEntity->institution->code_name.' '.__('with').' '.$recordEntity->absence_type->name;

        return $title;
    }

    public function onSetFilterToolbarElement(Event $event, ArrayObject $params, $institutionId)
    {
       
        $requestQuery = $params['query'];
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $InstitutionEducationGrades = TableRegistry::get('Institution.InstitutionGrades');

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
            $allGradeList = ["all" => 'All'];
            $educationGradesOptions = $allGradeList + $gradeList;
            
        } else {
            $educationGradesOptions = ['-1' => __('No Grades')];
        }

        // institution_class_id
        if (empty($requestQuery['institution_class_id'])) {
           
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
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
                $requestQuery['institution_class_id'] = $firstInstitutionClassIdResult->id;
            } else {
                $requestQuery['institution_class_id'] = -1;
            }
        }

        if ($selectedEducationGrades != -1) {
            
            $selectedClassId = $requestQuery['institution_class_id'];
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            
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
               
                $institutionClassOptions = $classList;
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

    public function onCaseIndexBeforeQuery(Event $event, $requestQuery, Query $query)
    {
        
        if (array_key_exists('institution_class_id', $requestQuery) && $requestQuery['institution_class_id'] != -1) {
            $institutionClassId = $requestQuery['institution_class_id'];
            $educationGradeId = $requestQuery['education_grade_id'];
            
            $academicPeriodId = $requestQuery['academic_period_id'];

            $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');

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
                    $InstitutionClassStudents->aliasField('institution_class_id') => $institutionClassId,
                    $InstitutionClassStudents->aliasField('education_grade_id') => $educationGradeId,
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

    public function onSetCustomCaseSummary(Event $event, int $id)
    {
        try {
            $recordEntity = $this->get($id, [
                'contain' => ['Users', 'AbsenceTypes', 'Institutions']
            ]);
            $days = [
                0 => 'Sunday',
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday'
            ];
            $start = TableRegistry::get('Configuration.ConfigItems')->value('first_day_of_week');
            $daysPerWeek = TableRegistry::get('Configuration.ConfigItems')->value('days_per_week') - 1;

            $ConfigItem = TableRegistry::get('Configuration.ConfigItems');
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


            $title = '';
            $title .= $recordEntity->user->name.' '.__('from').' '.$recordEntity->institution->code_name.' '.__('with').' '.__($recordEntity->absence_type->name) . ' - ('. $date .') - ' . __('Days Absent') . ': ' . $daysAbsent;

            return [$title, true];
        } catch (RecordNotFoundException $e) {
            return [__('Absence Record Deleted'), false];
        }
    }

    public function onSetCaseRecord(Event $event, ArrayObject $extra)
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
        $InstitutionCases = TableRegistry::get('Cases.InstitutionCases');

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

    public function onSetLinkedRecordsCheckCondition(Event $event, Query $query, array $where)
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

    public function onBuildCustomQuery(Event $event, $query)
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

    public function onIncludeCustomExcelFields(Event $event, $newFields)
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

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'student_id';
    }

    // public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    // {
    //     if (array_key_exists('absence_type_id', $data) && !empty($data['absence_type_id'])) {
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

    //     if (array_key_exists('full_day', $data) && !empty($data['full_day'])) {
    //         $fullDay = $data['full_day'];
    //         if ($fullDay == 1) {
    //             $data['start_time'] = null;
    //             $data['end_time'] = null;
    //         }
    //     }
    // }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $this->setValidationCode('start_date.ruleNoOverlappingAbsenceDate', 'Institution.Absences');
        $this->setValidationCode('start_date.ruleInAcademicPeriod', 'Institution.Absences');
        $this->setValidationCode('end_date.ruleCompareDateReverse', 'Institution.Absences');
        $this->setValidationCode('end_date.ruleInAcademicPeriod', 'Institution.Absences');


        $codeList = array_flip($this->absenceCodeList);
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
                        $CalendarEventDates = TableRegistry::get('CalendarEventDates');
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
            //             $CalendarEventDates = TableRegistry::get('CalendarEventDates');
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

    public function onGetSecurityUserId(Event $event, Entity $entity)
    {
        if (isset($entity->user->name_with_id)) {
            return $entity->user->name_with_id;
        }
    }

    public function onGetFullday(Event $event, Entity $entity)
    {
        $fullDayOptions = $this->getSelectOptions('general.yesno');
        return $fullDayOptions[$entity->full_day];
    }

    public function onGetAbsenceTypeId(Event $event, Entity $entity)
    {
        return __($entity->absence_type->name);
    }

    // public function onGetStudentAbsenceReasonId(Event $event, Entity $entity)
    // {
    //     if ($entity->student_absence_reason_id == 0) {
    //         return '<i class="fa fa-minus"></i>';
    //     }
    // }

    public function onGetStudentId(Event $event, Entity $entity)
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

    public function afterAction(Event $event)
    {
        $this->setFieldOrder($this->_fieldOrder);
        $this->fields['institution_student_absence_day_id']['visible'] = false;
    }

    public function indexBeforeAction(Event $event)
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

    public function viewAfterAction(Event $event, Entity $entity)
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

    public function institutionStudentRiskCalculateRiskValue(Event $event, ArrayObject $params)
    {
        $institutionId = $params['institution_id'];
        $studentId = $params['student_id'];
        $academicPeriodId = $params['academic_period_id'];

        $Indexes = TableRegistry::get('Risk.Risks');
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodStartDate = $AcademicPeriod->get($academicPeriodId)->start_date;
        $academicPeriodEndDate = $AcademicPeriod->get($academicPeriodId)->end_date;

        $absenceTypeId = $Indexes->getCriteriasDetails($params['criteria_name'])['absence_type_id'];

        $absenceResultsCount = $this
            ->find()
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('absence_type_id') => $absenceTypeId,
                $this->aliasField('date') . ' >='  => $academicPeriodStartDate,
                $this->aliasField('date') . ' <='  => $academicPeriodEndDate
            ])
            ->count();

        return $absenceResultsCount;
    }

    public function getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName)
    {
        $Indexes = TableRegistry::get('Risk.Risks');
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $dateFormat = $ConfigItems->value('date_format');
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodStartDate = $AcademicPeriod->get($academicPeriodId)->start_date;
        $academicPeriodEndDate = $AcademicPeriod->get($academicPeriodId)->end_date;
        $absenceTypeId = $Indexes->getCriteriasDetails($criteriaName)['absence_type_id'];

        $absenceResults = $this
            ->find()
            // ->contain(['AbsenceTypes', 'StudentAbsenceReasons'])
            ->contain(['AbsenceTypes'])
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('absence_type_id') => $absenceTypeId,
                $this->aliasField('date') . ' >='  => $academicPeriodStartDate,
                $this->aliasField('date') . ' <='  => $academicPeriodEndDate
            ])
            ->all();

        $referenceDetails = [];
        foreach ($absenceResults as $key => $obj) {

            $referenceDetails[$obj->id] = ' (' . $obj->date->format($dateFormat) . ')';
        }

        // tooltip only receieved string to be display
        $reference = '';
        foreach ($referenceDetails as $key => $referenceDetailsObj) {
            $reference = $reference . $referenceDetailsObj . ' <br/>';
        }

        return $reference;
    }

    public function getModelAlertData($threshold)
    {
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $currentAcademicPeriodId = $AcademicPeriods->getCurrent();
        $currentPeriodStartDate = $AcademicPeriods->get($currentAcademicPeriodId)->start_date;
        $currentPeriodEndDate = $AcademicPeriods->get($currentAcademicPeriodId)->end_date;

        // will do the comparison with threshold when retrieving the absence data
        $unexcusedAbsenceResults = $this->find()
            ->select([
                'total_days' => $this->find()->func()->count('*'),
                'Institutions.id',
                'Institutions.name',
                'Institutions.code',
                'Institutions.address',
                'Institutions.postal_code',
                'Institutions.contact_person',
                'Institutions.telephone',
                'Institutions.fax',
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
                'date' . ' >='  => $currentPeriodStartDate->format('Y-m-d'),
                'date' . ' <='  => $currentPeriodEndDate->format('Y-m-d'),
            ])
            ->group(['institution_id', 'student_id', 'absence_type_id'])
            ->having(['total_days >= ' => $threshold])
            ->hydrate(false)
            ;

        return $unexcusedAbsenceResults->toArray();
    }
}
