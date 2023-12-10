<?php

namespace Institution\Model\Table;

use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
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
use Cake\Log\Log;
use Cake\Datasource\ConnectionManager;//POCOR-6658

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

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        //$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'next_institution_class_id']);
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
        //$this->hasOne('StudentAbsencesPeriodDetails', ['className' => 'Institution.StudentAbsencesPeriodDetails']);institution_class_id
        $this->addBehavior('ContactExcel', [ //POCOR-6898 change Excel to ContactExcel Behaviour
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
        $overlapDateCondition['OR'][] = [$InstitutionStudents->aliasField('end_date') . ' >= ' => $weekStartDay, $InstitutionStudents->aliasField('end_date') . ' <= ' => $weekEndDay];
        $overlapDateCondition['OR'][] = [$InstitutionStudents->aliasField('start_date') . ' <= ' => $weekStartDay, $InstitutionStudents->aliasField('end_date') . ' >= ' => $weekEndDay];
        /* POCOR-5912 condition for week filter ends */
        /* POCOR-5919 condition for day filter starts */
        if ($day != -1) {
            $conditionQuery = [$InstitutionStudents->aliasField('start_date <= ') => $day,
                'OR' => [
                    $InstitutionStudents->aliasField('end_date is ') => null,
                    $InstitutionStudents->aliasField('end_date >= ') => $day,

                ]
            ];
        }
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
                ->contain([$this->Users->alias(), 'InstitutionClasses'])
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
                    // //POCOR-5900 condition
                    $InstitutionStudents->aliasField('institution_id') => $institutionId,
                    $InstitutionStudents->aliasField('academic_period_id') => $academicPeriodId,
                    $InstitutionStudents->aliasField('education_grade_id') => $educationGradeId,
                    $InstitutionStudents->aliasField('student_status_id') => 1,
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
                ->contain([$this->Users->alias(), 'InstitutionClasses'])
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
                    $InstitutionStudents->aliasField('student_status_id') => 1,
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

                            $conditions = array_merge($conditions, $SubId[0]);
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
                                        [$StudentAbsenceReasons->aliasField('id = ') => $entity->student_absence_reason_id])->first();

                                if (!empty($studentAbsenceReason)) {
                                    $absenceReason['name'] = $studentAbsenceReason->name;
                                }

                                $AbsenceTypes = TableRegistry::get('Institution.AbsenceTypes');
                                $absenceType = $AbsenceTypes
                                    ->find()
                                    ->select([
                                        'name' => $AbsenceTypes->aliasField('name'),
                                        'code' => $AbsenceTypes->aliasField('code')
                                    ])
                                    ->where([$AbsenceTypes->aliasField('id = ') => $entity->absence_type_id])->first();

                                if (!empty($absenceType)) {
                                    $absenceType['name'] = $absenceType->name;
                                    $absenceType['code'] = $absenceType->code;
                                }
                            }
                        } else {
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
                                    $InstitutionStudents->aliasField('start_date') . ' <= ' => $findDay
                                ])->toArray();

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
                        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
                        $getRecord = $StudentAttendanceMarkedRecords->find('all')
                            ->where([
                                $StudentAttendanceMarkedRecords->aliasField('institution_class_id') => $institutionClassId,
                                $StudentAttendanceMarkedRecords->aliasField('education_grade_id') => $educationGradeId,
                                $StudentAttendanceMarkedRecords->aliasField('institution_id') => $institutionId,
                                $StudentAttendanceMarkedRecords->aliasField('academic_period_id') => $academicPeriodId,
                                $StudentAttendanceMarkedRecords->aliasField('date') => $findDay,
                                $StudentAttendanceMarkedRecords->aliasField('no_scheduled_class') => 1
                            ])->first();
                        if (!empty($getRecord)) {
                            $row->is_NoClassScheduled = 1;
                        } else {
                            $row->is_NoClassScheduled = 0;
                        }

                        if (isset($this->request) && ('excel' === $this->request->pass[0])) {

                            $row->attendance = '';
                            
                            if($row->is_NoClassScheduled==1){//POCOR-7929
                                $row->attendance = 'No scheduled class';
                            }
                            else if (isset($data['absence_type_id']) && ($data['absence_type_id'] == $PRESENT)) {
                                $row->attendance = 'Present';
                            } else if (isset($data['absence_type_code']) && ($data['absence_type_code'] == 'EXCUSED' || $data['absence_type_code'] == 'UNEXCUSED')) {
                                $row->attendance = 'Absent - ' . (isset($absenceType['name'])) ? $absenceType['name'] : '';
                            } else if (isset($data['absence_type_code']) && $data['absence_type_code'] == 'LATE') {
                                $row->attendance = 'Late';
                            } else {
                                $row->attendance = 'NOTMARKED';
                            }

                            $row->comment = $data['comment'];
                            $row->student_absence_reasons = (isset($absenceReason['name'])) ? $absenceReason['name'] : NULL;
                            $row->name = $row['user']['first_name'] . ' ' . $row['user']['last_name'];
                            $row->class = $row['institution_class']['name'];
                            $row->date = date("d/m/Y", strtotime($findDay));
                            $row->StudentStatuses = $row['_matchingData']['StudentStatuses']['name'];
                            $row->studentId = $row['student_id'];
                            $row->test = 1;
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
                    'academic_period_id' => $academicPeriodId,
                    'day_id' => $day,
                    'education_grade_id' => $educationGradeId,
                    'week_start_day' => $weekStartDay,//POCOR-7183
                    'week_end_day' => $weekEndDay//POCOR-7183
                ])->toArray();

            $dayList = $AcademicPeriodsTable
                ->find('DaysForPeriodWeek', [
                    'academic_period_id' => $academicPeriodId,
                    'week_id' => $weekId,
                    'institution_id' => $institutionId,
                    'exclude_all' => true
                ])->toArray();

            $studentListResult = $this
                ->find('list', [
                    'keyField' => 'student_id',
                    'valueField' => 'student_id'
                ])
                ->matching($this->StudentStatuses->alias(), function ($q) {
                    return $q->where([
                        $this->StudentStatuses->aliasField('code') => 'CURRENT'
                    ]);
                })
                ->where([
                    $this->aliasField('academic_period_id') => $academicPeriodId,
                    $this->aliasField('institution_class_id') => $institutionClassId,
                ])->all();
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
                        $StudentAbsencesPeriodDetails->aliasField('subject_id = ') => $subjectId,
                        'AND' => [
                            $StudentAbsencesPeriodDetails->aliasField('date >= ') => $weekStartDay,
                            $StudentAbsencesPeriodDetails->aliasField('date <= ') => $weekEndDay,
                        ]
                    ])->toArray();

                $isMarkedRecords = $StudentAttendanceMarkedRecords
                    ->find()
                    ->select([
                        $StudentAttendanceMarkedRecords->aliasField('date'),
                        $StudentAttendanceMarkedRecords->aliasField('period'),
                        $StudentAttendanceMarkedRecords->aliasField('no_scheduled_class')//POCOR-7929
                    ])
                    ->where([
                        $StudentAttendanceMarkedRecords->aliasField('academic_period_id = ') => $academicPeriodId,
                        $StudentAttendanceMarkedRecords->aliasField('institution_class_id = ') => $institutionClassId,
                        $StudentAttendanceMarkedRecords->aliasField('education_grade_id = ') => $educationGradeId,
                        $StudentAttendanceMarkedRecords->aliasField('institution_id = ') => $institutionId,
                        $StudentAttendanceMarkedRecords->aliasField('subject_id = ') => $subjectId,
                        $StudentAttendanceMarkedRecords->aliasField('date >= ') => $weekStartDay,
                        $StudentAttendanceMarkedRecords->aliasField('date <= ') => $weekEndDay
                    ])->toArray();

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
                                if (!empty($isMarkedRecords)) {//POCOR-7183 add if check isMarkedRecords condition not empty
                                    foreach ($isMarkedRecords as $entity) {
                                        $entityDate = $entity->date->format('Y-m-d');
                                        $entityPeriod = $entity->period;
                                        //POCOR-7929 start
                                        if ($entityDate == $date && $entity->no_scheduled_class==1){
                                            $studentAttenanceData[$studentId][$dayId][$periodId] = 'NoScheduledClicked';
                                             break;
                                        }
                                        //POCOR-7929 end
                                        else if ($entityDate == $date && $entityPeriod == $periodId) {
                                            $studentAttenanceData[$studentId][$dayId][$periodId] = 'PRESENT';
                                            break;
                                        }
                                    }
                                }
                            }
                            if (!empty($result)) {//POCOR-7183 add if check result condition not empty
                                foreach ($result as $entity) {
                                    $entityDateFormat = $entity->date->format('Y-m-d');
                                    $entityStudentId = $entity->student_id;
                                    $entityPeriod = $entity->period;
                                    if ($studentId == $entityStudentId && $entityDateFormat == $date && $entityPeriod == $periodId) {
                                        if (isset($this->request) && ('excel' === $this->request->pass[0])) {
                                            if ($entity->code == 'EXCUSED' || $entity->code == 'UNEXCUSED') {
                                                $studentAttenanceData[$studentId][$dayId][$periodId] = 'ABSENT';
                                                break;
                                            } else {
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
                }

                $query
                    ->formatResults(function (ResultSetInterface $results) use ($studentAttenanceData, $weekStartDay, $weekEndDay, $periodList) {
                        return $results->map(function ($row) use ($studentAttenanceData, $weekStartDay, $weekEndDay,$periodList) {
                            $studentId = $row->student_id;
                            if (isset($studentAttenanceData[$studentId])) {
                                $row->week_attendance = $studentAttenanceData[$studentId];

                                $row->current = date("d/m/Y", strtotime($weekStartDay)) . ' - ' . date("d/m/Y", strtotime($weekEndDay));

                                if (isset($this->request) && ('excel' === $this->request->pass[0])) {
                                    $row->name = $row['user']['openemis_no'] . ' - ' . $row['user']['first_name'] . ' ' . $row['user']['last_name'];

                                    foreach ($studentAttenanceData[$studentId] as $key => $value) {
                                        //POCOR-7929 start
                                        foreach ($periodList as $Key => $PeriodData) {
                                            $id=(int)$PeriodData['id'];
                                            if($value[$id] == "NoScheduledClicked"){
                                            $value[$id] = "No Scheduled Classes";  
                                            }
                                            $row->{'week_attendance_status_' . $key. '-'.$PeriodData['name'] } = $value[$id];    
                                        //POCOR-7929 end
                                        }
                                    }
                                }
                            }
                            return $row;
                        });
                    });
            }
        }
        //POCOR-6547[START]
        if ($day != -1) {
            $studentId = [];
            $studentWithdraw = TableRegistry::get('institution_student_withdraw');
            //POCOR-7183 starts
            if (!empty($findDay[0]) && !empty($findDay[1]) && !empty($day['date'])) {
                $DayCondititon = [
                    $studentWithdraw->aliasField('effective_date >= ') => $findDay[0],
                    $studentWithdraw->aliasField('effective_date <= ') => $findDay[1]
                ];
            } else {
                $DayCondititon = [$studentWithdraw->aliasField('effective_date <= ') => $findDay];
            }//POCOR-7183 ends
            $studentWithdrawData = $studentWithdraw->find()
                ->select([
                    'student_id' => 'institution_student_withdraw.student_id',
                ])
                /*POCOR-6062 starts*/
                ->leftJoin([$InstitutionStudents->alias() => $InstitutionStudents->table()], [
                    $InstitutionStudents->aliasField('student_id = ') . $studentWithdraw->aliasField('student_id'),
                    $InstitutionStudents->aliasField('education_grade_id = ') . $studentWithdraw->aliasField('education_grade_id'),
                    $InstitutionStudents->aliasField('academic_period_id = ') . $studentWithdraw->aliasField('academic_period_id'),
                    $InstitutionStudents->aliasField('institution_id = ') . $studentWithdraw->aliasField('institution_id')
                ])/*POCOR-6062 ends*/
                ->where([
                    $studentWithdraw->aliasField('institution_id') => $institutionId,
                    $studentWithdraw->aliasField('academic_period_id') => $academicPeriodId,
                    $studentWithdraw->aliasField('education_grade_id') => $educationGradeId,
                    // $studentWithdraw->aliasField('effective_date >= ') => $day,
                    $DayCondititon,//POCOR-7183
                    $InstitutionStudents->aliasField('student_status_id !=') => 1 //POCOR-6062
                ])->toArray();
        } else {
            $studentId = [];
            $studentWithdraw = TableRegistry::get('institution_student_withdraw');
            $studentWithdrawData = $studentWithdraw->find()
                ->select([
                    'student_id' => 'institution_student_withdraw.student_id',
                ])
                /*POCOR-6062 starts*/
                ->leftJoin([$InstitutionStudents->alias() => $InstitutionStudents->table()], [
                    $InstitutionStudents->aliasField('student_id = ') . $studentWithdraw->aliasField('student_id'),
                    $InstitutionStudents->aliasField('education_grade_id = ') . $studentWithdraw->aliasField('education_grade_id'),
                    $InstitutionStudents->aliasField('academic_period_id = ') . $studentWithdraw->aliasField('academic_period_id'),
                    $InstitutionStudents->aliasField('institution_id = ') . $studentWithdraw->aliasField('institution_id')
                ])/*POCOR-6062 ends*/
                ->where([
                    $studentWithdraw->aliasField('institution_id') => $institutionId,
                    $studentWithdraw->aliasField('academic_period_id') => $academicPeriodId,
                    $studentWithdraw->aliasField('education_grade_id') => $educationGradeId,
                    //$studentWithdraw->aliasField('effective_date >= ') => $day,
                    $studentWithdraw->aliasField('effective_date <= ') => $findDay,
                    $InstitutionStudents->aliasField('student_status_id !=') => 1 //POCOR-6062
                ])
                ->toArray();
        }
        //POCOR-6547[END]       
        if ($studentWithdrawData) {
            $studentId = [];
            $WithDrawstudentId = [];
            $CurrentStudentId = [];
            $InstitutionStudents = TableRegistry::get('InstitutionStudents');//POCOR-7902
            foreach ($studentWithdrawData as $studenetVal) {
                $WithDrawstudentId[] = $studenetVal['student_id'];
            }
            //POCOR-7902 start
            $InstitutionStudentsCurrentData = $InstitutionStudents
                ->find()
                ->select([
                 'student_id'=>'InstitutionStudents.student_id'
                ])
                ->where([
                    $InstitutionStudents->aliasField('institution_id') => $institutionId,
                    $InstitutionStudents->aliasField('academic_period_id') => $academicPeriodId,
                    $InstitutionStudents->aliasField('education_grade_id') => $educationGradeId,
                    $InstitutionStudents->aliasField('student_status_id') => 1,
                    $InstitutionStudents->aliasField('student_id IN') => $WithDrawstudentId
                ])
                ->autoFields(true)
                ->toArray();
            foreach ($InstitutionStudentsCurrentData as $CurrentstudenetVal) {
                $CurrentStudentId[] = $CurrentstudenetVal['student_id'];
            }
            $studentId =  array_diff($WithDrawstudentId, $CurrentStudentId); //POCOR-7902 end
            $query->where([$this->aliasField('student_id NOT IN') => $studentId]);
        }
        return $query;
    }

    /**
     * @param Query $query
     * @param array $options
     * @return Query
     * @throws \Exception
     */
    public function findClassStudentsWithAbsenceArchive(Query $query, array $options)

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
//        $this->log("institutionId = $institutionId", 'debug');
//        $this->log("institutionClassId = $institutionClassId", 'debug');
//        $this->log("educationGradeId = $educationGradeId", 'debug');
//        $this->log("academicPeriodId = $academicPeriodId", 'debug');
//        $this->log("weekId = $weekId", 'debug');
//        $this->log("weekStartDay = $weekStartDay", 'debug');
//        $this->log("weekEndDay = $weekEndDay", 'debug');
//        $this->log("day = $day", 'debug');
//        $this->log("subjectId = $subjectId", 'debug');
        $archive = true;
        $weekly = false;
        $dayly = false;

        if ($day == -1) {
            $weekly = true;
            $dayly = false;
        }

        if ($day != -1) {
            $weekly = false;
            $dayly = true;
        }

        $query = $this->getAttendanceBasicQuery(
            $query,
            $academicPeriodId,
            $institutionClassId,
            $educationGradeId,
            $institutionId);
//        $this->log("step 1", 'debug');
        if ($subjectId != 0) {
            $query = $this->getAttendanceQueryWithSubjectId($query,
                $subjectId);
        } else {
            $subjectId = null;
        }
//        $this->log("step 2", 'debug');

        $query = $this->getAttendanceQueryWithoutWithdrawn($query,
            $dayly,
            $day,
            $institutionId,
            $academicPeriodId,
            $educationGradeId,
            $weekStartDay,
            $weekEndDay,
            $archive);
//        $this->log("step 3", 'debug');

        if ($dayly) {
            // single day
//            $this->log("step 4", 'debug');

            $query = $this->getAttendanceDailyQueryWithDayCondition($query, $day);
//            $this->log("step 5", 'debug');

            $query = $this->getAttendanceDailyQueryWithDetails($query, $attendancePeriodId, $day, $subjectId, $archive);
//            $this->log("step 6", 'debug');

            $query = $this->getAttendanceDailyQueryWithAbsenceTypes($query, $archive);
//            $this->log("step 7", 'debug');

            $query = $this->getAttendanceDailyQueryWithMarkedRecords($query, $day, $archive);
//            $this->log("step 8", 'debug');

            $query = $this->getAttendanceDailyQueryWithAbsenceReasons($query, $archive);
//            $this->log("step 9", 'debug');

            $query = $this->getAttendanceDailySelectFields($query, $day, $archive);
//            $this->log("step 10", 'debug');

        }

        if ($weekly) {
            $query = $this->getOverlapWeekCondition($query, $weekStartDay, $weekEndDay);
            $WeekDaysAbsenceArray = $this->getWeekDaysAbsenceArray($query,
                $academicPeriodId,
                $weekId,
                $institutionId,
                $institutionClassId,
                $day,
                $educationGradeId,
                $weekStartDay,
                $weekEndDay,
                $attendancePeriodId,
                $subjectId,
                $archive);
//            $this->log($WeekDaysAbsenceArray, 'debug');
            $query = $this->getAttendanceWeeklySelectFields($query);
            $query = $this->getAbsenceWeeklyQueryFormatResults($query, $WeekDaysAbsenceArray, $weekStartDay, $weekEndDay);
        }


        return $query;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $Users = TableRegistry::get('Security.Users');
        $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
        $institution_id = !empty($this->request->query['institution_id']) ? $this->request->query['institution_id'] : 0;

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
        $classId = !empty($this->request->query['institution_class_id']) ? $this->request->query['institution_class_id'] : 0;
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
        // echo "<pre>"; print_r($settings); die();
        $weekStartDay = $this->request->query['week_start_day'];
        $weekEndDay = $this->request->query['week_end_day'];
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
                    'ConfigItemOptions.option_type' => 'first_day_of_week',
                    'ConfigItemOptions.visible' => 1
                ])
                ->toArray();
            //POCOR-7929 start
            $StudentAttendanceMarkTypesTable = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
            $AcademicPeriodsTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $periodList = $StudentAttendanceMarkTypesTable
            ->find('PeriodByClass', [
                'institution_class_id' => $this->request->query['institution_class_id'],
                'academic_period_id' => $this->request->query['academic_period_id'],
                'day_id' => $day_id,
                'education_grade_id' => $this->request->query['education_grade_id'],
                'week_start_day' => $weekStartDay, //POCOR-7183
                'week_end_day' => $weekEndDay //POCOR-7183
            ])->toArray();
            //POCOR-7929 end
            $schooldays = [];
            for ($i = 0; $i < $daysPerWeek; ++$i) {
                $schooldays[] = ($firstDayOfWeek + 7 + $i) % 7;
            }

            if (!empty($schooldays)) {
                $newArray[] = [
                    'key' => 'StudentAttendances.current',
                    'field' => 'current',
                    'type' => 'string',
                    'label' => 'Current Week'
                ];
                foreach ($schooldays as $key => $value) {
                    //POCOR-7929 start
                    foreach($periodList as $Key=>$PeriodData){
                       
                        $newArray[] = [
                            'key' => 'StudentAttendances.week_attendance_status_' . $options[$value] .'-'.$PeriodData['name'],
                            'field' => 'week_attendance_status_' . $options[$value] .'-'.$PeriodData['name'],
                            'type' => 'string',
                            'label' => $options[$value] . '-' . $PeriodData['name']
                        ];
                    }
                    //POCOR-7929 end
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
        $filter_key = array('StudentAttendances.id', 'StudentAttendances.student_id', 'StudentAttendances.institution_class_id', 'StudentAttendances.education_grade_id', 'StudentAttendances.academic_period_id', 'StudentAttendances.next_institution_class_id', 'StudentAttendances.student_status_id', 'StudentAttendances.rahul');

        foreach ($fields_arr as $field) {
            if (in_array($field['key'], $filter_key)) {
                unset($field);
            } else {
                array_push($field_show, $field);
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
            if (!$absenceObj['full_day']) {
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
                        $lateString = $hoursLate . ' ' . __('Hour') . ' ' . $minutesLate . ' ' . __('Minute');
                    } else {
                        $lateString = $minutesLate . ' ' . __('Minute');
                    }
                    $timeStr = sprintf(__($absenceObj['absence_type_name']) . ' - (%s)', $lateString);
                } else {
                    $timeStr = sprintf(__('Absent') . ' - ' . $absenceObj['absence_reason'] . ' (%s - %s)', $startTimeAbsent, $endTimeAbsent);
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
        //echo "<pre>";print_r($data);die;
    }

    /*
     * PCOOR-6658 STARTS 
     * Create function for save attendance for multigrade class also.
     * author : Anubhav Jain <anubhav.jain@mail.vinove.com>
     */
    public function findClassStudentsWithAbsenceSave(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $institutionClassId = $options['institution_class_id'];
        $educationGradeId = $options['education_grade_id'];
        $academicPeriodId = $options['academic_period_id'];
        $attendancePeriodId = $options['attendance_period_id'];
        $day = $options['day_id'];
        $subjectId = $options['subject_id'];

        $studentAttendanceMarkedRecords = TableRegistry::get('student_attendance_marked_records');
        $AttendanceMarkedData = $studentAttendanceMarkedRecords->find()
            ->where([
                $studentAttendanceMarkedRecords->aliasField('institution_id') => $institutionId,
                $studentAttendanceMarkedRecords->aliasField('academic_period_id') => $academicPeriodId,
                $studentAttendanceMarkedRecords->aliasField('institution_class_id') => $institutionClassId,
                $studentAttendanceMarkedRecords->aliasField('education_grade_id') => $educationGradeId,
                $studentAttendanceMarkedRecords->aliasField('period') => $attendancePeriodId,
                $studentAttendanceMarkedRecords->aliasField('date') => $day,
                $studentAttendanceMarkedRecords->aliasField('subject_id') => $subjectId
            ])
            ->count();
        if ($AttendanceMarkedData > 0) {
            return $query->find('list')->where(['institution_id' => $institutionId, 'academic_period_id' => $academicPeriodId, 'institution_class_id' => $institutionClassId, 'education_grade_id' => $educationGradeId]);//POCOR-7028
            // return true;
        } else {
            $connection = ConnectionManager::get('default');
            $dbConfig = $connection->config();
            $dbname = $dbConfig['database'];
            $results = $connection->execute("INSERT INTO `student_attendance_marked_records` (`institution_id`, `academic_period_id`, `institution_class_id`, `education_grade_id`, `date`, `period`, `subject_id`, `no_scheduled_class`) VALUES ('$institutionId', '$academicPeriodId', '$institutionClassId', '$educationGradeId', '$day', '$attendancePeriodId', '$subjectId', '0')");
            return $query->find('list')->where(['institution_id' => $institutionId, 'academic_period_id' => $academicPeriodId, 'institution_class_id' => $institutionClassId, 'education_grade_id' => $educationGradeId]); //POCOR-7051
            //return true;
        }
    }

    /**
     * @param Query $query
     * @param $academicPeriodId
     * @param $institutionClassId
     * @param $educationGradeId
     * @param $institutionId
     * @return Query
     */

    private function getAttendanceBasicQuery(Query $query,
                                             $academicPeriodId,
                                             $institutionClassId,
                                             $educationGradeId,
                                             $institutionId)
    {
        $InstitutionStudents = TableRegistry::get('institution_students');
        $Users = TableRegistry::get('security_users');
        $Classes = TableRegistry::get('institution_classes');
        $Statuses = TableRegistry::get('student_statuses');
        $query
            ->select([
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_class_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('student_id'),
                $Users->aliasField('id'),
                $Users->aliasField('openemis_no'),
                $Users->aliasField('first_name'),
                $Users->aliasField('middle_name'),
                $Users->aliasField('third_name'),
                $Users->aliasField('last_name'),
                $Users->aliasField('preferred_name')
            ])
            ->innerJoin(
                [$Users->alias() => $Users->table()],
                [
                    $Users->aliasField('id = ') . $this->aliasField('student_id'),
                ]
            )
            ->innerJoin(
                [$Classes->alias() => $Classes->table()],
                [
                    $Classes->aliasField('id = ') . $this->aliasField('institution_class_id'),
                ]
            )
            ->leftJoin(
                [$InstitutionStudents->alias() => $InstitutionStudents->table()],
                [
                    $InstitutionStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $InstitutionStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                ]
            )->leftJoin(
                [$Statuses->alias() => $Statuses->table()],
                [$Statuses->aliasField('id = ') . $InstitutionStudents->aliasField('student_status_id'),]
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
                $InstitutionStudents->aliasField('student_status_id') => 1, //POCOR-7895
//                $Statuses->aliasField('code NOT IN') => ['WITHDRAWN'],
            ])->group([
                $Users->aliasField('id')
            ])
            ->orderAsc(
                $Users->aliasField('first_name')
            )
            ->orderAsc(
                $Users->aliasField('last_name')
            );
        return $query;
    }

    /**
     * @param Query $query
     * @param $subjectId
     * @return Query
     */
    private function getAttendanceQueryWithSubjectId(Query $query, $subjectId)
    {
        $InstitutionSubjectStudents = TableRegistry::get('institution_subject_students');
        $query
            ->innerJoin(
                [$InstitutionSubjectStudents->alias() => $InstitutionSubjectStudents->table()],
                [
                    $InstitutionSubjectStudents->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
                    $InstitutionSubjectStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                ]
            )
            ->where([
                $InstitutionSubjectStudents->aliasField('institution_subject_id') => $subjectId,
            ]);
        return $query;
    }

    /**
     * @param Query $query
     * @param $dayly
     * @param $day
     * @param $institutionId
     * @param $academicPeriodId
     * @param $educationGradeId
     * @param $weekStartDay
     * @param $weekEndDay
     * @return Query
     */
    private function getAttendanceQueryWithoutWithdrawn(Query $query, $dayly, $day, $institutionId, $academicPeriodId, $educationGradeId, $weekStartDay, $weekEndDay, $archive = false)
    {
        if ($archive) {
            return $query;
        }
        $studentWithdraw = TableRegistry::get('institution_student_withdraw');
        if ($dayly) {
            $DayCondititon = [$studentWithdraw->aliasField('effective_date <= ') => $day];
        }
        if (!$dayly) {
            $DayCondititon = [
                $studentWithdraw->aliasField('effective_date >= ') => $weekStartDay,
                $studentWithdraw->aliasField('effective_date <= ') => $weekEndDay
            ];
        }
        $withdrawStudentIds = [];
        $InstitutionStudents = TableRegistry::get('institution_students');
        $studentWithdrawData = $studentWithdraw->find()
            ->select([
                'student_id' => 'institution_student_withdraw.student_id',
            ])
            /*POCOR-6062 starts*/
            ->leftJoin([$InstitutionStudents->alias() => $InstitutionStudents->table()], [
                $InstitutionStudents->aliasField('student_id = ') . $studentWithdraw->aliasField('student_id'),
                $InstitutionStudents->aliasField('education_grade_id = ') . $studentWithdraw->aliasField('education_grade_id'),
                $InstitutionStudents->aliasField('academic_period_id = ') . $studentWithdraw->aliasField('academic_period_id'),
                $InstitutionStudents->aliasField('institution_id = ') . $studentWithdraw->aliasField('institution_id')
            ])/*POCOR-6062 ends*/
            ->where([
                $studentWithdraw->aliasField('institution_id') => $institutionId,
                $studentWithdraw->aliasField('academic_period_id') => $academicPeriodId,
                $studentWithdraw->aliasField('education_grade_id') => $educationGradeId,
                $DayCondititon,//POCOR-7183
                $InstitutionStudents->aliasField('student_status_id !=') => 1 //POCOR-6062
            ])->toArray();
        //POCOR-6547[END]
        if ($studentWithdrawData) {
            foreach ($studentWithdrawData as $withdrawStudent) {
                $withdrawStudentIds[] = $withdrawStudent['student_id'];
            }
            if (!empty($withdrawStudentIds)) {
                $query->where([$this->aliasField('student_id NOT IN') => $withdrawStudentIds]);
            }
        }
        return $query;
    }

    /**
     * @param Query $query
     * @param $day
     * @return Query
     */

    private function getAttendanceDailyQueryWithDayCondition(Query $query, $day)
    {
//        $this->log("getAttendanceDailyQueryWithDayCondition $day", 'debug');
        $InstitutionStudents = TableRegistry::get('institution_students');
        $dayCondition = [$InstitutionStudents->aliasField('start_date <= ') => $day,
            'OR' => [
                $InstitutionStudents->aliasField('end_date is ') => null,
                $InstitutionStudents->aliasField('end_date >= ') => $day,
            ]
        ];
        $query->where($dayCondition);
        return $query;
    }

    /**
     * @param Query $query
     * @param $attendancePeriodId
     * @param $day
     * @param $subjectId
     * @param $archive
     * @return Query
     * @throws \Exception
     */
    private function getAttendanceDailyQueryWithDetails(Query $query, $attendancePeriodId, $day, $subjectId, $archive=false)
    {
        $table_name = 'institution_student_absence_details';
        if (!$archive) {
            $Details = TableRegistry::get($table_name);
        }
        if ($archive) {
            $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
            $table_name = $archiveTableAndConnection[0];
            $Details = TableRegistry::get($table_name);
        }

//        $this->log($subjectId, 'debug');
        $options = [
            $Details->aliasField('academic_period_id = ')
            . $this->aliasField('academic_period_id'),
            $Details->aliasField('institution_class_id = ')
            . $this->aliasField('institution_class_id'),
//            $Details->aliasField('education_grade_id = ')
//            . $this->aliasField('education_grade_id'),
            $Details->aliasField('student_id = ')
            . $this->aliasField('student_id'),
            $Details->aliasField('institution_id = ')
            . $this->aliasField('institution_id'),
            $Details->aliasField('period = ')
            . $attendancePeriodId,
            $Details->aliasField('date = "')
            . $day . '"'
        ];
        if ($subjectId) {
            $options[] = $Details->aliasField('subject_id = ') . $subjectId;
        }
//        $this->log($options, 'debug');
        $query->leftJoin(
            [$Details->alias() => $Details->table()],
            $options
        );
        return $query;
    }

    /**
     * @param Query $query
     * @param bool $archive
     * @return Query
     * @throws \Exception
     */
    private function getAttendanceDailyQueryWithAbsenceTypes(Query $query, $archive = false)
    {
        $table_name = 'institution_student_absence_details';
        if (!$archive) {
            $Details = TableRegistry::get($table_name);
        }
        if ($archive) {
            $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
            $table_name = $archiveTableAndConnection[0];
            $Details = TableRegistry::get($table_name);
        }
        $Types = TableRegistry::get('absence_types');

        $options = [
            $Types->aliasField('id = ')
            . $Details->aliasField('absence_type_id'),
        ];

        $query->leftJoin(
            [$Types->alias() => $Types->table()],
            $options
        );

        return $query;
    }

    /**
     * @param Query $query
     * @param $day
     * @param bool $archive
     * @return Query
     * @throws \Exception
     */
    private function getAttendanceDailyQueryWithMarkedRecords(Query $query, $day, $archive = false)
    {
//        $this->log($subjectId, 'debug');
        $table_name = 'student_attendance_marked_records';
        if (!$archive) {
            $Records = TableRegistry::get($table_name);
        }
        if ($archive) {
            $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
            $table_name = $archiveTableAndConnection[0];
            $Records = TableRegistry::get($table_name);
        }

        $options = [
            $Records->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
//            $Records->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
            $Records->aliasField('institution_id = ') . $this->aliasField('institution_id'),
            $Records->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
            $Records->aliasField('date = "') . $day . '"',
            $Records->aliasField('no_scheduled_class = ') . 1
        ];
//        $this->log($options, 'debug');
        $query->leftJoin(
            [$Records->alias() => $Records->table()],
            $options
        );

        return $query;
    }

    /**
     * @param Query $query
     * @param bool $archive
     * @return Query
     * @throws \Exception
     */
    private function getAttendanceDailyQueryWithAbsenceReasons(Query $query, $archive = false)
    {
//        $this->log($subjectId, 'debug');
        $table_name = 'institution_student_absence_details';
        if (!$archive) {
            $Details = TableRegistry::get($table_name);
        }
        if ($archive) {
            $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
            $table_name = $archiveTableAndConnection[0];
            $Details = TableRegistry::get($table_name);
        }
        $Reasons = TableRegistry::get('student_absence_reasons');
        $options = [
            $Details->aliasField('student_absence_reason_id = ') . $Reasons->aliasField('id'),
        ];
        $query->leftJoin(
            [$Reasons->alias() => $Reasons->table()],
            $options
        );

        return $query;
    }

    /**
     * @param Query $query
     * @param $day
     * @param bool $archive
     * @return Query
     * @throws \Exception
     */

    private function getAttendanceDailySelectFields(Query $query, $day, $archive = false)
    {
        $Statuses = TableRegistry::get('student_statuses');
        $Users = TableRegistry::get('security_users');
        $Types = TableRegistry::get('absence_types');
        $Classes = TableRegistry::get('institution_classes');
        $Reasons = TableRegistry::get('student_absence_reasons');
        if (!$archive) {
            $Details = TableRegistry::get('institution_student_absence_details');
            $Records = TableRegistry::get('student_attendance_marked_records');
        }
        if ($archive) {
            $table_name = 'institution_student_absence_details';
            if ($archive) {
                $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
                $table_name = $archiveTableAndConnection[0];
                $Details = TableRegistry::get($table_name);
            }
            $table_name = 'student_attendance_marked_records';
            if ($archive) {
                $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
                $table_name = $archiveTableAndConnection[0];
                $Records = TableRegistry::get($table_name);
            }
        }
        $first_name = $Users->aliasField('first_name');
        $last_name = $Users->aliasField('last_name');
        $absence_type_id = $Types->aliasField('id');
        $absence_type_code = $Types->aliasField('code');
        $absence_type_name = $Types->aliasField('name');
        $student_absence_reason_id = $Details->aliasField('student_absence_reason_id');
        $query->select([$this->aliasField('id'),
                'date' => $Details->aliasField('date'),
                'day' => '"' . $day . '"',
                'period' => $Details->aliasField('period'),
                'subject_id' => $Details->aliasField('subject_id'),
                'marked_date' => $Records->aliasField('date'),
                'marked_period' => $Records->aliasField('period'),
                'marked_subject_id' => $Records->aliasField('subject_id'),
                'comment' => $Details->aliasField('comment'),
                'student_absence_reason_id' => "COALESCE($student_absence_reason_id, NULL)",
                'student_absence_reason' => $Reasons->aliasField('name'),
                'student_name' => "CONCAT($first_name, ' ', $last_name)",
                'student_status' => $Statuses->aliasField('name'),
                'class_name' => $Classes->aliasField('name'),
                'openemis_no' => $Users->aliasField('openemis_no'),
                'absence_type_id' => "COALESCE($absence_type_id, 0)",
                'absence_type_code' => "COALESCE($absence_type_code, 'PRESENT')",
                'absence_type_name' => "COALESCE($absence_type_name, 'Present')",
                'no_scheduled_class' => $Records->aliasField('no_scheduled_class'),
                'user_id' => $this->aliasField('student_id')
            ]
        );
        return $query;
    }

    /**
     * @param Query $query
     * @param $weekStartDay
     * @param $weekEndDay
     * @return Query
     */
    private function getOverlapWeekCondition(Query $query, $weekStartDay, $weekEndDay)
    {
        $InstitutionStudents = TableRegistry::get('institution_students');
        $overlapDateCondition = [];
        $overlapDateCondition['OR'] = [];
        $overlapDateCondition['OR'][] = [$InstitutionStudents->aliasField('start_date') . ' >= ' => $weekStartDay, $InstitutionStudents->aliasField('start_date') . ' <= ' => $weekEndDay];
        $overlapDateCondition['OR'][] = [$InstitutionStudents->aliasField('end_date') . ' >= ' => $weekStartDay, $InstitutionStudents->aliasField('end_date') . ' <= ' => $weekEndDay];
        $overlapDateCondition['OR'][] = [$InstitutionStudents->aliasField('start_date') . ' <= ' => $weekStartDay, $InstitutionStudents->aliasField('end_date') . ' >= ' => $weekEndDay];

        $query = $query->where($overlapDateCondition);
        return $query;
    }

    /**
     * @param Query $query
     * @param $academicPeriodId
     * @param $weekId
     * @param $institutionId
     * @param $institutionClassId
     * @param $day
     * @param $educationGradeId
     * @param $weekStartDay
     * @param $weekEndDay
     * @param $attendancePeriodId
     * @param $subjectId
     * @param bool $archive
     * @return array
     * @throws \Exception
     */
    private function getWeekDaysAbsenceArray(Query $query,
                                             $academicPeriodId,
                                             $weekId,
                                             $institutionId,
                                             $institutionClassId,
                                             $day,
                                             $educationGradeId,
                                             $weekStartDay,
                                             $weekEndDay,
                                             $attendancePeriodId,
                                             $subjectId,
                                             $archive = false)
    {
        $dayList = $this->getWeekDaysList(
            $academicPeriodId,
            $weekId,
            $institutionId);
        $periodList = $this->getPeriodList(
            $institutionClassId,
            $academicPeriodId,
            $day,
            $educationGradeId,
            $weekStartDay,
            $weekEndDay);
        $WeekDaysAbsenceArray = [];
        foreach ($dayList as $day) {
            $weekday = $day['day'];
            $date = $day['date'];
            foreach ($periodList as $period) {
                $periodId = $period['id'];
                $not_marked = $this->getNotMarkedDay($institutionId,
                    $academicPeriodId,
                    $institutionClassId,
                    $educationGradeId,
                    $attendancePeriodId,
                    $subjectId,
                    $date,
                    $archive);
                $wideQuery = clone $query;
                $wideQuery = $this->getAttendanceDailyQueryWithDetails($wideQuery, $periodId, $date, $subjectId, $archive);
                $wideQuery = $this->getAttendanceDailyQueryWithAbsenceTypes($wideQuery, $archive);
                $wideQuery = $this->getAttendanceDailyQueryWithMarkedRecords($wideQuery, $date, $archive);
                $wideQuery = $this->getAttendanceDailyQueryWithAbsenceReasons($wideQuery, $archive);
                $wideQuery = $this->getAttendanceDailySelectFields($wideQuery, $date, $archive);
                $wideQueryResult = $wideQuery->find('list', [
                    'keyField' => 'day',
                    'groupField' => 'user_id',
                    'valueField' => function ($result) use ($not_marked, $periodId) {
                        $day = $result;
//                            $this->log($day, 'debug');
                        if ($not_marked) {
                            if (isset($result->no_scheduled_class) && $result->no_scheduled_class == 1) {
                                return [$periodId => "NoScheduledClicked"];
                            } else {
                                return [$periodId => "NOTMARKED"];
                            }
                        }
                        return [$periodId => $day->absence_type_code];
                    },
                ])
                    ->toArray();
//                    $this->log($wideQueryResult,'debug');
                foreach ($wideQueryResult as $student => $markday) {
                    if (isset($WeekDaysAbsenceArray[$student])) {
                        $WeekDaysAbsenceArray[$student][$weekday][$periodId] = $markday[$date][$periodId];
                    } else {
                        $WeekDaysAbsenceArray[$student] = [];
                        $WeekDaysAbsenceArray[$student][$weekday] = [$periodId => $markday[$date][$periodId]];
                    }
                }
            }
        }
        return $WeekDaysAbsenceArray;
    }

    private function getAttendanceWeeklySelectFields(Query $query)
    {
        $Users = TableRegistry::get('security_users');
        $Classes = TableRegistry::get('institution_classes');
        $first_name = $Users->aliasField('first_name');
        $last_name = $Users->aliasField('last_name');

        $query->select([$this->aliasField('id'),
                $this->aliasField('student_id'),
                'class_name' => $Classes->aliasField('name'),
                'student_name' => "CONCAT($first_name, ' ', $last_name)",
                'openemis_no' => $Users->aliasField('openemis_no'),
            ]
        );
        return $query;
    }

    /**
     * @param $academicPeriodId
     * @param $weekId
     * @param $institutionId
     * @return array
     */
    private function getWeekDaysList($academicPeriodId, $weekId, $institutionId)
    {
        $AcademicPeriodsTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        $dayList = $AcademicPeriodsTable
            ->find('DaysForPeriodWeek', [
                'academic_period_id' => $academicPeriodId,
                'week_id' => $weekId,
                'institution_id' => $institutionId,
                'exclude_all' => true
            ])->toArray();
        return $dayList;
    }

    /**
     * @param $institutionClassId
     * @param $academicPeriodId
     * @param $day
     * @param $educationGradeId
     * @param $weekStartDay
     * @param $weekEndDay
     * @return array
     */
    private function getPeriodList($institutionClassId, $academicPeriodId, $day, $educationGradeId, $weekStartDay, $weekEndDay)
    {
        $StudentAttendanceMarkTypesTable = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');

        $periodList = $StudentAttendanceMarkTypesTable
            ->find('PeriodByClass', [
                'institution_class_id' => $institutionClassId,
                'academic_period_id' => $academicPeriodId,
                'day_id' => $day,
                'education_grade_id' => $educationGradeId,
                'week_start_day' => $weekStartDay,//POCOR-7183
                'week_end_day' => $weekEndDay//POCOR-7183
            ])->toArray();
        return $periodList;
    }

    /**
     * @param $institutionId
     * @param $academicPeriodId
     * @param $institutionClassId
     * @param $educationGradeId
     * @param $attendancePeriodId
     * @param $subjectId
     * @param $day
     * @param bool $archive
     * @return bool
     * @throws \Exception
     */
    private function getNotMarkedDay($institutionId,
                                     $academicPeriodId,
                                     $institutionClassId,
                                     $educationGradeId,
                                     $attendancePeriodId,
                                     $subjectId,
                                     $day,
                                     $archive = false)
    {
        $table_name = 'student_attendance_marked_records';
        if (!$archive) {
            $Records = TableRegistry::get($table_name);
        }
        if ($archive) {
            $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
            $table_name = $archiveTableAndConnection[0];
            $Records = TableRegistry::get($table_name);
        }
        $where = [
            $Records->aliasField('institution_id') => $institutionId,
            $Records->aliasField('academic_period_id') => $academicPeriodId,
            $Records->aliasField('institution_class_id') => $institutionClassId,
//            $Records->aliasField('education_grade_id') => $educationGradeId,
            $Records->aliasField('date') => $day
        ];
        if ($attendancePeriodId) {
            $where[$Records->aliasField('period')] = $attendancePeriodId;
        }
        if ($subjectId) {
            $where[$Records->aliasField('subject_id')] = $subjectId;
        }
        $totalMarkedCount = $Records
            ->find('all')
            ->where($where)
            ->count();
        if ($totalMarkedCount == 0) {
            $not_marked = true;
        }
        return $not_marked;
    }

    /**
     * @param Query $query
     * @param array $WeekDaysAbsenceArray
     * @param $weekStartDay
     * @param $weekEndDay
     * @return Query
     */

    private function getAbsenceWeeklyQueryFormatResults(Query $query, array $WeekDaysAbsenceArray, $weekStartDay, $weekEndDay)
    {
        $query
            ->formatResults(function (ResultSetInterface $results) use ($WeekDaysAbsenceArray, $weekStartDay, $weekEndDay) {
                return $results->map(function ($row) use ($WeekDaysAbsenceArray, $weekStartDay, $weekEndDay) {
                    $studentId = $row->student_id;
                    if (isset($WeekDaysAbsenceArray[$studentId])) {
                        $row->week_attendance = $WeekDaysAbsenceArray[$studentId];
                        $row->current = date("d/m/Y", strtotime($weekStartDay)) . ' - ' . date("d/m/Y", strtotime($weekEndDay));

                        if (isset($this->request) && ('excel' === $this->request->pass[0])) {
                            foreach ($WeekDaysAbsenceArray[$studentId] as $key => $value) {
                                $day_value = "";
                                if (sizeof($value) == 1) {
                                    $day_value = $value[1];
                                } else {
                                    foreach ($value as $period_key => $period_value) {
                                        $day_value = $day_value . "; $period_key: " . $period_value;
                                    }
                                    $day_value = trim($day_value, '; ');
                                }
                                $row->{'week_attendance_status_' . $key} = $day_value;
                            }
                        }
                    }
                    return $row;
                });
            });
        return $query;
    }


}
