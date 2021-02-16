<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Chronos\Date;
use Cake\Datasource\ResultSetInterface;
use Cake\Core\Configure;

use App\Model\Table\ControllerActionTable;

class StudentAttendancesTable extends ControllerActionTable
{
    private $allDayOptions = [];
    private $selectedDate;
    private $_absenceData = [];

    public function initialize(array $config)
    {
        $this->table('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        //$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'institution_class_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'next_institution_class_id']);
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
        //$this->hasOne('StudentAbsencesPeriodDetails', ['className' => 'Institution.StudentAbsencesPeriodDetails']);institution_class_id
        $this->addBehavior('Excel', [
            'excludes' => [
                'start_date',
                'end_date',
                'start_year',
                'end_year',
                'FTE',
                'staff_type_id',
                'staff_status_id',
                'institution_id',
                'institution_position_id',
                'security_group_user_id'
            ],
            'pages' => ['index']
        ]);
       
        $AbsenceTypesTable = TableRegistry::get('Institution.AbsenceTypes');
        $this->absenceList = $AbsenceTypesTable->getAbsenceTypeList();
        $this->absenceCodeList = $AbsenceTypesTable->getCodeList();

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'view']
        ]);
    }

    public function findClassStudentsWithAbsence(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $institutionClassId = $options['institution_class_id'];
        $educationGradeId = $options['education_grade_id'];
        $academicPeriodId = $options['academic_period_id'];
        $attendancePeriodId = $options['attendance_period_id'];
        $weekId = $options['week_id'];
        $weekStartDay = $options['week_start_day'];
        $weekEndDay = $options['week_end_day'];
        $day = $options['day_id'];
        $subjectId = $options['subject_id'];

             
        $InstitutionSubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
        $InstitutionStudents = TableRegistry::get('Institution.Students');
        $this->Users = TableRegistry::get('Security.Users');
        /* POCOR-5912 condition for week filter starts */
        $overlapDateCondition['OR'] = [];
        $overlapDateCondition['OR'][] = [$InstitutionStudents->aliasField('start_date') . ' >= ' => $weekStartDay, $InstitutionStudents->aliasField('start_date') . ' <= ' => $weekEndDay];
        $overlapDateCondition['OR'][] = [$InstitutionStudents->aliasField('end_date'). ' >= ' => $weekStartDay, $InstitutionStudents->aliasField('end_date') . ' <= ' => $weekEndDay];
        $overlapDateCondition['OR'][] = [$InstitutionStudents->aliasField('start_date') . ' <= ' => $weekStartDay, $InstitutionStudents->aliasField('end_date') . ' >= ' => $weekEndDay];
        /* POCOR-5912 condition for week filter ends */
        /* POCOR-5919 condition for day filter starts */
        $conditionQuery = [$InstitutionStudents->aliasField('start_date <= ') => $day,
                'OR' => [
                $InstitutionStudents->aliasField('end_date is ') => null,
                $InstitutionStudents->aliasField('end_date >= ') => $day
                ]
        ];
        /* POCOR-5919 condition for day filter ends */
        
        if ($day == -1) {
            $findDay[] = $weekStartDay;
            $findDay[] = $weekEndDay;
        } else {
            $findDay = $day;
        }

        if ($subjectId != 0) {
            $query
            ->select([
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_class_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('student_id'),
                $this->Users->aliasField('id'),
                $this->Users->aliasField('openemis_no'),
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('middle_name'),
                $this->Users->aliasField('third_name'),
                $this->Users->aliasField('last_name'),
                $this->Users->aliasField('preferred_name')
            ])
            ->contain([$this->Users->alias(),'InstitutionClasses'])
            ->leftJoin(
                    [$InstitutionSubjectStudents->alias() => $InstitutionSubjectStudents->table()],
                    [
                        $InstitutionSubjectStudents->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
                        $InstitutionSubjectStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                    ]
                )
            //POCOR-5900 start (Filter for check start date of student)
            ->leftJoin(
                [$InstitutionStudents->alias() => $InstitutionStudents->table()],
                [
                    $InstitutionStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                ]
            )
            //POCOR-5900 end
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('institution_class_id') => $institutionClassId,
                $this->aliasField('education_grade_id') => $educationGradeId,
                $InstitutionSubjectStudents->aliasField('institution_subject_id') => $subjectId,
                //POCOR-5900 condition
                $InstitutionStudents->aliasField('institution_id') => $institutionId,
                $InstitutionStudents->aliasField('academic_period_id') => $academicPeriodId,
                $InstitutionStudents->aliasField('education_grade_id') => $educationGradeId,
                $overlapDateCondition,
                $conditionQuery
            ])
            ->group([
                $InstitutionSubjectStudents->aliasField('student_id')
            ])
            ->order([
                $this->Users->aliasField('id')
            ]);
        } else {
            $query
                ->select([
                    $this->aliasField('academic_period_id'),
                    $this->aliasField('institution_class_id'),
                    $this->aliasField('institution_id'),
                    $this->aliasField('student_id'),
                    $this->Users->aliasField('id'),
                    $this->Users->aliasField('openemis_no'),
                    $this->Users->aliasField('first_name'),
                    $this->Users->aliasField('middle_name'),
                    $this->Users->aliasField('third_name'),
                    $this->Users->aliasField('last_name'),
                    $this->Users->aliasField('preferred_name')
                ])
                ->contain([$this->Users->alias(),'InstitutionClasses'])
                //POCOR-5900 start (Filter for check start date of student)
                ->leftJoin(
                    [$InstitutionStudents->alias() => $InstitutionStudents->table()],
                    [
                        $InstitutionStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                    ]
                )
                //POCOR-5900 end
                ->where([
                    $this->aliasField('academic_period_id') => $academicPeriodId,
                    $this->aliasField('institution_class_id') => $institutionClassId,
                    $this->aliasField('education_grade_id') => $educationGradeId,
                    //POCOR-5900 condition
                    $InstitutionStudents->aliasField('institution_id') => $institutionId,
                    $InstitutionStudents->aliasField('academic_period_id') => $academicPeriodId,
                    $InstitutionStudents->aliasField('education_grade_id') => $educationGradeId,
                    $overlapDateCondition,
                    $conditionQuery 
                    ])
                    ->group([
                        $InstitutionStudents->aliasField('student_id')
                    ])
                    ->order([
                        $this->Users->aliasField('first_name')
                    ]);
        }

        if ($day != -1) {
            // single day

            $query
                ->formatResults(function (ResultSetInterface $results) use ($findDay, $attendancePeriodId, $subjectId, $educationGradeId) {


                    $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
                    $InstitutionStudents = TableRegistry::get('Institution.Students');
                   
                    return $results->map(function ($row) use ($StudentAbsencesPeriodDetails, $findDay, $attendancePeriodId, $subjectId, $educationGradeId, $InstitutionStudents) {
                                        
                        
                        $academicPeriodId = $row->academic_period_id;
                        $institutionClassId = $row->institution_class_id;
                        $studentId = $row->student_id;
                        $institutionId = $row->institution_id;
                        $PRESENT = 0;
                        $conditions = [];
                        $conditions = [
                            $StudentAbsencesPeriodDetails->aliasField('academic_period_id = ') => $academicPeriodId,
                            $StudentAbsencesPeriodDetails->aliasField('institution_class_id = ') => $institutionClassId,
                            $StudentAbsencesPeriodDetails->aliasField('education_grade_id = ') => $educationGradeId,
                            $StudentAbsencesPeriodDetails->aliasField('student_id = ') => $studentId,
                            $StudentAbsencesPeriodDetails->aliasField('institution_id = ') => $institutionId,
                            $StudentAbsencesPeriodDetails->aliasField('period = ') => $attendancePeriodId,
                            $StudentAbsencesPeriodDetails->aliasField('date = ') => $findDay,
                           // $StudentAbsencesPeriodDetails->aliasField('subject_id = ') => $subjectId,
                        ];
                        if ($subjectId) {
                            $SubId[] = [$StudentAbsencesPeriodDetails->aliasField('subject_id = ') => $subjectId];
                            
                           $conditions = array_merge($conditions,$SubId[0]);
                        }

                       
                        $absenceReason = array();
                        $absenceType = array();

                        $result = $StudentAbsencesPeriodDetails
                            ->find()
                            ->contain(['AbsenceTypes'])
                            ->select([
                                $StudentAbsencesPeriodDetails->aliasField('date'),
                                $StudentAbsencesPeriodDetails->aliasField('period'),
                                $StudentAbsencesPeriodDetails->aliasField('comment'),
                                $StudentAbsencesPeriodDetails->aliasField('absence_type_id'),
                                $StudentAbsencesPeriodDetails->aliasField('student_absence_reason_id'),
                                'AbsenceTypes.code'
                            ])
                            ->where($conditions)
                            ->all();
                        if (!$result->isEmpty()) {
                           
                            $entity = $result->first();
                            $data = [
                                'date' => $entity->date,
                                'period' => $entity->period,
                                'comment' => $entity->comment,
                                'absence_type_id' => $entity->absence_type_id,
                                'student_absence_reason_id' => $entity->student_absence_reason_id,
                                'absence_type_code' => $entity->absence_type->code
                            ];

                            if (isset($this->request) && ('excel' === $this->request->pass[0])) {
                              
                                    $StudentAbsenceReasons = TableRegistry::get('Institution.StudentAbsenceReasons');
                                    $studentAbsenceReason = $StudentAbsenceReasons
                                    ->find()
                                    ->select([
                                        'name' => $StudentAbsenceReasons->aliasField('name')
                                    ])
                                    ->where(
                                    [$StudentAbsenceReasons->aliasField('id = ') => $entity->student_absence_reason_id])
                                    ->first();

                                    if(!empty($studentAbsenceReason)){
                                        $absenceReason['name'] = $studentAbsenceReason->name;
                                    }

                                    $AbsenceTypes = TableRegistry::get('Institution.AbsenceTypes');
                                    $absenceType = $AbsenceTypes
                                    ->find()
                                    ->select([
                                        'name' => $AbsenceTypes->aliasField('name'),
                                        'code' => $AbsenceTypes->aliasField('code')
                                    ])
                                    ->where([$AbsenceTypes->aliasField('id = ') => $entity->absence_type_id])
                                    ->first();

                                    if(!empty($absenceType)) {
                                        $absenceType['name'] = $absenceType->name;
                                        $absenceType['code'] = $absenceType->code;
                                    }
                            
                            }
                        } else {
                           //die('adsfad');
                                $StudentAttendanceMarkedRecords = TableRegistry::get('Institution.StudentAttendanceMarkedRecords');

                                    $isMarkedRecords = $StudentAttendanceMarkedRecords
                                    ->find()
                                    ->select([
                                        $StudentAttendanceMarkedRecords->aliasField('date'),
                                        $StudentAttendanceMarkedRecords->aliasField('period')
                                    ])
                                    //POCOR-5900 start (Filter for check start date of student)
                                    ->leftJoin(
                                        [$InstitutionStudents->alias() => $InstitutionStudents->table()],
                                        [
                                            $InstitutionStudents->aliasField('institution_id = ') . $StudentAttendanceMarkedRecords->aliasField('institution_id'),
                                        ]
                                    )
                                    //POCOR-5900 end
                                    ->where([
                                        $StudentAttendanceMarkedRecords->aliasField('academic_period_id = ') => $academicPeriodId,
                                        $StudentAttendanceMarkedRecords->aliasField('institution_class_id = ') => $institutionClassId,
                                        $StudentAttendanceMarkedRecords->aliasField('education_grade_id = ') => $educationGradeId,
                                        $StudentAttendanceMarkedRecords->aliasField('institution_id = ') => $institutionId,
                                        $StudentAttendanceMarkedRecords->aliasField('date = ') => $findDay,
                                        $StudentAttendanceMarkedRecords->aliasField('subject_id = ') => $subjectId,
                                        $InstitutionStudents->aliasField('start_date').' <= ' => $findDay
                                    ])
                                    ->toArray();
                                    
                                    if (!empty($isMarkedRecords)) {
                                        $data = [
                                            'date' => $findDay,
                                            'period' => $attendancePeriodId,
                                            'comment' => null,
                                            'absence_type_id' => $PRESENT,
                                            'student_absence_reason_id' => null,
                                            'absence_type_code' => null
                                        ];
                                    } else {
                                        $data = [
                                            'date' => $findDay,
                                            'period' => $attendancePeriodId,
                                            'comment' => null,
                                            'absence_type_id' => null,
                                            'student_absence_reason_id' => null,
                                            'absence_type_code' => null
                                        ];
                                    }
                        } 

                        $row->institution_student_absences = $data;

                    if (isset($this->request) && ('excel' === $this->request->pass[0])) {

                        $row->attendance = '';

                        if (isset($data['absence_type_id']) && ($data['absence_type_id'] == $PRESENT)) {
                            $row->attendance = 'Present';
                        } else if (isset($data['absence_type_code']) && ($data['absence_type_code']=='EXCUSED' || $data['absence_type_code']=='UNEXCUSED')) {
                            $row->attendance = 'Absent - '.(isset($absenceType['name']))?$absenceType['name']:'';
                        } else if (isset($data['absence_type_code']) && $data['absence_type_code']=='LATE') {
                            $row->attendance = 'Late';
                        } else {
                             $row->attendance = 'NOTMARKED';
                        }

                           $row->comment = $data['comment'];
                           $row->student_absence_reasons =  (isset($absenceReason['name'])) ? $absenceReason['name'] : NULL;
                           $row->name = $row['user']['first_name'] . ' ' . $row['user']['last_name'];                        
                           $row->class = $row['institution_class']['name'];                        
                           $row->date =  date("d/m/Y", strtotime($findDay));                        
                           $row->StudentStatuses = $row['_matchingData']['StudentStatuses']['name'];                        
                           $row->studentId = $row['student_id'];                        
                        }

                        return $row;
                    });
                }
            );
        } else {
            // all day
            $StudentAttendanceMarkTypesTable = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
            $AcademicPeriodsTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');

            $periodList = $StudentAttendanceMarkTypesTable
                ->find('PeriodByClass', [
                    'institution_class_id' => $institutionClassId,
                    'academic_period_id' => $academicPeriodId
                ])
                ->toArray();

            $dayList = $AcademicPeriodsTable
                ->find('DaysForPeriodWeek', [
                    'academic_period_id' => $academicPeriodId,
                    'week_id' => $weekId,
                    'institution_id' => $institutionId,
                    'exclude_all' => true
                ])
                ->toArray();

            $studentListResult = $this
                ->find('list', [
                    'keyField' => 'student_id',
                    'valueField' => 'student_id'
                ])
                ->matching($this->StudentStatuses->alias(), function($q) {
                    return $q->where([
                        $this->StudentStatuses->aliasField('code') => 'CURRENT'
                    ]);
                })
                ->where([
                    $this->aliasField('academic_period_id') => $academicPeriodId,
                    $this->aliasField('institution_class_id') => $institutionClassId,
                ])
                ->all();

               

            if (!$studentListResult->isEmpty()) {
                $studentList = $studentListResult->toArray();

                $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
                $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');

                $result = $StudentAbsencesPeriodDetails
                    ->find()
                    ->contain(['AbsenceTypes'])
                    ->select([
                        $StudentAbsencesPeriodDetails->aliasField('student_id'),
                        $StudentAbsencesPeriodDetails->aliasField('date'),
                        $StudentAbsencesPeriodDetails->aliasField('period'),
                        $StudentAbsencesPeriodDetails->aliasField('absence_type_id'),
                        'code' => 'AbsenceTypes.code'
                    ])
                    ->where([
                        $StudentAbsencesPeriodDetails->aliasField('academic_period_id = ') => $academicPeriodId,
                        $StudentAbsencesPeriodDetails->aliasField('institution_class_id = ') => $institutionClassId,
                        $StudentAbsencesPeriodDetails->aliasField('education_grade_id = ') => $educationGradeId,
                        $StudentAbsencesPeriodDetails->aliasField('student_id IN ') => $studentList,
                        $StudentAbsencesPeriodDetails->aliasField('institution_id = ') => $institutionId,
                        'AND' => [
                            $StudentAbsencesPeriodDetails->aliasField('date >= ') => $weekStartDay,
                            $StudentAbsencesPeriodDetails->aliasField('date <= ') => $weekEndDay,

                        ]
                    ])
                    ->toArray();

                $isMarkedRecords = $StudentAttendanceMarkedRecords
                    ->find()
                    ->select([
                        $StudentAttendanceMarkedRecords->aliasField('date'),
                        $StudentAttendanceMarkedRecords->aliasField('period')
                    ])
                    ->where([
                        $StudentAttendanceMarkedRecords->aliasField('academic_period_id = ') => $academicPeriodId,
                        $StudentAttendanceMarkedRecords->aliasField('institution_class_id = ') => $institutionClassId,
                        $StudentAttendanceMarkedRecords->aliasField('education_grade_id = ') => $educationGradeId,
                        $StudentAttendanceMarkedRecords->aliasField('institution_id = ') => $institutionId,
                        $StudentAttendanceMarkedRecords->aliasField('date >= ') => $weekStartDay,
                        $StudentAttendanceMarkedRecords->aliasField('date <= ') => $weekEndDay
                    ])
                    ->toArray();
                
                $studentAttenanceData = [];
                foreach ($studentList as $value) {
                    $studentId = $value;
                    if (!isset($studentAttenanceData[$studentId])) {
                        $studentAttenanceData[$studentId] = [];
                    }

                    foreach ($dayList as $day) {
                        $dayId = $day['day'];
                        $date = $day['date'];

                        if (!isset($studentAttenanceData[$studentId][$dayId])) {
                            $studentAttenanceData[$studentId][$dayId] = [];
                        }

                        foreach ($periodList as $period) {
                            $periodId = $period['id'];

                            if (!isset($studentAttenanceData[$studentId][$dayId][$periodId])) {
                                $studentAttenanceData[$studentId][$dayId][$periodId] = 'NOTMARKED';
                                foreach ($isMarkedRecords as $entity) {
                                    $entityDate = $entity->date->format('Y-m-d');
                                    $entityPeriod = $entity->period;

                                    if ($entityDate == $date && $entityPeriod == $periodId) {
                                        $studentAttenanceData[$studentId][$dayId][$periodId] = 'PRESENT';
                                        break;
                                    }
                                }
                            }

                            foreach ($result as $entity) {
                                $entityDateFormat = $entity->date->format('Y-m-d');
                                $entityStudentId = $entity->student_id;
                                $entityPeriod = $entity->period;

                                if ($studentId == $entityStudentId && $entityDateFormat == $date && $entityPeriod == $periodId)
                                {

                                    if(isset($this->request) && ('excel' === $this->request->pass[0]))
                                    {
                                            if ($entity->code == 'EXCUSED' || $entity->code == 'UNEXCUSED')
                                            {
                                                $studentAttenanceData[$studentId][$dayId][$periodId] = 'ABSENT';
                                                break;
                                            }  else {
                                                $studentAttenanceData[$studentId][$dayId][$periodId] = $entity->code;
                                                break;
                                            }

                                    } else {
                                        $studentAttenanceData[$studentId][$dayId][$periodId] = $entity->code;
                                        break;
                                    }
                                }

                            }
                        }
                    }
                }

                $query
                    ->formatResults(function (ResultSetInterface $results) use ($studentAttenanceData) {
                        return $results->map(function ($row) use ($studentAttenanceData) {
                            $studentId = $row->student_id;
                            if (isset($studentAttenanceData[$studentId])) {
                                $row->week_attendance = $studentAttenanceData[$studentId];

                                if (isset($this->request) && ('excel' === $this->request->pass[0])) {
                                    $row->name = $row['user']['openemis_no'] . ' - ' . $row['user']['first_name'] . ' ' . $row['user']['last_name'];
                                    foreach ($studentAttenanceData[$studentId] as $key => $value) {
                                        $row->{'week_attendance_status_'.$key} = $value[1];
                                    }
                                }

                            }
                            return $row;
                        });
                    });
            }
        }

        return $query;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $Users = TableRegistry::get('Security.Users');
        $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
        $institution_id = !empty($this->request->query['institution_id']) ? $this->request->query['institution_id'] : 0 ;

        $query
        ->leftJoin(
                    [$Users->alias() => $Users->table()],
                    [
                        $Users->aliasField('id = ') . $this->aliasField('student_id')
                    ]
                )
        ->where([$this->aliasField('institution_id') => $institution_id]);
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        ini_set("memory_limit", "-1");

        $institutionId = $this->Session->read('Institution.Institutions.id');
        $classId = !empty($this->request->query['institution_class_id']) ? $this->request->query['institution_class_id'] : 0 ;
        $attendancePeriodId = $this->request->query['attendance_period_id'];
        $weekId = $this->request->query['week_id'];
        $weekStartDay = $this->request->query['week_start_day'];
        $weekEndDay = $this->request->query['week_end_day'];
        $dayId = $this->request->query['day_id'];
        $educationGradeId = $this->request->query['education_grade_id'];
        $subjectId = $this->request->query['subject_id'];  

        $sheetName = 'StudentAttendances';
        $sheets[] = [
            'name' => $sheetName,
            'table' => $this,
            'query' => $this
                ->find()
                ->select(['openemis_no' => 'Users.openemis_no'
                ]),
            'institutionId' => $institutionId,
            'classId' => $classId,
            'educationGradeId' => $educationGradeId,
            'academicPeriodId' => $this->request->query['academic_period_id'],
            'attendancePeriodId' => $attendancePeriodId,
            'weekId' => $weekId,
            'weekStartDay' => $weekStartDay,
            'weekEndDay' => $weekEndDay,
            'dayId' => $dayId,
            'subjectId' => $subjectId,
            'orientation' => 'landscape'
        ];
    }

    // To select another one more field from the containable data
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $day_id = $this->request->query('day_id');
        $newArray[] = [
            'key' => 'StudentAttendances.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => 'Openemis ID'
        ];

        $newArray[] = [
            'key' => 'StudentAttendances.name',
            'field' => 'name',
            'type' => 'string',
            'label' => 'Name'
        ];

        if ($day_id == -1) {

            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
            $daysPerWeek = $ConfigItems->value('days_per_week');

            $optionTable = TableRegistry::get('Configuration.ConfigItemOptions');
            $options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
                ->where([
                    'ConfigItemOptions.option_type' =>'first_day_of_week',
                    'ConfigItemOptions.visible' => 1
                ])
                ->toArray();

                $schooldays = [];
                for ($i = 0; $i < $daysPerWeek; ++$i) {
                    $schooldays[] = ($firstDayOfWeek + 7 + $i) % 7;
                }

                if (!empty($schooldays)) {
               
                   foreach ($schooldays as $key => $value) {
                        $newArray[] = [
                            'key' => 'StudentAttendances.week_attendance_status_'.$options[$value],
                            'field' => 'week_attendance_status_'.$options[$value],
                            'type' => 'string',
                            'label' => $options[$value]
                        ];
                   }
                }
        } else {
            $newArray[] = [
                'key' => 'StudentAttendances.attendance',
                'field' => 'attendance',
                'type' => 'string',
                'label' => ''
            ];
            $newArray[] = [
                'key' => 'StudentAttendances.date',
                'field' => 'date',
                'type' => 'string',
                'label' => ''
            ];
            $newArray[] = [
                'key' => 'StudentAttendances.student_statuses',
                'field' => 'StudentStatuses',
                'type' => 'string',
                'label' => ''
            ];
            $newArray[] = [
                'key' => 'StudentAttendances.class',
                'field' => 'class',
                'type' => 'string',
                'label' => ''
            ];
            $newArray[] = [
                'key' => 'StudentAttendances.student_absence_reasons',
                'field' => 'student_absence_reasons',
                'type' => 'string',
                'label' => 'Absent Reasons'
            ];
            $newArray[] = [
                'key' => 'StudentAttendances.comment',
                'field' => 'comment',
                'type' => 'string',
                'label' => 'Comment'
            ];
        }

        $fields_arr = $fields->getArrayCopy();
        $field_show = array();
        $filter_key = array('StudentAttendances.id','StudentAttendances.student_id','StudentAttendances.institution_class_id','StudentAttendances.education_grade_id','StudentAttendances.academic_period_id','StudentAttendances.next_institution_class_id','StudentAttendances.student_status_id');

        foreach ($fields_arr as $field){
            if (in_array($field['key'], $filter_key)) {
                unset($field);
            }
            else {
                array_push($field_show,$field);
            }
        }

        $newFields = array_merge($newArray, $field_show);
        $fields->exchangeArray($newFields);
        $sheet = $settings['sheet'];   

        $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        // Set data into a temporary variable
        $options['education_grade_id'] = $sheet['educationGradeId'];
        $options['institution_id'] = $sheet['institutionId'];
        $options['institution_class_id'] = $sheet['classId'];
        $options['academic_period_id'] = $sheet['academicPeriodId'];
        $options['attendance_period_id'] = $sheet['attendancePeriodId'];
        $options['week_id'] = $sheet['weekId'];
        $options['week_start_day'] = $sheet['weekStartDay'];
        $options['week_end_day'] = $sheet['weekEndDay'];
        $options['day_id'] = $sheet['dayId'];
        $options['subject_id'] = $sheet['subjectId'];

        $this->_absenceData = $this->findClassStudentsWithAbsence($sheet['query'], $options);
    }

    public function onExcelRenderAttendance(Event $event, Entity $entity, array $attr)
    {
        // Get the data from the temporary variable
        $absenceData = $this->_absenceData;
        $absenceCodeList = $this->absenceCodeList;
        if (isset($absenceData[$entity->student_id][$attr['date']])) {
            $absenceObj = $absenceData[$entity->student_id][$attr['date']];
            if (! $absenceObj['full_day']) {
                $startTimeAbsent = $absenceObj['start_time'];
                $endTimeAbsent = $absenceObj['end_time'];
                $startTime = new Time($startTimeAbsent);
                $startTimeAbsent = $startTime->format('h:i A');
                $endTime = new Time($endTimeAbsent);
                $endTimeAbsent = $endTime->format('h:i A');
                if ($absenceCodeList[$absenceObj['absence_type_id']] == 'LATE') {
                    $secondsLate = intval($endTime->toUnixString()) - intval($startTime->toUnixString());
                    $minutesLate = $secondsLate / 60;
                    $hoursLate = floor($minutesLate / 60);
                    if ($hoursLate > 0) {
                        $minutesLate = $minutesLate - ($hoursLate * 60);
                        $lateString = $hoursLate.' '.__('Hour').' '.$minutesLate.' '.__('Minute');
                    } else {
                        $lateString = $minutesLate.' '.__('Minute');
                    }
                    $timeStr = sprintf(__($absenceObj['absence_type_name']) . ' - (%s)', $lateString);
                } else {
                    $timeStr = sprintf(__('Absent') . ' - ' . $absenceObj['absence_reason']. ' (%s - %s)', $startTimeAbsent, $endTimeAbsent);
                }
                return $timeStr;
            } else {
                return sprintf('%s %s %s', __('Absent'), __('Full'), __('Day'));
            }
        } else {
            return '';
        }
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        echo "<pre>";print_r($data);die;
    }

    
}
