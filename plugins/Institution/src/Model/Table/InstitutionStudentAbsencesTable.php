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
    private $_fieldOrder = [
        'absence_type_id', 'academic_period_id', 'class', 'student_id',
        'full_day', 'start_date', 'end_date', 'start_time', 'end_time',
        'student_absence_reason_id'
    ];
    private $absenceList;
    private $absenceCodeList;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Institution.Absence');

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('StudentAbsenceReasons', ['className' => 'Institution.StudentAbsenceReasons']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
        $this->belongsTo('InstitutionStudentAbsenceDays', ['className' => 'Institution.InstitutionStudentAbsenceDays', 'foreignKey' =>'institution_student_absence_day_id']);
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('Excel', [
            'excludes' => [
                'start_year',
                'end_year',
                'institution_id',
                'student_id',
                'full_day',
                'start_date',
                'start_time',
                'end_time',
                'end_date'
            ],
            'pages' => ['index']
        ]);
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
        return $events;
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
        $InstitutionStudentAbsenceDays = $this->InstitutionStudentAbsenceDays;
        $startDate = $entity->start_date;
        $endDate = $entity->end_date;
        $fullDay = $entity->full_day;

        if ($fullDay && $entity->isNew()) {
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
            $requestQuery['education_grade_id'] = -1;
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
            $gradeList = $result->toArray();
            $educationGradesOptions = ['-1' => __('-- Select Grade --')] + $gradeList;
        } else {
            $educationGradesOptions = ['-1' => __('No Grades')];
        }

        // institution_class_id
        if (empty($requestQuery['institution_class_id'])) {
            $requestQuery['institution_class_id'] = -1;
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
                $institutionClassOptions = ['-1' => __('-- Select Class --')] + $classList;
            } else {
                $institutionClassOptions = ['-1' => __('No Classes')];
            }
        } else {
            $selectedClassId = -1;
            $institutionClassOptions = ['-1' => __('-- Select Class --')];
        }

        $params['element'] = ['filter' => ['name' => 'Cases.StudentAbsences/controls', 'order' => 2]];
        $params['query'] = $requestQuery;
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
                        $this->aliasField('start_date >= ') => $startDate,
                        $this->aliasField('start_date <= ') => $endDate
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
            $startDate = $recordEntity->start_date->format($format);
            $endDate = $recordEntity->end_date->format($format);

            $workingDays = [];
            for ($a = $daysPerWeek; $a >= 0; $a--) {
                $key = (($start + $a) % 7);
                $workingDays[$key] = $key;
            }

            $days = array_diff_key($days, $workingDays);
            $daysAbsent = 0;

            $s = clone $recordEntity->start_date;
            $daysAbsent = 0;
            do {
                if (!in_array($s->format('l'), $days)) {
                    $daysAbsent++;
                }
                $s->addDay(1);
            } while ($s->lte($recordEntity->end_date));


            $title = '';
            $title .= $recordEntity->user->name.' '.__('from').' '.$recordEntity->institution->code_name.' '.__('with').' '.__($recordEntity->absence_type->name) . ' - ('.$startDate.' - '. $endDate .') - ' . __('Days Absent') . ': ' . $daysAbsent;

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

        $newData = [
            'case_number' => '',
            'title' => $title,
            'status_id' => $statusId,
            'assignee_id' => $assigneeId,
            'institution_id' => $institutionId,
            'workflow_rule_id' => $workflowRuleId, // required by workflow behavior to get the correct workflow
            'linked_records' => $linkedRecords
        ];

        $patchOptions = ['validate' => false];

        $newEntity = $InstitutionCases->newEntity($newData, $patchOptions);
        return $InstitutionCases->save($newEntity);
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

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists('absence_type_id', $data) && !empty($data['absence_type_id'])) {
            $absenceTypeId = $data['absence_type_id'];
            $absenceTypeCode = $this->absenceCodeList[$absenceTypeId];
            switch ($absenceTypeCode) {
                case 'UNEXCUSED':
                    $data['student_absence_reason_id'] = 0;
                    break;

                case 'LATE':
                    $data['full_day'] = 0;
                    break;
            }
        }

        if (array_key_exists('full_day', $data) && !empty($data['full_day'])) {
            $fullDay = $data['full_day'];
            if ($fullDay == 1) {
                $data['start_time'] = null;
                $data['end_time'] = null;
            }
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $query
            ->where([$this->aliasField('institution_id') => $institutionId])
            ->select(['openemis_no' => 'Users.openemis_no']);
    }

    // To select another one more field from the containable data
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newArray = [];
        $newArray[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'InstitutionStudentAbsences.student_id',
            'field' => 'student_id',
            'type' => 'integer',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'InstitutionStudentAbsences.absences',
            'field' => 'absences',
            'type' => 'string',
            'label' => __('Absences')
        ];
        $newFields = array_merge($newArray, $fields->getArrayCopy());
        $fields->exchangeArray($newFields);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $this->setValidationCode('start_date.ruleNoOverlappingAbsenceDate', 'Institution.Absences');
        $this->setValidationCode('start_date.ruleInAcademicPeriod', 'Institution.Absences');
        $this->setValidationCode('end_date.ruleCompareDateReverse', 'Institution.Absences');
        $this->setValidationCode('end_date.ruleInAcademicPeriod', 'Institution.Absences');


        $codeList = array_flip($this->absenceCodeList);
        $validator
            ->add('start_date', [
                'ruleCompareJoinDate' => [
                    'rule' => ['compareJoinDate', 'student_id'],
                    'on' => 'create'
                ],
                'ruleNoOverlappingAbsenceDate' => [
                    'rule' => ['noOverlappingAbsenceDate', $this]
                ],
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'on' => 'create'
                ]
            ])
            ->add('end_date', [
                'ruleCompareJoinDate' => [
                    'rule' => ['compareJoinDate', 'student_id'],
                    'on' => 'create'
                ],
                'ruleCompareDateReverse' => [
                    'rule' => ['compareDateReverse', 'start_date', true]
                ],
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'on' => 'create'
                ],
                'checkIfSchoolIsClosed' => [
                    'rule' => function ($value, $context) {
                        $CalendarEventDates = TableRegistry::get('CalendarEventDates');
                        $startDate = new Date($context['data']['start_date']);
                        $endDate = new Date($value);

                        if ($startDate == $endDate) {
                            $isSchoolClosed = $CalendarEventDates->isSchoolClosed($startDate);
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
                                $isSchoolClosed = $CalendarEventDates->isSchoolClosed($date);
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
                ],
            ])
            ->allowEmpty('start_time', function ($context) {
                if (array_key_exists('full_day', $context['data'])) {
                    return $context['data']['full_day'];
                }
                return false;
            })
            ->requirePresence('start_time', function ($context) {
                if (array_key_exists('full_day', $context['data'])) {
                    return !$context['data']['full_day'];
                }
                return false;
            })
            ->add('start_time', [
                'ruleInInstitutionShift' => [
                    'rule' => ['inInstitutionShift', 'academic_period_id'],
                    'on' => 'create'
                ]
            ])
            ->allowEmpty('end_time', function ($context) {
                if (array_key_exists('full_day', $context['data'])) {
                    return $context['data']['full_day'];
                }
                return false;
            })
            ->requirePresence('end_time', function ($context) {
                if (array_key_exists('full_day', $context['data'])) {
                    return !$context['data']['full_day'];
                }
                return false;
            })
            ->add('end_time', [
                'ruleCompareAbsenceTimeReverse' => [
                    'rule' => ['compareAbsenceTimeReverse', 'start_time', true]
                ],
                'ruleInInstitutionShift' => [
                    'rule' => ['inInstitutionShift', 'academic_period_id'],
                    'on' => 'create'
                ]
            ])
            ;
        return $validator;
    }

    public function onExcelGetStudentAbsenceReasonId(Event $event, Entity $entity)
    {
        if ($entity->student_absence_reason_id == 0) {
            return __('Unexcused');
        }
    }

    public function onExcelGetAbsences(Event $event, Entity $entity)
    {
        $startDate = "";
        $endDate = "";

        if (!empty($entity->start_date)) {
            $startDate = $this->formatDate($entity->start_date);
        } else {
            $startDate = $entity->start_date;
        }

        if (!empty($entity->end_date)) {
            $endDate = $this->formatDate($entity->end_date);
        } else {
            $endDate = $entity->end_date;
        }

        if ($entity->full_day) {
            return sprintf('%s %s (%s - %s)', __('Full'), __('Day'), $startDate, $endDate);
        } else {
            $startTime = $entity->start_time;
            $endTime = $entity->end_time;
            return sprintf('%s (%s - %s) %s (%s - %s)', __('Non Full Day'), $startDate, $endDate, __('Time'), $startTime, $endTime);
        }
    }

    public function onGetDate(Event $event, Entity $entity)
    {
        $startDate = $this->formatDate($entity->start_date);
        $endDate = $this->formatDate($entity->end_date);
        if ($entity->full_day == 1) {
            if (!empty($entity->end_date) && $entity->end_date > $entity->start_date) {
                $value = sprintf('%s - %s (%s)', $startDate, $endDate, __('full day'));
            } else {
                $value = sprintf('%s (%s)', $startDate, __('full day'));
            }
        } else {
            if ($this->absenceCodeList[$entity->absence_type_id] == 'LATE') {
                $endTime = $entity->end_time;
                $startTime = $entity->start_time;
                $secondsLate = intval($endTime->toUnixString()) - intval($startTime->toUnixString());
                $minutesLate = $secondsLate / 60;
                $hoursLate = floor($minutesLate / 60);
                if ($hoursLate > 0) {
                    $minutesLate = $minutesLate - ($hoursLate * 60);
                    $lateString = $hoursLate.' '.__('Hour').' '.$minutesLate.' '.__('Minute');
                } else {
                    $lateString = $minutesLate.' '.__('Minute');
                }
                $value = sprintf('%s (%s)', $startDate, $lateString);
            } else {
                $value = sprintf('%s (%s - %s)', $startDate, $this->formatTime($entity->start_time), $this->formatTime($entity->end_time));
            }
        }

        return $value;
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

    public function onGetStudentAbsenceReasonId(Event $event, Entity $entity)
    {
        if ($entity->student_absence_reason_id == 0) {
            return '<i class="fa fa-minus"></i>';
        }
    }

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

    public function editOnInitialize(Event $event, Entity $entity)
    {
        $this->request->query['student'] = $entity->student_id;
        $this->request->query['full_day'] = $entity->full_day;
        $this->request->data[$this->alias()]['full_day'] = $entity->full_day;
        $this->request->data[$this->alias()]['absence_type_id'] = $entity->absence_type_id;
    }

    public function beforeAction(Event $event)
    {
        $tabElements = [
            'Attendance' => [
                'url' => ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'StudentAttendances'],
                'text' => __('Attendance')
            ],
            'Absence' => [
                'url' => ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'StudentAbsences'],
                'text' => __('Absence')
            ]
        ];
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Absence');
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

        $this->_fieldOrder = ['date', 'student_id', 'absence_type_id', 'student_absence_reason_id'];
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        // POCOR-2547 Adding sortWhiteList to $options
        $query->contain(['Users']);

        $sortList = ['Users.first_name'];
        if (array_key_exists('sortWhitelist', $options)) {
            $sortList = array_merge($options['sortWhitelist'], $sortList);
        }
        $options['sortWhitelist'] = $sortList;

        // POCOR-2547 sort list of staff and student by name
        if (!isset($this->request->query['sort'])) {
            $query->order([$this->Users->aliasField('first_name'), $this->Users->aliasField('last_name')]);
        }
        // end POCOR-2547
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

    public function addEditBeforePatch(Event $event, $entity, $requestData, $patchOptions)
    {
        $absenceTypeId = $requestData[$this->alias()]['absence_type_id'];
        if ($this->absenceCodeList[$absenceTypeId] == 'LATE') {
            $requestData[$this->alias()]['end_date'] = $requestData[$this->alias()]['start_date'];
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity)
    {
        list($periodOptions, $selectedPeriod, $classOptions, $selectedClass, $studentOptions, $selectedStudent) = array_values($this->_getSelectOptions());
        $fullDayOptions = $this->getSelectOptions('general.yesno');
        $absenceTypeOptions = $this->absenceList;

        $this->field('absence_type_id', [
            'options' => $absenceTypeOptions
        ]);
        $this->field('academic_period_id', [
            'options' => $periodOptions
        ]);
        $this->field('class', [
            'options' => $classOptions
        ]);
        $this->field('student_id', [
            'options' => $studentOptions
        ]);
        $this->field('full_day', [
            'options' => $fullDayOptions
        ]);
        // Start Date and End Date
        if ($this->action == 'add') {
            // Malcolm discussed with Umairah and Thed - will revisit this when default date of htmlhelper is capable of setting 'defaultViewDate' ($entity->start_date = $todayDate; was: causing validation error to disappear)
            $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $startDate = $AcademicPeriod->get($selectedPeriod)->start_date;
            $endDate = $AcademicPeriod->get($selectedPeriod)->end_date;
            $this->field('end_date');

            $this->fields['start_date']['date_options']['startDate'] = $startDate->format('d-m-Y');
            $this->fields['start_date']['date_options']['endDate'] = $endDate->format('d-m-Y');
            $this->fields['end_date']['date_options']['startDate'] = $startDate->format('d-m-Y');
            $this->fields['end_date']['date_options']['endDate'] = $endDate->format('d-m-Y');
        } elseif ($this->action == 'edit') {
            $this->field('start_date', ['value' => date('Y-m-d', strtotime($entity->start_date))]);
            $this->field('end_date', ['value' => date('Y-m-d', strtotime($entity->end_date))]);
        }
        // End

        $this->field('start_time', ['type' => 'time', 'attr' => ['value' => date('h:i A', strtotime($entity->start_time))]]);
        $this->field('end_time', ['type' => 'time', 'attr' => ['value' => date('h:i A', strtotime($entity->end_time))]]);
        $this->field('student_absence_reason_id', ['type' => 'select']);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        $attr['onChangeReload'] = 'changePeriod';
        if ($action != 'add') {
            $attr['visible'] = false;
        }
        $attr['default'] = $request->query['period'];
        return $attr;
    }

    public function onUpdateFieldClass(Event $event, array $attr, $action, $request)
    {
        $attr['onChangeReload'] = 'changeClass';
        if ($action != 'add') {
            $attr['visible'] = false;
        }
        $attr['select'] = false;
        return $attr;
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
            if (array_key_exists($selectedAbsenceType, $this->absenceCodeList) && $this->absenceCodeList[$selectedAbsenceType] == 'LATE') {
                $attr['type'] = 'hidden';
            }
        }

        if ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = date('d-m-Y', strtotime($attr['value']));
        }

        return $attr;
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = date('d-m-Y', strtotime($attr['value']));
        }
        return $attr;
    }

    public function onUpdateFieldStartTime(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function onUpdateFieldEndTime(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }
        return $attr;
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $Users = TableRegistry::get('User.Users');
            $selectedStudent = $request->query('student');

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $Users->get($selectedStudent)->name_with_id;
        }

        return $attr;
    }

    public function onUpdateFieldFullDay(Event $event, array $attr, $action, $request)
    {
        $fullDayOptions = $attr['options'];
        $selectedFullDay = isset($request->data[$this->alias()]['full_day']) ? $request->data[$this->alias()]['full_day'] : 1;
        $this->advancedSelectOptions($fullDayOptions, $selectedFullDay);

        if ($selectedFullDay == 1) {
            $this->fields['start_time']['visible'] = false;
            $this->fields['end_time']['visible'] = false;
        } else {
            $this->fields['start_time']['visible'] = true;
            $this->fields['end_time']['visible'] = true;

            // to on the mandatory field asterick, using timepicker_input.ctp
            // timepicker_input.ctp, have the form helper error message, turn off the form helper error message.
            $this->fields['start_time']['null'] = false;
            $this->fields['end_time']['null'] = false;
        }

        if ($action == 'add') {
            $selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
            if (array_key_exists($selectedAbsenceType, $this->absenceCodeList) && $this->absenceCodeList[$selectedAbsenceType] == 'LATE') {
                $attr['type'] = 'hidden';
                $attr['attr']['value'] = 0;
                $this->fields['start_time']['visible'] = true;
                $this->fields['end_time']['visible'] = true;
                $request->data[$this->alias()]['full_day'] = 0;
            }
        }

        if ($action == 'edit') {
            $attr['type'] = 'readonly';
            if ($this->request->query['full_day']) {
                $attr['attr']['value'] = __('Yes');
            } else {
                $attr['attr']['value'] = __('No');
            }
        }

        $attr['select'] = false;
        $attr['options'] = $fullDayOptions;
        $attr['onChangeReload'] = 'changeFullDay';

        return $attr;
    }

    public function onUpdateFieldAbsenceTypeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            foreach ($attr['options'] as $key => $value) {
                $absenceTypeOptions[$key] = __($value);
            }
            if (!isset($request->data[$this->alias()]['absence_type_id'])) {
                $request->data[$this->alias()]['absence_type_id'] = key($absenceTypeOptions);
            }
            $selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
            $attr['options'] = $absenceTypeOptions;
            $attr['default'] = $selectedAbsenceType;
            $attr['onChangeReload'] = 'changeAbsenceType';
        }

        if ($action == 'edit') {
            $attr['type'] = 'readonly';
        }

        return $attr;
    }

    public function onUpdateFieldStudentAbsenceReasonId(Event $event, array $attr, $action, $request)
    {
        $selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
        if (!empty($selectedAbsenceType)) {
            $absenceType = $this->absenceCodeList[$selectedAbsenceType];
            if ($absenceType == 'UNEXCUSED') {
                $attr['type'] = 'hidden';
                $attr['attr']['value'] = 0;
            }
        }
        return $attr;
    }

    public function addEditOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['period']);
        unset($request->query['class']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
                if (array_key_exists('class', $request->data[$this->alias()])) {
                    $request->query['class'] = $request->data[$this->alias()]['class'];
                    $classId = $data[$this->alias()]['class'];

                    $InstitutionShift = TableRegistry::get('Institution.InstitutionShifts');
                    $shiftTime = $InstitutionShift
                            ->find('shiftTime', ['institution_class_id' => $classId])
                            ->first();

                    $startTime = $shiftTime->start_time;
                    $endTime = $shiftTime->end_time;

                    $entity->start_time = date('h:i A', strtotime($startTime));
                    $entity->end_time = date('h:i A', strtotime($endTime));

                    $data[$this->alias()]['start_time'] = $entity->start_time;
                    $data[$this->alias()]['end_time'] = $entity->end_time;
                }
            }
        }
    }

    public function addEditOnChangeClass(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['period']);
        unset($request->query['class']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
                if (array_key_exists('class', $request->data[$this->alias()])) {
                    $request->query['class'] = $request->data[$this->alias()]['class'];
                    $classId = $data[$this->alias()]['class'];

                    $InstitutionShift = TableRegistry::get('Institution.InstitutionShifts');
                    $shiftTime = $InstitutionShift
                            ->find('shiftTime', ['institution_class_id' => $classId])
                            ->first();

                    $startTime = $shiftTime->start_time;
                    $endTime = $shiftTime->end_time;

                    $entity->start_time = date('h:i A', strtotime($startTime));
                    $entity->end_time = date('h:i A', strtotime($endTime));

                    $data[$this->alias()]['start_time'] = $entity->start_time;
                    $data[$this->alias()]['end_time'] = $entity->end_time;
                }
            }
        }
    }

    public function addEditOnChangeFullDay(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['full_day']);
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('full_day', $request->data[$this->alias()])) {
                    $request->query['full_day'] = $request->data[$this->alias()]['full_day'];
                    // full day == 1, not full day == 0
                    if (!$request->data[$this->alias()]['full_day']) {
                        $classId = $data[$this->alias()]['class'];

                        $InstitutionShift = TableRegistry::get('Institution.InstitutionShifts');
                        $shiftTime = $InstitutionShift
                            ->find('shiftTime', ['institution_class_id' => $classId])
                            ->first();

                        $startTime = $shiftTime->start_time;
                        $endTime = $shiftTime->end_time;

                        $entity->start_time = date('h:i A', strtotime($startTime));
                        $entity->end_time = date('h:i A', strtotime($endTime));
                    }
                }
            }
        }
    }

    public function addEditOnChangeAbsenceType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['absence_type_id']);
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('absence_type_id', $request->data[$this->alias()])) {
                    $selectedAbsenceType = $request->data[$this->alias()]['absence_type_id'];
                    $request->query['absence_type_id'] = $selectedAbsenceType;
                    if (array_key_exists($selectedAbsenceType, $this->absenceCodeList) && $this->absenceCodeList[$selectedAbsenceType] == 'LATE') {
                        $classId = $data[$this->alias()]['class'];

                        $InstitutionShift = TableRegistry::get('Institution.InstitutionShifts');
                        $shiftTime = $InstitutionShift
                            ->find('shiftTime', ['institution_class_id' => $classId])
                            ->first();

                        $startTime = $shiftTime->start_time;
                        $endTime = $shiftTime->end_time;

                        $entity->start_time = date('h:i A', strtotime($startTime));
                        $entity->end_time = date('h:i A', strtotime($endTime));
                    }
                }
            }
        }
    }

    public function _getSelectOptions()
    {
        //Return all required options and their key
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $Students = TableRegistry::get('Institution.InstitutionClassStudents');
        $institutionId = $this->Session->read('Institution.Institutions.id');

        // Academic Period
        $periodOptions = $AcademicPeriod->getYearList(['isEditable' => true]);
        if (is_null($this->request->query('period'))) {
            $this->request->query['period'] = $AcademicPeriod->getCurrent();
        }
        $selectedPeriod = $this->queryString('period', $periodOptions);
        $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
            'callable' => function ($id) use ($Classes, $institutionId) {
                return $Classes
                    ->find()
                    ->where([
                        $Classes->aliasField('institution_id') => $institutionId,
                        $Classes->aliasField('academic_period_id') => $id
                    ])
                    ->count();
            }
        ]);
        // End

        // Class
        $userId = $this->Auth->user('id');
        $AccessControl = $this->AccessControl;
        $classOptions = $Classes
            ->find('list')
            ->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl, 'controller' => $this->controller]) // restrict user to see own class if permission is set
            ->where([
                $Classes->aliasField('institution_id') => $institutionId,
                $Classes->aliasField('academic_period_id') => $selectedPeriod
            ])
            ->order([$Classes->aliasField('class_number') => 'ASC'])
            ->toArray();
        $selectedClass = !is_null($this->request->query('class')) ? $this->request->query('class') : key($classOptions);
        // End

        // Student
        $selectedClass = $this->queryString('class', $classOptions);
        $Students = TableRegistry::get('Institution.InstitutionClassStudents');
        $studentOptions = $Students
            ->find('list', ['keyField' => 'student_id', 'valueField' => 'student_name'])
            ->where([
                $Students->aliasField('academic_period_id') => $selectedPeriod,
                $Students->aliasField('institution_class_id') => $selectedClass
            ])
            ->contain(['Users'])
            ->order(['Users.first_name', 'Users.last_name']) // POCOR-2547 sort list of staff and student by name
            ->toArray();
        $selectedStudent = !is_null($this->request->query('student')) ? $this->request->query('student') : key($studentOptions);
        // End

        return compact('periodOptions', 'selectedPeriod', 'classOptions', 'selectedClass', 'studentOptions', 'selectedStudent');
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

        $absenceResults = $this
            ->find()
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('absence_type_id') => $absenceTypeId,
                $this->aliasField('start_date') . ' >='  => $academicPeriodStartDate,
                $this->aliasField('end_date') . ' <='  => $academicPeriodEndDate
            ])
            ->all();

        $absenceDay = 0;
        foreach ($absenceResults as $key => $obj) {
            $endDate = $obj->end_date;
            $startDate = $obj->start_date;
            $interval = $endDate->diff($startDate);
            $absenceDay = $absenceDay + $interval->days + 1;
        }

        return $absenceDay;
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
            ->contain(['AbsenceTypes', 'StudentAbsenceReasons'])
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('absence_type_id') => $absenceTypeId,
                $this->aliasField('start_date') . ' >='  => $academicPeriodStartDate,
                $this->aliasField('end_date') . ' <='  => $academicPeriodEndDate
            ])
            ->all();

        $referenceDetails = [];
        foreach ($absenceResults as $key => $obj) {
            $reason = 'Unexcused';
            if (isset($obj->student_absence_reason->name)) {
                $reason = $obj->student_absence_reason->name;
            }

            $startDate = $obj->start_date;
            $endDate = $obj->end_date;

            if ($startDate == $endDate) {
                $referenceDetails[$obj->id] = __($reason) . ' (' . $startDate->format($dateFormat) . ')';
            } else {
                $referenceDetails[$obj->id] = __($reason) . ' (' . $startDate->format($dateFormat) . ' - ' . $endDate->format($dateFormat) . ')';
            }
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
                'total_days' => $this->find()->func()->sum(
                    'DATEDIFF(end_date, start_date)+1' // MYSQL:-SPECIFIC CODE
                ),
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
                'start_date' . ' >='  => $currentPeriodStartDate->format('Y-m-d'),
                'end_date' . ' <='  => $currentPeriodEndDate->format('Y-m-d'),
            ])
            ->group(['institution_id', 'student_id', 'absence_type_id'])
            ->having(['total_days >= ' => $threshold])
            ->hydrate(false)
            ;

        return $unexcusedAbsenceResults->toArray();
    }
}
