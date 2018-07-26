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
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);

        // $this->addBehavior('Institution.Calendar');
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
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('institution_class_id') => $institutionClassId,
                $this->aliasField('student_status_id') => $this->StudentStatuses->getIdByCode('CURRENT')
            ]);

        if ($day != -1) {
            // single day
            $query
                ->formatResults(function (ResultSetInterface $results) use ($findDay, $attendancePeriodId) {
                    $StudentAbsenceTable = TableRegistry::get('Institution.StudentAbsences');
                    return $results->map(function ($row) use ($StudentAbsenceTable, $findDay, $attendancePeriodId) {
                        $academicPeriodId = $row->academic_period_id;
                        $institutionClassId = $row->institution_class_id;
                        $studentId = $row->student_id;
                        $institutionId = $row->institution_id;

                        $PERSENT = 0;

                        $conditions = [
                            $StudentAbsenceTable->aliasField('academic_period_id = ') => $academicPeriodId,
                            $StudentAbsenceTable->aliasField('institution_class_id = ') => $institutionClassId,
                            $StudentAbsenceTable->aliasField('student_id = ') => $studentId,
                            $StudentAbsenceTable->aliasField('institution_id = ') => $institutionId,
                            $StudentAbsenceTable->aliasField('period = ') => $attendancePeriodId,
                            $StudentAbsenceTable->aliasField('date = ') => $findDay,
                        ];

                        $result = $StudentAbsenceTable
                            ->find()
                            ->contain(['AbsenceTypes'])
                            ->select([
                                $StudentAbsenceTable->aliasField('date'),
                                $StudentAbsenceTable->aliasField('period'),
                                $StudentAbsenceTable->aliasField('comment'),
                                $StudentAbsenceTable->aliasField('absence_type_id'),
                                $StudentAbsenceTable->aliasField('student_absence_reason_id'),
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
                                'absence_type_id' => $PERSENT,
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
                    'exclude_all' => true
                ])
                ->toArray();

            $studentListResult = $this
                ->find('list', [
                    'keyField' => 'student_id',
                    'valueField' => 'student_id'
                ])
                ->where([
                    $this->aliasField('academic_period_id') => $academicPeriodId,
                    $this->aliasField('institution_class_id') => $institutionClassId,
                    $this->aliasField('student_status_id') => $this->StudentStatuses->getIdByCode('CURRENT')
                ])
                ->all();

            if (!$studentListResult->isEmpty()) {
                $studentList = $studentListResult->toArray();

                $StudentAbsenceTable = TableRegistry::get('Institution.StudentAbsences');
                $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');

                $result = $StudentAbsenceTable
                    ->find()
                    ->contain(['AbsenceTypes'])
                    ->select([
                        $StudentAbsenceTable->aliasField('student_id'),
                        $StudentAbsenceTable->aliasField('date'),
                        $StudentAbsenceTable->aliasField('period'),
                        $StudentAbsenceTable->aliasField('absence_type_id'),
                        'code' => 'AbsenceTypes.code'
                    ])
                    ->where([
                        $StudentAbsenceTable->aliasField('academic_period_id = ') => $academicPeriodId,
                        $StudentAbsenceTable->aliasField('institution_class_id = ') => $institutionClassId,
                        $StudentAbsenceTable->aliasField('student_id IN ') => $studentList,
                        $StudentAbsenceTable->aliasField('institution_id = ') => $institutionId,
                        'AND' => [
                            $StudentAbsenceTable->aliasField('date >= ') => $weekStartDay,
                            $StudentAbsenceTable->aliasField('date <= ') => $weekEndDay,

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
