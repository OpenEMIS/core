<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Datasource\ResultSetInterface;

class StudentAttendancesTable extends AppTable
{
    private $allDayOptions = [];
    private $selectedDate;

    public function initialize(array $config)
    {
        $this->table('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'next_institution_class_id']);
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);

        // $this->addBehavior('Excel', [
        //     'excludes' => ['status', 'education_grade_id', 'id', 'academic_period_id', 'institution_id'],
        //     'pages' => ['index']
        // ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'view']
        ]);
    }

    public function findClassStudentsWithAbsence(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $institutionClassId = $options['institution_class_id'];
        $academicPeriodId = $options['academic_period_id'];
        $attendancePeriodId = $options['attendance_period_id'];
        $weekId = $options['week_id'];
        $weekStartDay = $options['week_start_day'];
        $weekEndDay = $options['week_end_day'];
        $day = $options['day_id'];

        if ($day == -1) {
            $findDay[] = $weekStartDay;
            $findDay[] = $weekEndDay;
        } else {
            $findDay = $day;
        }

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
            ->contain([$this->Users->alias()])
            ->matching($this->StudentStatuses->alias(), function($q) {
                return $q->where([
                    $this->StudentStatuses->aliasField('code') => 'CURRENT'
                ]);
            })
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('institution_class_id') => $institutionClassId,
            ]);

        if ($day != -1) {
            // single day
            $query
                ->formatResults(function (ResultSetInterface $results) use ($findDay, $attendancePeriodId) {
                    $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
                    return $results->map(function ($row) use ($StudentAbsencesPeriodDetails, $findDay, $attendancePeriodId) {
                        $academicPeriodId = $row->academic_period_id;
                        $institutionClassId = $row->institution_class_id;
                        $studentId = $row->student_id;
                        $institutionId = $row->institution_id;

                        $PRESENT = 0;

                        $conditions = [
                            $StudentAbsencesPeriodDetails->aliasField('academic_period_id = ') => $academicPeriodId,
                            $StudentAbsencesPeriodDetails->aliasField('institution_class_id = ') => $institutionClassId,
                            $StudentAbsencesPeriodDetails->aliasField('student_id = ') => $studentId,
                            $StudentAbsencesPeriodDetails->aliasField('institution_id = ') => $institutionId,
                            $StudentAbsencesPeriodDetails->aliasField('period = ') => $attendancePeriodId,
                            $StudentAbsencesPeriodDetails->aliasField('date = ') => $findDay,
                        ];

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
                        } else {
                            $data = [
                                'date' => $findDay,
                                'period' => $attendancePeriodId,
                                'comment' => null,
                                'absence_type_id' => $PRESENT,
                                'student_absence_reason_id' => null,
                                'absence_type_code' => null
                            ];
                        }

                        $row->institution_student_absences = $data;
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

                                if ($studentId == $entityStudentId && $entityDateFormat == $date && $entityPeriod == $periodId) {
                                    $studentAttenanceData[$studentId][$dayId][$periodId] = $entity->code;
                                    break;
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
                            }
                            return $row;
                        });
                    });
            }
        }

        return $query;
    }
}
