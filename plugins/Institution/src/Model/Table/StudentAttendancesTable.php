<?php

namespace Institution\Model\Table;

use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use ArrayObject;

use Cake\Event\EventInterface;
use Cake\I18n\Time;
use Cake\Http\ServerRequest;
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
use Cake\Datasource\ConnectionManager; //POCOR-6658
use Cake\ORM\Locator\TableLocator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Table;
use Cake\Chronos\Chronos;

// POCOR-9406

class StudentAttendancesTable extends ControllerActionTable
{
    private $allDayOptions = [];
    private $selectedDate;
    private $_absenceData = [];

    public function initialize(array $config): void
    {
        $this->setTable('institution_class_students');
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

        $this->addBehavior(
            'Institution.InstitutionTab',
            ['appliedAction' => [
                'Students' =>
                ['student_status_id', 'academic_period_id',],
                'StudentUser' =>
                [
                    'student_status_id',
                    'academic_period_id',
                ]
            ]]
        );

        $AbsenceTypesTable = TableRegistry::getTableLocator()->get('Institution.AbsenceTypes');
        $this->absenceList = $AbsenceTypesTable->getAbsenceTypeList();
        $this->absenceCodeList = $AbsenceTypesTable->getCodeList();

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'view']
        ]);
    }

    public function findClassStudentsWithAbsence(Query $query, array $options)
    {
        Log::debug("Entering findClassStudentsWithAbsence with options: " . print_r($options, true));

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
        $attendanceBy = $options['attendance_by'];

        $InstitutionSubjectStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');
        $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.Students');
        $this->Users = TableRegistry::getTableLocator()->get('Security.Users');
        $StudentStatusesTable = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
        $statuses = $StudentStatusesTable->findCodeList();
        Log::debug("StudentStatuses fetched: " . print_r($statuses, true));

        $overlapDateCondition = $this->_getOverlapDateCondition($InstitutionStudents, $weekStartDay, $weekEndDay);
        $conditionQuery = $this->_getDayFilterCondition($InstitutionStudents, $day);

        $findDay = ($day == -1) ? [$weekStartDay, $weekEndDay] : $day;

        Log::debug("Overlap Date Condition: " . print_r($overlapDateCondition, true));
        Log::debug("Day Filter Condition: " . print_r($conditionQuery, true));
        Log::debug("Find Day: " . print_r($findDay, true));
        $query = $this->_buildAttendanceQuery(
            $query,
            $institutionId,
            $institutionClassId,
            $educationGradeId,
            $academicPeriodId,
            $subjectId,
            $attendanceBy,
            $statuses,
            $overlapDateCondition,
            $conditionQuery,
            $InstitutionSubjectStudents,
            $InstitutionStudents,
            $this->Users
        );

                if ($day != -1) {
                    // Single day attendance
                    Log::debug("Processing single day attendance for day: " . $day);

                    // Pre-load tables outside the map function
                    $StudentAbsencesPeriodDetailsTable = TableRegistry::getTableLocator()->get('Institution.StudentAbsencesPeriodDetails');
                    $StudentAttendanceMarkedRecordsTable = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkedRecords');
                    $StudentAbsenceReasonsTable = TableRegistry::getTableLocator()->get('Institution.StudentAbsenceReasons');
                    $AbsenceTypesTable = TableRegistry::getTableLocator()->get('Institution.AbsenceTypes');
                    $InstitutionSubjectsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');

                    $query->formatResults(
                        function (ResultSetInterface $results) use (
                            $findDay,
                            $attendancePeriodId,
                            $subjectId,
                            $attendanceBy,
                            $StudentAbsencesPeriodDetailsTable,
                            $StudentAttendanceMarkedRecordsTable,
                            $StudentAbsenceReasonsTable,
                            $AbsenceTypesTable,
                            $InstitutionSubjectsTable,
                            $InstitutionStudents // Already loaded
                        ) {
                            return $results->map(function ($row) use (
                                $StudentAbsencesPeriodDetailsTable,
                                $findDay,
                                $attendancePeriodId,
                                $subjectId,
                                $attendanceBy,
                                $StudentAttendanceMarkedRecordsTable,
                                $StudentAbsenceReasonsTable,
                                $AbsenceTypesTable,
                                $InstitutionSubjectsTable,
                                $InstitutionStudents
                            ) {
                                return $this->_formatSingleDayStudentAttendanceRow(
                                    $row,
                                    $findDay,
                                    $attendancePeriodId,
                                    $subjectId,
                                    $attendanceBy,
                                    $StudentAbsencesPeriodDetailsTable,
                                    $StudentAttendanceMarkedRecordsTable,
                                    $StudentAbsenceReasonsTable,
                                    $AbsenceTypesTable,
                                    $InstitutionSubjectsTable,
                                    $InstitutionStudents
                                );
                            });
                        }
                    );        } else {
            // All day (weekly) attendance
            Log::debug("Processing weekly attendance for period: " . $attendancePeriodId);

            // Pre-load tables outside the map function
            $StudentAttendanceMarkTypesTable = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkTypes');
            $AcademicPeriodsTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
            $StudentAbsencesPeriodDetailsTable = TableRegistry::getTableLocator()->get('Institution.StudentAbsencesPeriodDetails');
            $StudentAttendanceMarkedRecordsTable = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkedRecords');
            $InstitutionSubjectsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');

            $periodList = $StudentAttendanceMarkTypesTable
                ->find('PeriodByClass', [
                    'institution_class_id' => $institutionClassId,
                    'academic_period_id' => $academicPeriodId,
                    'day_id' => $day,
                    'education_grade_id' => $educationGradeId,
                    'week_start_day' => $weekStartDay,
                    'week_end_day' => $weekEndDay
                ])->toArray();
            Log::debug("Period List for weekly attendance: " . print_r($periodList, true));

            $dayList = $AcademicPeriodsTable
                ->find('DaysForPeriodWeek', [
                    'academic_period_id' => $academicPeriodId,
                    'week_id' => $weekId,
                    'institution_id' => $institutionId,
                    'exclude_all' => true
                ])->toArray();
            Log::debug("Day List for weekly attendance: " . print_r($dayList, true));

            $studentListResult = $this
                ->find('list', [
                    'keyField' => 'student_id',
                    'valueField' => 'student_id'
                ])
                ->matching($this->StudentStatuses->getAlias(), function ($q) use ($statuses) {
                    return $q->where([
                        $this->StudentStatuses->aliasField('code') => $statuses['CURRENT']
                    ]);
                })
                ->where([
                    $this->aliasField('academic_period_id') => $academicPeriodId,
                    $this->aliasField('institution_class_id') => $institutionClassId,
                ])->all();

            if (!$studentListResult->isEmpty()) {
                $studentList = $studentListResult->toArray();
                if (empty($studentList)) {
                    $studentList = [0]; // POCOR-8022
                }
                Log::debug("Student List for weekly attendance: " . print_r($studentList, true));

                $absenceDetails = $StudentAbsencesPeriodDetailsTable
                    ->find()
                    ->contain(['AbsenceTypes'])
                    ->select([
                        $StudentAbsencesPeriodDetailsTable->aliasField('student_id'),
                        $StudentAbsencesPeriodDetailsTable->aliasField('date'),
                        $StudentAbsencesPeriodDetailsTable->aliasField('period'),
                        $StudentAbsencesPeriodDetailsTable->aliasField('subject_id'),
                        $StudentAbsencesPeriodDetailsTable->aliasField('absence_type_id'),
                        'code' => 'AbsenceTypes.code'
                    ])
                    ->where([
                        $StudentAbsencesPeriodDetailsTable->aliasField('academic_period_id = ') => $academicPeriodId,
                        $StudentAbsencesPeriodDetailsTable->aliasField('institution_class_id = ') => $institutionClassId,
                        $StudentAbsencesPeriodDetailsTable->aliasField('education_grade_id = ') => $educationGradeId,
                        $StudentAbsencesPeriodDetailsTable->aliasField('student_id IN ') => $studentList,
                        $StudentAbsencesPeriodDetailsTable->aliasField('institution_id = ') => $institutionId,
                        $StudentAbsencesPeriodDetailsTable->aliasField('subject_id = ') => $subjectId,
                        'AND' => [
                            $StudentAbsencesPeriodDetailsTable->aliasField('date >= ') => $weekStartDay,
                            $StudentAbsencesPeriodDetailsTable->aliasField('date <= ') => $weekEndDay,
                        ]
                    ])->toArray();
                Log::debug("Absence Details for weekly attendance: " . print_r($absenceDetails, true));


                $markedRecords = $StudentAttendanceMarkedRecordsTable
                    ->find()
                    ->select([
                        $StudentAttendanceMarkedRecordsTable->aliasField('date'),
                        $StudentAttendanceMarkedRecordsTable->aliasField('period'),
                        $StudentAttendanceMarkedRecordsTable->aliasField('subject_id'),
                        $StudentAttendanceMarkedRecordsTable->aliasField('no_scheduled_class')
                    ])
                    ->where([
                        $StudentAttendanceMarkedRecordsTable->aliasField('academic_period_id = ') => $academicPeriodId,
                        $StudentAttendanceMarkedRecordsTable->aliasField('institution_class_id = ') => $institutionClassId,
                        $StudentAttendanceMarkedRecordsTable->aliasField('education_grade_id = ') => $educationGradeId,
                        $StudentAttendanceMarkedRecordsTable->aliasField('institution_id = ') => $institutionId,
                        $StudentAttendanceMarkedRecordsTable->aliasField('subject_id = ') => $subjectId,
                        $StudentAttendanceMarkedRecordsTable->aliasField('date >= ') => $weekStartDay,
                        $StudentAttendanceMarkedRecordsTable->aliasField('date <= ') => $weekEndDay
                    ])->toArray();
                Log::debug("Marked Records for weekly attendance: " . print_r($markedRecords, true));


                $studentAttenanceData = $this->_buildWeeklyAttendanceData(
                    $studentList,
                    $dayList,
                    $periodList,
                    $markedRecords,
                    $absenceDetails,
                    $attendanceBy,
                    $subjectId
                );
                Log::debug("Aggregated student attendance data for weekly view: " . print_r($studentAttenanceData, true));

                $query
                    ->formatResults(function (ResultSetInterface $results) use (
                        $studentAttenanceData,
                        $weekStartDay,
                        $weekEndDay,
                        $periodList,
                        $attendanceBy,
                        $subjectId,
                        $InstitutionSubjectsTable // Pass pre-loaded table
                    ) {
                        return $results->map(function ($row) use (
                            $studentAttenanceData,
                            $weekStartDay,
                            $weekEndDay,
                            $periodList,
                            $attendanceBy,
                            $subjectId,
                            $InstitutionSubjectsTable
                        ) {
                            return $this->_formatWeeklyStudentAttendanceRow(
                                $row,
                                $studentAttenanceData,
                                $weekStartDay,
                                $weekEndDay,
                                $periodList,
                                $attendanceBy,
                                $subjectId,
                                $InstitutionSubjectsTable
                            );
                        });
                    });
            }
        }

        // Apply withdrawal filters
        $query = $this->_applyWithdrawalFilters(
            $query,
            $institutionId,
            $academicPeriodId,
            $educationGradeId,
            $day,
            $findDay,
            $InstitutionStudents, // Already loaded
            TableRegistry::getTableLocator()->get('Institution.StudentWithdraw')
        );

        Log::debug("Finished findClassStudentsWithAbsence. Final query: " . print_r($query->clause('where'), true));
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
            $institutionId
        );
        //        $this->log("step 1", 'debug');
        if ($subjectId != 0) {
            $query = $this->getAttendanceQueryWithSubjectId(
                $query,
                $subjectId
            );
        } else {
            $subjectId = null;
        }
        //        $this->log("step 2", 'debug');

        $query = $this->getAttendanceQueryWithoutWithdrawn(
            $query,
            $dayly,
            $day,
            $institutionId,
            $academicPeriodId,
            $educationGradeId,
            $weekStartDay,
            $weekEndDay,
            $archive
        );
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
            $WeekDaysAbsenceArray = $this->getWeekDaysAbsenceArray(
                $query,
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
                $archive
            );
            //            $this->log($WeekDaysAbsenceArray, 'debug');
            $query = $this->getAttendanceWeeklySelectFields($query);
            $query = $this->getAbsenceWeeklyQueryFormatResults($query, $WeekDaysAbsenceArray, $weekStartDay, $weekEndDay);
        }


        return $query;
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $Users = TableRegistry::getTableLocator()->get('User.Users');
        $StudentAbsencesPeriodDetails = TableRegistry::getTableLocator()->get('Institution.StudentAbsencesPeriodDetails');
        $institution_id = !empty($this->request->getQuery()['institution_id']) ? $this->request->getQuery()['institution_id'] : 0;
        $query
            ->leftJoin(
                [$Users->getAlias() => $Users->getTable()],
                [
                    $Users->aliasField('id = ') . $this->aliasField('student_id')
                ]
            )
            ->where([$this->aliasField('institution_id') => $institution_id]);
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        ini_set("memory_limit", "-1");

        $institutionId = $this->getInstitutionID();
        $classId = !empty($this->request->getQuery()['institution_class_id']) ? $this->request->getQuery()['institution_class_id'] : 0;
        $attendancePeriodId = $this->request->getQuery()['attendance_period_id'];
        $weekId = $this->request->getQuery()['week_id'];
        $weekStartDay = $this->request->getQuery()['week_start_day'];
        $weekEndDay = $this->request->getQuery()['week_end_day'];
        $dayId = $this->request->getQuery()['day_id'];
        $educationGradeId = $this->request->getQuery()['education_grade_id'];
        $subjectId = $this->request->getQuery()['subject_id']; //POCOR-8874
        $attendanceBy = $this->request->getQuery()['attendance_by']; //POCOR-8874

        $sheetName = 'StudentAttendances';
        $sheets[] = [
            'name' => $sheetName,
            'table' => $this,
            'query' => $this
                ->find()
                ->select(
                    [
                        'openemis_no' => 'Users.openemis_no'
                    ]
                ),
            'institutionId' => $institutionId,
            'classId' => $classId,
            'educationGradeId' => $educationGradeId,
            'academicPeriodId' => $this->request->getQuery()['academic_period_id'],
            'attendancePeriodId' => $attendancePeriodId,
            'weekId' => $weekId,
            'weekStartDay' => $weekStartDay,
            'weekEndDay' => $weekEndDay,
            'dayId' => $dayId,
            'subjectId' => $subjectId, // POCOR-8874
            'attendance_by' => $attendanceBy, //POCOR-8874
            'orientation' => 'landscape'
        ];
    }

    // To select another one more field from the containable data
    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $weekStartDay = $this->request->getQuery()['week_start_day'];
        $weekEndDay = $this->request->getQuery()['week_end_day'];
        $day_id = $this->request->getQuery()['day_id'];
        $attendanceBy = $this->request->getQuery()['attendance_by']; //POCOR-8874
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
        //POCOR-8874 start
        $newArray[] = [
            'key' => 'StudentAttendances.attendanceBy',
            'field' => 'attendanceBy',
            'type' => 'string',
            'label' => 'Attendance By'
        ];
        if ($attendanceBy == 'subject') {
            $newArray[] = [
                'key' => 'StudentAttendances.subject',
                'field' => 'subject',
                'type' => 'string',
                'label' => 'Subject'
            ];
        } else {
            $newArray[] = [
                'key' => 'StudentAttendances.period',
                'field' => 'period',
                'type' => 'string',
                'label' => 'Period'
            ];
        }
        //POCOR-8874 end

        if ($day_id == -1) {


            $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
            $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
            $daysPerWeek = $ConfigItems->value('days_per_week');

            $optionTable = TableRegistry::getTableLocator()->get('Configuration.ConfigItemOptions');
            $options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
                ->where([
                    'ConfigItemOptions.option_type' => 'first_day_of_week',
                    'ConfigItemOptions.visible' => 1
                ])
                ->toArray();
            //POCOR-7929 start
            $StudentAttendanceMarkTypesTable = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkTypes');
            $AcademicPeriodsTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
            $periodList = $StudentAttendanceMarkTypesTable
                ->find('PeriodByClass', [
                    'institution_class_id' => $this->request->getQuery()['institution_class_id'],
                    'academic_period_id' => $this->request->getQuery()['academic_period_id'],
                    'day_id' => $day_id,
                    'education_grade_id' => $this->request->getQuery()['education_grade_id'],
                    'week_start_day' => $weekStartDay, //POCOR-7183
                    'week_end_day' => $weekEndDay //POCOR-7183
                ])->toArray();
            //POCOR-7929 end
            //POCOR-8874 start
            $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
            $getSubject = $InstitutionSubjects->find('all')
                ->where([
                    $InstitutionSubjects->aliasField('id') => $this->request->getQuery()['subject_id'],
                ])->first();
            //POCOR-8874 end
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
                    //POCOR-8874 start
                    if ($attendanceBy == 'period') {
                        //POCOR-7929 start
                        foreach ($periodList as $Key => $PeriodData) {

                            $newArray[] = [
                                'key' => 'StudentAttendances.week_attendance_status_' . $options[$value] . '-' . $PeriodData['name'],
                                'field' => 'week_attendance_status_' . $options[$value] . '-' . $PeriodData['name'],
                                'type' => 'string',
                                'label' => $options[$value] . '-' . $PeriodData['name']
                            ];
                        }
                        //POCOR-7929 end
                    } else {
                        $newArray[] = [
                            'key' => 'StudentAttendances.week_attendance_status_' . $options[$value] . '-' . $getSubject->name,
                            'field' => 'week_attendance_status_' . $options[$value] . '-' . $getSubject->name,
                            'type' => 'string',
                            'label' => $options[$value] . '-' . $getSubject->name
                        ];
                    }
                    //POCOR-8874 end
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

        $AcademicPeriodTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');

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
        $options['subject_id'] = $sheet['subjectId']; //POCOR-8874
        $options['attendance_by'] = $sheet['attendance_by']; //POCOR-8874


        $this->_absenceData = $this->findClassStudentsWithAbsence($sheet['query'], $options);
    }

    public function onExcelRenderAttendance(EventInterface $event, Entity $entity, array $attr)
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

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        //echo "<pre>";print_r($data);die;
    }

    // POCOR-9406
    /**
     * Finder: ensures a marker row exists and resets no_scheduled_class for the slice,
     * then returns the original query filtered by class/grade/period.
     */
    public function findEditSavePeriodMarked(Query $query, array $options)
    {
        $p       = $this->normalizeAttendanceParams($options);
        $MarkedRecords = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkedRecords');
//        Log::debug(print_r(['p' => $p], true));
        // 1) Reset only rows that actually need it (to avoid wide locks)
        $searchConds = $this->markedDayConditions($p, /*includeSubject*/ true);
//        Log::debug(print_r(['searchConds' => $searchConds], true));
        $this->resetNoScheduledClass($MarkedRecords, $searchConds);

        // 2) Ensure the specific marker row exists (fires AFTER INSERT trigger only once)
        $keyConds = $this->markedDayConditions($p, /*includeSubject*/ true);
        if (!$MarkedRecords->find()->where($keyConds)->limit(1)->count()) {
            $this->insertMarkedDayIfAbsent($p);
        }

        // 3) Keep finder contract: filter the caller's $query and return it
        return $this->applyReturnFilter($query, $p);
    }

    /* =========================
     * Helpers (single purpose)
     * ========================= */

    /**
     * Normalize and type-cast inputs. Subjectless => 0 (per schema default/PK).
     */
    private function normalizeAttendanceParams(array $options): array
    {
        $subjectRaw = $options['subject_id'] ?? null;
        $subjectId  = ($subjectRaw === null || $subjectRaw === '' || $subjectRaw === '0' || $subjectRaw === 0 || $subjectRaw === 'undefined')
            ? 0 : (int)$subjectRaw;

        // Normalize the date
        $rawDate = $options['day_id'] ?? null;
        $normalizedDate = null;

        if (!empty($rawDate)) {
            try {
                $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
                $systemDateFormat = $ConfigItems->value('date_format') ?: 'Y-m-d';

                // Convert to Chronos (safe DateTime subclass)
                $date = Chronos::createFromFormat('Y-m-d', $rawDate);
//                $date = Chronos::createFromFormat($systemDateFormat, $rawDate);
                $normalizedDate = $date->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning("Invalid date format in Attendance params: '$rawDate' using format '$systemDateFormat'");
                $normalizedDate = $rawDate; // fallback — still use raw string, but might fail later
            }
        }

        $p = [
            'institution_id'       => (int)$options['institution_id'],
            'institution_class_id' => (int)$options['institution_class_id'],
            'education_grade_id'   => (int)$options['education_grade_id'],
            'academic_period_id'   => (int)$options['academic_period_id'],
            'period'               => (int)$options['attendance_period_id'],
            'date'                 => $normalizedDate, // now normalized
            'subject_id'           => $subjectId,
        ];

        return $p;
    }

    /**
     * Build where conditions for the marker slice.
     * If $includeSubject = false, we DO NOT include subject_id (subject-agnostic slice).
     */
    private function markedDayConditions(array $p, bool $includeSubject): array
    {
        $conds = [
            'institution_id'       => $p['institution_id'],
            'academic_period_id'   => $p['academic_period_id'],
            'institution_class_id' => $p['institution_class_id'],
            'education_grade_id'   => $p['education_grade_id'],
            'date'                 => $p['date'],
            'period'               => $p['period'],
        ];
        if ($includeSubject) {
            $conds['subject_id'] = $p['subject_id']; // 0 for subjectless
        }
        return $conds;
    }

    /**
     * Narrow, lock-friendly reset: only update rows where no_scheduled_class != 0, in ID chunks.
     */
    /**
     * Same goal, but groups many PKs into a single UPDATE with OR’ed PK predicates.
     * Good compromise between A (many queries) and a single wide UPDATE (big locks).
     */
    private function resetNoScheduledClass(Table $MarkedRecords, array $searchConds): void
    {
        $rows = $MarkedRecords->find()
            ->select([
                'institution_id', 'academic_period_id', 'institution_class_id',
                'education_grade_id', 'date', 'period', 'subject_id'
            ])
            ->where($searchConds + ['no_scheduled_class !=' => 0])
            ->enableHydration(false)
            ->all()
            ->toList();

        if (empty($rows)) {
            return;
        }

        $conn = ConnectionManager::get('default');

        foreach (array_chunk($rows, 100) as $chunk) {
            $whereParts = [];
            $params     = [];

            foreach ($chunk as $i => $pk) {
                $whereParts[] = sprintf(
                    '(institution_id = :i%d AND academic_period_id = :ap%d AND institution_class_id = :ic%d AND education_grade_id = :eg%d AND `date` = :d%d AND period = :p%d AND subject_id = :s%d)',
                    $i, $i, $i, $i, $i, $i, $i
                );

                // Make sure date is valid format before adding it to query
                $params["i{$i}"]  = (int)$pk['institution_id'];
                $params["ap{$i}"] = (int)$pk['academic_period_id'];
                $params["ic{$i}"] = (int)$pk['institution_class_id'];
                $params["eg{$i}"] = (int)$pk['education_grade_id'];
                $params["d{$i}"]  = (string)$pk['date'];
                $params["p{$i}"]  = (int)$pk['period'];
                $params["s{$i}"]  = (int)$pk['subject_id'];
            }

            $sql = 'UPDATE student_attendance_marked_records
                SET no_scheduled_class = 0
                WHERE (' . implode(' OR ', $whereParts) . ')
                  AND no_scheduled_class != 0';

            try {
                $this->retryOnLock(function () use ($conn, $sql, $params) {
                    $conn->execute($sql, $params);
                });
            } catch (\PDOException $e) {
                // Skip known data errors, such as invalid date format
                if (stripos($e->getMessage(), 'Incorrect DATE value') !== false) {
                    Log::warning('Skipped chunk due to invalid date: ' . $e->getMessage());
                    continue; // Skip this chunk
                }

                // Re-throw other unexpected errors
                throw $e;
            }
        }
    }

    /**
     * Ensure a single marker row exists via INSERT IGNORE (idempotent; avoids duplicate trigger runs).
     */
    private function insertMarkedDayIfAbsent(array $p): void
    {
        /** @var Connection $conn */
        $conn = ConnectionManager::get('default');

        $sql = <<<SQL
INSERT IGNORE INTO student_attendance_marked_records
(institution_id, academic_period_id, institution_class_id, education_grade_id, `date`, period, subject_id, no_scheduled_class)
VALUES (:institution_id, :academic_period_id, :institution_class_id, :education_grade_id, :date, :period, :subject_id, 0)
SQL;

        $this->retryOnLock(function () use ($conn, $sql, $p) {
            $conn->execute($sql, [
                'institution_id'       => $p['institution_id'],
                'academic_period_id'   => $p['academic_period_id'],
                'institution_class_id' => $p['institution_class_id'],
                'education_grade_id'   => $p['education_grade_id'],
                'date'                 => $p['date'],
                'period'               => $p['period'],
                'subject_id'           => $p['subject_id'], // 0 allowed by schema/PK
            ]);
        });
    }

    /**
     * Apply the original finder’s list filter to the caller’s query and return it.
     */
    private function applyReturnFilter(Query $query, array $p): Query
    {
        return $query->find('list')->where([
            'institution_id'       => $p['institution_id'],
            'academic_period_id'   => $p['academic_period_id'],
            'institution_class_id' => $p['institution_class_id'],
            'education_grade_id'   => $p['education_grade_id'],
        ]);
    }

    /**
     * Tiny retry wrapper to survive 1205/1213 transient lock issues.
     */
    private function retryOnLock(callable $fn, int $retries = 3, int $backoffMicros = 200_000): void
    {
        beginning:
        try {
            $fn();
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            if ($retries > 0 && (strpos($msg, '1205') !== false || strpos($msg, '1213') !== false)) {
                usleep($backoffMicros); // 200ms
                $retries--;
                goto beginning;
            }
            throw $e;
        }
    }


/*
 * PCOOR-6658 STARTS
 * Create function for save attendance for multigrade class also.
 * author : Anubhav Jain <anubhav.jain@mail.vinove.com>
 */
//    public function findClassStudentsWithAbsenceSave(Query $query, array $options)
//    {
//        $institutionId = $options['institution_id'];
//        $institutionClassId = $options['institution_class_id'];
//        $educationGradeId = $options['education_grade_id'];
//        $academicPeriodId = $options['academic_period_id'];
//        $attendancePeriodId = $options['attendance_period_id'];
//        $day = $options['day_id'];
//        $subjectId = $options['subject_id'];
//
//        $studentAttendanceMarkedRecords = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkedRecords');
//        //POCOR-8383 start
//        $check  = $studentAttendanceMarkedRecords->updateAll(
//            ['no_scheduled_class' => 0], // Fields to update
//            [   // Conditions for which records to update
//                'institution_class_id' => $institutionClassId,
//                'education_grade_id' => $educationGradeId,
//                'institution_id' => $institutionId,
//                'academic_period_id' => $academicPeriodId,
//                'date' => $day,
//                'period' => $attendancePeriodId
//            ]
//        ); //POCOR-8383 end
//        $AttendanceMarkedData = $studentAttendanceMarkedRecords->find()
//            ->where([
//                $studentAttendanceMarkedRecords->aliasField('institution_id') => $institutionId,
//                $studentAttendanceMarkedRecords->aliasField('academic_period_id') => $academicPeriodId,
//                $studentAttendanceMarkedRecords->aliasField('institution_class_id') => $institutionClassId,
//                $studentAttendanceMarkedRecords->aliasField('education_grade_id') => $educationGradeId,
//                $studentAttendanceMarkedRecords->aliasField('period') => $attendancePeriodId,
//                $studentAttendanceMarkedRecords->aliasField('date') => $day,
//                $studentAttendanceMarkedRecords->aliasField('subject_id') => $subjectId
//            ])
//            ->count();
//        if ($AttendanceMarkedData > 0) {
//            return $query->find('list')->where(['institution_id' => $institutionId, 'academic_period_id' => $academicPeriodId, 'institution_class_id' => $institutionClassId, 'education_grade_id' => $educationGradeId]); //POCOR-7028
//            // return true;
//        } else {
//            $connection = ConnectionManager::get('default');
//            $dbConfig = $connection->config();
//            $dbname = $dbConfig['database'];
//            $results = $connection->execute("INSERT INTO `student_attendance_marked_records` (`institution_id`, `academic_period_id`, `institution_class_id`, `education_grade_id`, `date`, `period`, `subject_id`, `no_scheduled_class`) VALUES ('$institutionId', '$academicPeriodId', '$institutionClassId', '$educationGradeId', '$day', '$attendancePeriodId', '$subjectId', '0')");
//            return $query->find('list')->where(['institution_id' => $institutionId, 'academic_period_id' => $academicPeriodId, 'institution_class_id' => $institutionClassId, 'education_grade_id' => $educationGradeId]); //POCOR-7051
//            //return true;
//        }
//    }

    /**
     * Builds the overlap date condition for student enrollment dates.
     * @param Table $InstitutionStudents The InstitutionStudents table instance.
     * @param string $weekStartDay The start day of the week.
     * @param string $weekEndDay The end day of the week.
     * @return array The overlap date condition array.
     */
    private function _getOverlapDateCondition($InstitutionStudents, string $weekStartDay, string $weekEndDay): array
    {
        $overlapDateCondition['OR'] = [];
        $overlapDateCondition['OR'][] = [$InstitutionStudents->aliasField('start_date') . ' >= ' => $weekStartDay, $InstitutionStudents->aliasField('start_date') . ' <= ' => $weekEndDay];
        $overlapDateCondition['OR'][] = [$InstitutionStudents->aliasField('end_date') . ' >= ' => $weekStartDay, $InstitutionStudents->aliasField('end_date') . ' <= ' => $weekEndDay];
        $overlapDateCondition['OR'][] = [$InstitutionStudents->aliasField('start_date') . ' <= ' => $weekStartDay, $InstitutionStudents->aliasField('end_date') . ' >= ' => $weekEndDay];
        Log::debug("Overlap Date Condition generated: " . print_r($overlapDateCondition, true));
        return $overlapDateCondition;
    }

    /**
     * Builds the day filter condition for student enrollment dates.
     * @param Table $InstitutionStudents The InstitutionStudents table instance.
     * @param mixed $day The specific day or -1 for weekly view.
     * @return array The day filter condition array, or empty if day is -1.
     */
    private function _getDayFilterCondition($InstitutionStudents, $day): array
    {
        $conditionQuery = [];
        if ($day != -1) {
            $conditionQuery = [
                $InstitutionStudents->aliasField('start_date <= ') => $day,
                'OR' => [
                    $InstitutionStudents->aliasField('end_date is ') => null,
                    $InstitutionStudents->aliasField('end_date >= ') => $day,
                ]
            ];
        }
        Log::debug("Day Filter Condition generated: " . print_r($conditionQuery, true));
        return $conditionQuery;
    }

    /**
     * Builds the main attendance query with appropriate joins and conditions.
     * @param Query $query The query object.
     * @param int $institutionId Institution ID.
     * @param int $institutionClassId Institution Class ID.
     * @param int $educationGradeId Education Grade ID.
     * @param int $academicPeriodId Academic Period ID.
     * @param int $subjectId Subject ID.
     * @param string $attendanceBy Attendance type ('subject' or 'period').
     * @param array $statuses Student statuses.
     * @param array $overlapDateCondition Overlap date condition.
     * @param array $conditionQuery Day filter condition.
     * @param Table $InstitutionSubjectStudents InstitutionSubjectStudents table instance.
     * @param Table $InstitutionStudents InstitutionStudents table instance.
     * @param Table $Users Users table instance.
     * @return Query The modified query object.
     */
    private function _buildAttendanceQuery(
        Query $query,
        int $institutionId,
        int $institutionClassId,
        int $educationGradeId,
        int $academicPeriodId,
        int $subjectId,
        string $attendanceBy,
        array $statuses,
        array $overlapDateCondition,
        array $conditionQuery,
        Table $InstitutionSubjectStudents,
        Table $InstitutionStudents,
        Table $Users
    ): Query {
        Log::debug("Building attendance query for subjectId: $subjectId, attendanceBy: $attendanceBy");

        $commonSelect = [
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
            $Users->aliasField('preferred_name'), // POCOR-9011
            $Users->aliasField('gender_id') // POCOR-9011
        ];

        $query->select($commonSelect)
            ->contain([$Users->getAlias(), 'InstitutionClasses'])
            ->leftJoin(
                [$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()],
                [
                    $InstitutionStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                ]
            );

        $commonWhere = [
            $this->aliasField('academic_period_id') => $academicPeriodId,
            $this->aliasField('institution_class_id') => $institutionClassId,
            $this->aliasField('education_grade_id') => $educationGradeId,
            $InstitutionStudents->aliasField('institution_id') => $institutionId,
            $InstitutionStudents->aliasField('academic_period_id') => $academicPeriodId,
            $InstitutionStudents->aliasField('education_grade_id') => $educationGradeId,
            $InstitutionStudents->aliasField('student_status_id IN') => $statuses, // Use $statuses directly as it's already a list of codes
            $overlapDateCondition,
        ];
        if (!empty($conditionQuery)) {
             $commonWhere[] = $conditionQuery;
        }

        if ($subjectId != 0 || $attendanceBy == 'subject') {
            $query->leftJoin(
                [$InstitutionSubjectStudents->getAlias() => $InstitutionSubjectStudents->getTable()],
                [
                    $InstitutionSubjectStudents->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
                    $InstitutionSubjectStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                ]
            )
                ->where(array_merge($commonWhere, [
                    $InstitutionSubjectStudents->aliasField('institution_subject_id') => $subjectId,
                ]))
                ->group([
                    $InstitutionSubjectStudents->aliasField('student_id')
                ])
                ->order([
                    $Users->aliasField('id')
                ]);
        } else {
            $query->where($commonWhere)
                ->group([
                    $InstitutionStudents->aliasField('student_id')
                ])
                ->order([
                    $Users->aliasField('first_name')
                ]);
        }
        Log::debug("Attendance query built. Conditions: " . print_r($query->clause('where'), true));
        return $query;
    }

    /**
     * Formats a single student's attendance row for a specific day.
     * @param object $row Student data row.
     * @param mixed $findDay The day or range of days being queried.
     * @param int $attendancePeriodId The attendance period ID.
     * @param int $subjectId The subject ID.
     * @param string $attendanceBy Attendance type ('subject' or 'period').
     * @param Table $StudentAbsencesPeriodDetailsTable StudentAbsencesPeriodDetails table instance.
     * @param Table $StudentAttendanceMarkedRecordsTable StudentAttendanceMarkedRecords table instance.
     * @param Table $StudentAbsenceReasonsTable StudentAbsenceReasons table instance.
     * @param Table $AbsenceTypesTable AbsenceTypes table instance.
     * @param Table $InstitutionSubjectsTable InstitutionSubjects table instance.
     * @param Table $InstitutionStudentsTable InstitutionStudents table instance.
     * @return object The modified student data row.
     */
    private function _formatSingleDayStudentAttendanceRow(
        object $row,
        $findDay,
        int $attendancePeriodId,
        int $subjectId,
        string $attendanceBy,
        Table $StudentAbsencesPeriodDetailsTable,
        Table $StudentAttendanceMarkedRecordsTable,
        Table $StudentAbsenceReasonsTable,
        Table $AbsenceTypesTable,
        Table $InstitutionSubjectsTable,
        Table $InstitutionStudentsTable
    ): object {
        Log::debug("Formatting single day attendance row for student_id: {$row->student_id} on day: " . print_r($findDay, true));

        $academicPeriodId = $row->academic_period_id;
        $institutionClassId = $row->institution_class_id;
        $studentId = $row->student_id;
        $institutionId = $row->institution_id;
        $educationGradeId = $row->education_grade_id ?? 0; // Added as it was missing from options in previous use

        $PRESENT = 0; // Class constant for 'Present' status

        // POCOR-9011 start - Gender mapping
        $row->gender = __('Not Set');
        if (isset($row['user']['gender_id'])) {
            if ($row['user']['gender_id'] == 2) {
                $row->gender = __('Female');
            }
            if ($row['user']['gender_id'] == 1) {
                $row->gender = __('Male');
            }
        }
        // POCOR-9011 end

        $absenceReason = [];
        $absenceType = [];
        $data = [];

        $conditions = $this->_getAbsenceDetailsConditions(
            $StudentAbsencesPeriodDetailsTable,
            $academicPeriodId,
            $institutionClassId,
            $educationGradeId,
            $studentId,
            $institutionId,
            $attendancePeriodId,
            $findDay,
            $subjectId,
            $attendanceBy
        );

        $absenceResult = $StudentAbsencesPeriodDetailsTable
            ->find()
            ->contain(['AbsenceTypes'])
            ->select([
                $StudentAbsencesPeriodDetailsTable->aliasField('date'),
                $StudentAbsencesPeriodDetailsTable->aliasField('period'),
                $StudentAbsencesPeriodDetailsTable->aliasField('comment'),
                $StudentAbsencesPeriodDetailsTable->aliasField('absence_type_id'),
                $StudentAbsencesPeriodDetailsTable->aliasField('student_absence_reason_id'),
                'AbsenceTypes.code'
            ])
            ->where($conditions)
            ->first(); // Using first() instead of all() for a single record

        if ($absenceResult) {
            Log::debug("Absence result found for student {$studentId}: " . print_r($absenceResult->toArray(), true));
            $data = [
                'date' => $absenceResult->date,
                'period' => $absenceResult->period,
                'comment' => $absenceResult->comment,
                'absence_type_id' => $absenceResult->absence_type_id ?? 0,
                'student_absence_reason_id' => $absenceResult->student_absence_reason_id,
                'absence_type_code' => $absenceResult->absence_type->code
            ];
            // If in Excel context, fetch detailed reason and type names
            if (isset($this->request) && ('excel' === $this->request->getAttribute('params')['pass'][0])) {
                $absenceReason = $this->_getStudentAbsenceReasonName($StudentAbsenceReasonsTable, $absenceResult->student_absence_reason_id);
                $absenceType = $this->_getAbsenceTypeNameAndCode($AbsenceTypesTable, $absenceResult->absence_type_id);
            }
        } else {
            Log::debug("No absence result found for student {$studentId}. Checking marked records.");
            $data = $this->_getAttendanceMarkedData(
                $StudentAttendanceMarkedRecordsTable,
                $InstitutionStudentsTable,
                $academicPeriodId,
                $institutionClassId,
                $educationGradeId,
                $institutionId,
                $findDay,
                $subjectId,
                $attendancePeriodId,
                $PRESENT
            );
        }

        $row->institution_student_absences = $data;

        $record = $StudentAttendanceMarkedRecordsTable->find('all')
            ->where([
                $StudentAttendanceMarkedRecordsTable->aliasField('institution_class_id') => $institutionClassId,
//                $StudentAttendanceMarkedRecordsTable->aliasField('education_grade_id') => $educationGradeId,
                $StudentAttendanceMarkedRecordsTable->aliasField('institution_id') => $institutionId,
                $StudentAttendanceMarkedRecordsTable->aliasField('academic_period_id') => $academicPeriodId,
                $StudentAttendanceMarkedRecordsTable->aliasField('date') => $findDay,
                $StudentAttendanceMarkedRecordsTable->aliasField('no_scheduled_class') => 1,
                $StudentAttendanceMarkedRecordsTable->aliasField('period IS') => $attendancePeriodId
            ])->first();

        $row->is_NoClassScheduled = (!empty($record)) ? 1 : 0;
        Log::debug("is_NoClassScheduled for student {$studentId}: {$row->is_NoClassScheduled}");

        if ($subjectId) {
            $subject = $InstitutionSubjectsTable->find('all')
                ->where([$InstitutionSubjectsTable->aliasField('id') => $subjectId])
                ->first();
            $row->subject = $subject ? $subject->name : null;
            Log::debug("Subject name for student {$studentId}: {$row->subject}");
        }
        $attendanceReason = '';
        if (isset($this->request) && ('excel' === $this->request->getAttribute('params')['pass'][0])) {
            $row = $this->_setExcelRowData(
                $row,
                $data,
                $attendanceReason,
                $absenceType,
                $PRESENT,
                $attendancePeriodId,
                $attendanceBy
            );
        }

        Log::debug("Finished formatting row for student_id: {$row->student_id}");
        return $row;
    }

    /**
     * Helper to get conditions for fetching StudentAbsencesPeriodDetails.
     */
    private function _getAbsenceDetailsConditions(
        Table $StudentAbsencesPeriodDetailsTable,
        int $academicPeriodId,
        int $institutionClassId,
        int $educationGradeId,
        int $studentId,
        int $institutionId,
        int $attendancePeriodId,
        $findDay,
        int $subjectId,
        string $attendanceBy
    ): array {
        $conditions = [
            $StudentAbsencesPeriodDetailsTable->aliasField('academic_period_id = ') => $academicPeriodId,
            $StudentAbsencesPeriodDetailsTable->aliasField('institution_class_id = ') => $institutionClassId,
            $StudentAbsencesPeriodDetailsTable->aliasField('education_grade_id = ') => $educationGradeId,
            $StudentAbsencesPeriodDetailsTable->aliasField('student_id = ') => $studentId,
            $StudentAbsencesPeriodDetailsTable->aliasField('institution_id = ') => $institutionId,
            $StudentAbsencesPeriodDetailsTable->aliasField('date = ') => $findDay,
        ];

        if ($subjectId && $attendanceBy == 'subject') {
            $conditions[] = [$StudentAbsencesPeriodDetailsTable->aliasField('subject_id = ') => $subjectId];
        } else {
            $conditions[] = [$StudentAbsencesPeriodDetailsTable->aliasField('period = ') => $attendancePeriodId];
        }
        Log::debug("Absence details conditions: " . print_r($conditions, true));
        return $conditions;
    }

    /**
     * Helper to get attendance marked data if no absence details are found.
     */
    private function _getAttendanceMarkedData(
        Table $StudentAttendanceMarkedRecordsTable,
        Table $InstitutionStudentsTable,
        int $academicPeriodId,
        int $institutionClassId,
        int $educationGradeId,
        int $institutionId,
        $findDay,
        int $subjectId,
        int $attendancePeriodId,
        int $PRESENT
    ): array {
        $isMarkedRecords = $StudentAttendanceMarkedRecordsTable
            ->find()
            ->select([
                $StudentAttendanceMarkedRecordsTable->aliasField('date'),
                $StudentAttendanceMarkedRecordsTable->aliasField('period')
            ])
            ->leftJoin(
                [$InstitutionStudentsTable->getAlias() => $InstitutionStudentsTable->getTable()],
                [
                    $InstitutionStudentsTable->aliasField('institution_id = ') . $StudentAttendanceMarkedRecordsTable->aliasField('institution_id'),
                ]
            )
            ->where([
                $StudentAttendanceMarkedRecordsTable->aliasField('academic_period_id = ') => $academicPeriodId,
                $StudentAttendanceMarkedRecordsTable->aliasField('institution_class_id = ') => $institutionClassId,
                $StudentAttendanceMarkedRecordsTable->aliasField('education_grade_id = ') => $educationGradeId,
                $StudentAttendanceMarkedRecordsTable->aliasField('institution_id = ') => $institutionId,
                $StudentAttendanceMarkedRecordsTable->aliasField('date = ') => $findDay,
                $StudentAttendanceMarkedRecordsTable->aliasField('subject_id = ') => $subjectId,
                $InstitutionStudentsTable->aliasField('start_date') . ' <= ' => $findDay
            ])->first(); // Using first() instead of toArray() for single check

        $data = [
            'date' => $findDay,
            'period' => $attendancePeriodId,
            'comment' => null,
            'student_absence_reason_id' => null,
            'absence_type_code' => null
        ];

        if ($isMarkedRecords) {
            $data['absence_type_id'] = $PRESENT;
        } else {
            $data['absence_type_id'] = 0; // Default to 'Not Marked' if no absence and no marked record
        }
        Log::debug("Attendance marked data: " . print_r($data, true));
        return $data;
    }

    /**
     * Helper to get student absence reason name for Excel export.
     */
    private function _getStudentAbsenceReasonName(Table $StudentAbsenceReasonsTable, ?int $studentAbsenceReasonId): array
    {
        $absenceReason = [];
        if ($studentAbsenceReasonId) {
            $result = $StudentAbsenceReasonsTable
                ->find()
                ->select(['name' => $StudentAbsenceReasonsTable->aliasField('name')])
                ->where([$StudentAbsenceReasonsTable->aliasField('id = ') => $studentAbsenceReasonId])
                ->first();
            if ($result) {
                $absenceReason['name'] = $result->name;
            }
        }
        Log::debug("Student absence reason name: " . print_r($absenceReason, true));
        return $absenceReason;
    }

    /**
     * Helper to get absence type name and code for Excel export.
     */
    private function _getAbsenceTypeNameAndCode(Table $AbsenceTypesTable, ?int $absenceTypeId): array
    {
        $absenceType = [];
        if ($absenceTypeId && $absenceTypeId != 0) {
            $result = $AbsenceTypesTable
                ->find()
                ->select([
                    'name' => $AbsenceTypesTable->aliasField('name'),
                    'code' => $AbsenceTypesTable->aliasField('code')
                ])
                ->where([$AbsenceTypesTable->aliasField('id = ') => $absenceTypeId])
                ->first();
            if ($result) {
                $absenceType['name'] = $result->name;
                $absenceType['code'] = $result->code;
            }
        }
        Log::debug("Absence type name and code: " . print_r($absenceType, true));
        return $absenceType;
    }

    /**
     * Helper to set Excel-specific row data.
     */
    private function _setExcelRowData(
        object $row,
        array $data,
        array $absenceReason,
        array $absenceType,
        int $PRESENT,
        int $attendancePeriodId,
        string $attendanceBy
    ): object {
        $row->attendance = '';
        if ($row->is_NoClassScheduled == 1) {
            $row->attendance = 'No scheduled class';
        } elseif (isset($data['absence_type_id']) && ($data['absence_type_id'] == $PRESENT)) {
            $row->attendance = 'Present';
        } elseif (isset($data['absence_type_code']) && ($data['absence_type_code'] == 'EXCUSED' || $data['absence_type_code'] == 'UNEXCUSED')) {
            $row->attendance = 'Absent - ' . ($absenceType['name'] ?? '');
        } elseif (isset($data['absence_type_code']) && $data['absence_type_code'] == 'LATE') {
            $row->attendance = 'Late';
        } else {
            $row->attendance = 'NOTMARKED';
        }

        $row->comment = $data['comment'];
        $row->student_absence_reasons = $absenceReason['name'] ?? null;
        $row->name = ($row['user']['first_name'] ?? '') . ' ' . ($row['user']['last_name'] ?? '');
        $row->class = $row['institution_class']['name'] ?? '';
        $row->date = date("d/m/Y", strtotime($data['date']));
        $row->StudentStatuses = $row['_matchingData']['StudentStatuses']['name'] ?? '';
        $row->studentId = $row['student_id'];
        $row->attendanceBy = $attendanceBy;
        $row->period = "Period " . $attendancePeriodId;
        $row->test = 1; // This seems like a debug flag, consider removing for production
        Log::debug("Excel row data set for student {$row->student_id}: " . print_r($row, true));
        return $row;
    }

    /**
     * Helper to set Excel-specific row data.
     */
    private function __setExcelRowData(
        object $row,
        array $data,
        array $absenceReason,
        array $absenceType,
        int $PRESENT,
        int $attendancePeriodId,
        string $attendanceBy
    ): object {
        $row->attendance = '';
        if ($row->is_NoClassScheduled == 1) {
            $row->attendance = 'No scheduled class';
        } elseif (isset($data['absence_type_id']) && ($data['absence_type_id'] == $PRESENT)) {
            $row->attendance = 'Present';
        } elseif (isset($data['absence_type_code']) && ($data['absence_type_code'] == 'EXCUSED' || $data['absence_type_code'] == 'UNEXCUSED')) {
            $row->attendance = 'Absent - ' . ($absenceType['name'] ?? '');
        } elseif (isset($data['absence_type_code']) && $data['absence_type_code'] == 'LATE') {
            $row->attendance = 'Late';
        } else {
            $row->attendance = 'NOTMARKED';
        }

        $row->comment = $data['comment'];
        $row->student_absence_reasons = $absenceReason['name'] ?? null;
        $row->name = ($row['user']['first_name'] ?? '') . ' ' . ($row['user']['last_name'] ?? '');
        $row->class = $row['institution_class']['name'] ?? '';
        $row->date = date("d/m/Y", strtotime($data['date']));
        $row->StudentStatuses = $row['_matchingData']['StudentStatuses']['name'] ?? '';
        $row->studentId = $row['student_id'];
        $row->attendanceBy = $attendanceBy;
        $row->period = "Period " . $attendancePeriodId;
        $row->test = 1; // This seems like a debug flag, consider removing for production
        Log::debug("Excel row data set for student {$row->student_id}: " . print_r($row, true));
        return $row;
    }

    /**
     * Builds aggregated weekly attendance data.
     * @param array $studentList List of student IDs.
     * @param array $dayList List of days in the week.
     * @param array $periodList List of periods.
     * @param array $markedRecords Marked attendance records.
     * @param array $absenceDetails Absence details.
     * @param string $attendanceBy Attendance type ('subject' or 'period').
     * @param int $subjectId Subject ID.
     * @return array Aggregated attendance data.
     */
    private function _buildWeeklyAttendanceData(
        array $studentList,
        array $dayList,
        array $periodList,
        array $markedRecords,
        array $absenceDetails,
        string $attendanceBy,
        int $subjectId
    ): array {
        $studentAttenanceData = [];
        foreach ($studentList as $studentId) {
            if (!isset($studentAttenanceData[$studentId])) {
                $studentAttenanceData[$studentId] = [];
            }

            foreach ($dayList as $day) {
                $dayOfWeek = $day['day'];
                $date = $day['date'];
                if (!isset($studentAttenanceData[$studentId][$dayOfWeek])) {
                    $studentAttenanceData[$studentId][$dayOfWeek] = [];
                }

                foreach ($periodList as $period) {
                    $periodId = $period['id'];
                    if (!isset($studentAttenanceData[$studentId][$dayOfWeek][$periodId])) {
                        $studentAttenanceData[$studentId][$dayOfWeek][$periodId] = 'NOTMARKED';

                        // Check marked records first
                        foreach ($markedRecords as $entity) {
                            $entityDate = $entity->date->format('Y-m-d');
                            $entityPeriod = $entity->period;
                            $entitySubject = $entity->subject_id;

                            if ($entityDate == $date) {
                                if ($entity->no_scheduled_class == 1) { // POCOR-7929
                                    $studentAttenanceData[$studentId][$dayOfWeek][$periodId] = 'NoScheduledClicked';
                                    break;
                                } elseif ($entityPeriod == $periodId) {
                                    $studentAttenanceData[$studentId][$dayOfWeek][$periodId] = 'PRESENT';
                                    break;
                                } elseif ($entitySubject == $subjectId && $attendanceBy == 'subject') { // POCOR-8874
                                    $studentAttenanceData[$studentId][$dayOfWeek][$periodId] = 'PRESENT';
                                    break;
                                }
                            }
                        }

                        // Override with absence details if present
                        foreach ($absenceDetails as $entity) {
                            $entityDateFormat = $entity->date->format('Y-m-d');
                            $entityStudentId = $entity->student_id;
                            $entityPeriod = $entity->period;
                            $entitySubject = $entity->subject_id;

                            if ($studentId == $entityStudentId && $entityDateFormat == $date) {
                                if ($entityPeriod == $periodId || ($entitySubject == $subjectId && $attendanceBy == 'subject')) { // POCOR-8874
                                    // If in Excel context and absence is Excused/Unexcused, use 'ABSENT', else use code
                                    if (isset($this->request) && ('excel' === ($this->request->getAttribute('params')['pass'][0] ?? null))) {
                                        if ($entity->code == 'EXCUSED' || $entity->code == 'UNEXCUSED') {
                                            $studentAttenanceData[$studentId][$dayOfWeek][$periodId] = 'ABSENT';
                                            break;
                                        } else {
                                            $studentAttenanceData[$studentId][$dayOfWeek][$periodId] = $entity->code;
                                            break;
                                        }
                                    } else {
                                        $studentAttenanceData[$studentId][$dayOfWeek][$periodId] = $entity->code;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        Log::debug("Built weekly attendance data: " . print_r($studentAttenanceData, true));
        return $studentAttenanceData;
    }

    /**
     * Formats a single student's attendance row for the weekly view.
     * @param object $row Student data row.
     * @param array $studentAttenanceData Aggregated attendance data.
     * @param string $weekStartDay Start day of the week.
     * @param string $weekEndDay End day of the week.
     * @param array $periodList List of periods.
     * @param string $attendanceBy Attendance type ('subject' or 'period').
     * @param int $subjectId Subject ID.
     * @param Table $InstitutionSubjectsTable InstitutionSubjects table instance.
     * @return object The modified student data row.
     */
    private function _formatWeeklyStudentAttendanceRow(
        object $row,
        array $studentAttenanceData,
        string $weekStartDay,
        string $weekEndDay,
        array $periodList,
        string $attendanceBy,
        int $subjectId,
        Table $InstitutionSubjectsTable
    ): object {
        $studentId = $row->student_id;
        if (isset($studentAttenanceData[$studentId])) {
            $row->week_attendance = $studentAttenanceData[$studentId];
            $row->current = date("d/m/Y", strtotime($weekStartDay)) . ' - ' . date("d/m/Y", strtotime($weekEndDay));

            if (isset($this->request) && ('excel' === ($this->request->getAttribute('params')['pass'][0] ?? null))) {
                $row->name = ($row['user']['openemis_no'] ?? '') . ' - ' . ($row['user']['first_name'] ?? '') . ' ' . ($row['user']['last_name'] ?? '');

                $periodNames = [];
                foreach ($periodList as $Period) {
                    $periodNames[] = $Period['name'];
                }
                $row->period = implode(" ", $periodNames);

                foreach ($studentAttenanceData[$studentId] as $dayKey => $dayValue) {
                    foreach ($periodList as $periodData) {
                        $periodId = (int) $periodData['id'];
                        $attendanceStatus = $dayValue[$periodId] ?? 'NOTMARKED';

                        if ($attendanceStatus == "NoScheduledClicked") {
                            $attendanceStatus = "No Scheduled Classes";
                        }

                        if ($attendanceBy == 'period') {
                            $row->{'week_attendance_status_' . $dayKey . '-' . $periodData['name']} = $attendanceStatus;
                        } else {
                            $getSubject = $InstitutionSubjectsTable->find('all')
                                ->where([$InstitutionSubjectsTable->aliasField('id') => $subjectId])
                                ->first();
                            $subjectName = $getSubject ? $getSubject->name : 'Unknown Subject';
                            $row->{'week_attendance_status_' . $dayKey . '-' . $subjectName} = $attendanceStatus;
                            $row->subject = $subjectName;
                        }
                    }
                }
            }
        }
        $row->attendanceBy = $attendanceBy;
        Log::debug("Formatted weekly student attendance row for student {$row->student_id}: " . print_r($row, true));
        return $row;
    }

    /**
     * Applies student withdrawal filters to the query.
     * @param Query $query The query object to modify.
     * @param int $institutionId Institution ID.
     * @param int $academicPeriodId Academic Period ID.
     * @param int $educationGradeId Education Grade ID.
     * @param mixed $day The specific day or -1 for weekly view.
     * @param mixed $findDay The day or range of days to find.
     * @param Table $InstitutionStudentsTable InstitutionStudents table instance.
     * @param Table $StudentWithdrawTable StudentWithdraw table instance.
     * @return Query The modified query object.
     */
    private function _applyWithdrawalFilters(
        Query $query,
        int $institutionId,
        int $academicPeriodId,
        int $educationGradeId,
        $day,
        $findDay,
        Table $InstitutionStudentsTable,
        Table $StudentWithdrawTable
    ): Query {
        Log::debug("Applying withdrawal filters for day: " . print_r($day, true) . ", findDay: " . print_r($findDay, true));

        $DayCondititon = [];
        if ($day != -1) {
            // Single day
            if (is_array($findDay)) { // This happens if $day == -1 originally, but then $findDay is set to weekStart/EndDay
                $DayCondititon = [
                    $StudentWithdrawTable->aliasField('effective_date >= ') => $findDay[0],
                    $StudentWithdrawTable->aliasField('effective_date <= ') => $findDay[1]
                ];
            } else {
                $DayCondititon = [$StudentWithdrawTable->aliasField('effective_date <= ') => $findDay];
            }
        } else {
            // Weekly view
            $DayCondititon = [
                $StudentWithdrawTable->aliasField('effective_date >= ') => $findDay[0],
                $StudentWithdrawTable->aliasField('effective_date <= ') => $findDay[1]
            ];
        }
        Log::debug("Day condition for withdrawal: " . print_r($DayCondititon, true));

        $studentWithdrawData = $StudentWithdrawTable->find()
            ->select([
                'student_id' => $StudentWithdrawTable->aliasField('student_id'),
            ])
            ->leftJoin(
                [$InstitutionStudentsTable->getAlias() => $InstitutionStudentsTable->getTable()],
                [
                    $InstitutionStudentsTable->aliasField('student_id = ') . $StudentWithdrawTable->aliasField('student_id'),
                    $InstitutionStudentsTable->aliasField('education_grade_id = ') . $StudentWithdrawTable->aliasField('education_grade_id'),
                    $InstitutionStudentsTable->aliasField('academic_period_id = ') . $StudentWithdrawTable->aliasField('academic_period_id'),
                    $InstitutionStudentsTable->aliasField('institution_id = ') . $StudentWithdrawTable->aliasField('institution_id')
                ]
            )
            ->where([
                $StudentWithdrawTable->aliasField('institution_id') => $institutionId,
                $StudentWithdrawTable->aliasField('academic_period_id') => $academicPeriodId,
                $StudentWithdrawTable->aliasField('education_grade_id') => $educationGradeId,
                $DayCondititon,
                $InstitutionStudentsTable->aliasField('student_status_id !=') => 1 // Status ID 1 typically means CURRENT
            ])->toArray();
        Log::debug("Student withdrawal data: " . print_r($studentWithdrawData, true));

        if (!empty($studentWithdrawData)) {
            $withdrawnStudentIds = [];
            foreach ($studentWithdrawData as $studentVal) {
                $withdrawnStudentIds[] = $studentVal['student_id'];
            }
            $withdrawnStudentIds = empty($withdrawnStudentIds) ? [0] : array_unique($withdrawnStudentIds);
            Log::debug("Withdrawn student IDs: " . print_r($withdrawnStudentIds, true));

            $InstitutionStudentsCurrentTable = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
            $currentStudentsInWithdrawalList = $InstitutionStudentsCurrentTable
                ->find()
                ->select(['student_id' => 'InstitutionStudents.student_id'])
                ->where([
                    $InstitutionStudentsCurrentTable->aliasField('institution_id') => $institutionId,
                    $InstitutionStudentsCurrentTable->aliasField('academic_period_id') => $academicPeriodId,
                    $InstitutionStudentsCurrentTable->aliasField('education_grade_id') => $educationGradeId,
                    $InstitutionStudentsCurrentTable->aliasField('student_status_id') => 1, // CURRENT status
                    $InstitutionStudentsCurrentTable->aliasField('student_id IN') => $withdrawnStudentIds
                ])
                ->enableAutoFields(true)
                ->toArray();
            Log::debug("Current students also in withdrawal list: " . print_r($currentStudentsInWithdrawalList, true));

            $currentStudentIds = [];
            foreach ($currentStudentsInWithdrawalList as $currentStudentVal) {
                $currentStudentIds[] = $currentStudentVal['student_id'];
            }

            $finalExcludedStudentIds = array_diff($withdrawnStudentIds, $currentStudentIds);
            $finalExcludedStudentIds = empty($finalExcludedStudentIds) ? [0] : array_unique($finalExcludedStudentIds);
            Log::debug("Final excluded student IDs: " . print_r($finalExcludedStudentIds, true));

            $query->where([$this->aliasField('student_id NOT IN') => $finalExcludedStudentIds]);
        }
        return $query;
    }

    /**
     * @param Query $query
     * @param $academicPeriodId
     * @param $institutionClassId
     * @param $educationGradeId
     * @param $institutionId
     * @return Query
     */

    private function getAttendanceBasicQuery(
        Query $query,
        $academicPeriodId,
        $institutionClassId,
        $educationGradeId,
        $institutionId
    ) {
        $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
        $Users = TableRegistry::getTableLocator()->get('Security.Users');
        $Classes = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $Statuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
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
                [$Users->getAlias() => $Users->getTable()],
                [
                    $Users->aliasField('id = ') . $this->aliasField('student_id'),
                ]
            )
            ->innerJoin(
                [$Classes->getAlias() => $Classes->getTable()],
                [
                    $Classes->aliasField('id = ') . $this->aliasField('institution_class_id'),
                ]
            )
            ->leftJoin(
                [$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()],
                [
                    $InstitutionStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $InstitutionStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                ]
            )->leftJoin(
                [$Statuses->getAlias() => $Statuses->getTable()],
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
        $InstitutionSubjectStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');
        $query
            ->innerJoin(
                [$InstitutionSubjectStudents->getAlias() => $InstitutionSubjectStudents->getTable()],
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
        $studentWithdraw = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentWithdraw');
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
        $InstitutionStudents = TableRegistry::getTableLocator()->get('institution_students');
        $studentWithdrawData = $studentWithdraw->find()
            ->select([
                'student_id' => 'institution_student_withdraw.student_id',
            ])
            /*POCOR-6062 starts*/
            ->leftJoin([$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()], [
                $InstitutionStudents->aliasField('student_id = ') . $studentWithdraw->aliasField('student_id'),
                $InstitutionStudents->aliasField('education_grade_id = ') . $studentWithdraw->aliasField('education_grade_id'),
                $InstitutionStudents->aliasField('academic_period_id = ') . $studentWithdraw->aliasField('academic_period_id'),
                $InstitutionStudents->aliasField('institution_id = ') . $studentWithdraw->aliasField('institution_id')
            ])/*POCOR-6062 ends*/
            ->where([
                $studentWithdraw->aliasField('institution_id') => $institutionId,
                $studentWithdraw->aliasField('academic_period_id') => $academicPeriodId,
                $studentWithdraw->aliasField('education_grade_id') => $educationGradeId,
                $DayCondititon, //POCOR-7183
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
        $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
        $dayCondition = [
            $InstitutionStudents->aliasField('start_date <= ') => $day,
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
    private function getAttendanceDailyQueryWithDetails(Query $query, $attendancePeriodId, $day, $subjectId, $archive = false)
    {
        $table_name = 'institution_student_absence_details';
        $tableLocator = new TableLocator();
        if (!$archive) {
            $Details = $tableLocator->get($table_name);
        }
        if ($archive) {
            $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
            $table_name = $archiveTableAndConnection[0];
            $Details = $tableLocator->get($table_name);
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
            [$Details->getAlias() => $Details->getTable()],
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
        $tableLocator = new TableLocator();
        if (!$archive) {
            $Details = $tableLocator->get($table_name);
        }
        if ($archive) {
            $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
            $table_name = $archiveTableAndConnection[0];
            $Details = $tableLocator->get($table_name);
        }
        $Types = TableRegistry::getTableLocator()->get('Institution.AbsenceTypes');

        $options = [
            $Types->aliasField('id = ')
                . $Details->aliasField('absence_type_id'),
        ];

        $query->leftJoin(
            [$Types->getAlias() => $Types->getTable()],
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
        $tableLocator = new TableLocator();
        if (!$archive) {
            $Records = $tableLocator->get($table_name);
        }
        if ($archive) {
            $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
            $table_name = $archiveTableAndConnection[0];
            $Records = $tableLocator->get($table_name);
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
            [$Records->getAlias() => $Records->getTable()],
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
        $tableLocator = new TableLocator();
        if (!$archive) {
            $Details = $tableLocator->get($table_name);
        }
        if ($archive) {
            $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
            $table_name = $archiveTableAndConnection[0];
            $Details = $tableLocator->get($table_name);
        }
        $Reasons = TableRegistry::getTableLocator()->get('Institution.StudentAbsenceReasons');
        $options = [
            $Details->aliasField('student_absence_reason_id = ') . $Reasons->aliasField('id'),
        ];
        $query->leftJoin(
            [$Reasons->getAlias() => $Reasons->getTable()],
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
        $Statuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
        $Users = TableRegistry::getTableLocator()->get('Security.Users');
        $Types = TableRegistry::getTableLocator()->get('Institution.AbsenceTypes');
        $Classes = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $Reasons = TableRegistry::getTableLocator()->get('Institution.StudentAbsenceReasons');
        if (!$archive) {
            $Details = TableRegistry::getTableLocator()->get('Institution.institutionStudentAbsenceDetails');
            $Records = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkedRecords');
        }
        if ($archive) {
            $table_name = 'institution_student_absence_details';
            $tableLocator = new TableLocator();
            if ($archive) {
                $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
                $table_name = $archiveTableAndConnection[0];
                $Details = $tableLocator->get($table_name);
            }
            $table_name = 'student_attendance_marked_records';
            if ($archive) {
                $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
                $table_name = $archiveTableAndConnection[0];
                $Records = $tableLocator->get($table_name);
            }
        }
        $first_name = $Users->aliasField('first_name');
        $last_name = $Users->aliasField('last_name');
        $absence_type_id = $Types->aliasField('id');
        $absence_type_code = $Types->aliasField('code');
        $absence_type_name = $Types->aliasField('name');
        $student_absence_reason_id = $Details->aliasField('student_absence_reason_id');
        $query->select(
            [
                $this->aliasField('id'),
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
        $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
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
    private function getWeekDaysAbsenceArray(
        Query $query,
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
        $archive = false
    ) {
        $dayList = $this->getWeekDaysList(
            $academicPeriodId,
            $weekId,
            $institutionId
        );
        $periodList = $this->getPeriodList(
            $institutionClassId,
            $academicPeriodId,
            $day,
            $educationGradeId,
            $weekStartDay,
            $weekEndDay
        );
        $WeekDaysAbsenceArray = [];
        foreach ($dayList as $day) {
            $weekday = $day['day'];
            $date = $day['date'];
            foreach ($periodList as $period) {
                $periodId = $period['id'];
                $not_marked = $this->getNotMarkedDay(
                    $institutionId,
                    $academicPeriodId,
                    $institutionClassId,
                    $educationGradeId,
                    $attendancePeriodId,
                    $subjectId,
                    $date,
                    $archive
                );
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
        $Users = TableRegistry::getTableLocator()->get('Security.Users');
        $Classes = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $first_name = $Users->aliasField('first_name');
        $last_name = $Users->aliasField('last_name');

        $query->select(
            [
                $this->aliasField('id'),
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
        $AcademicPeriodsTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');

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
        $StudentAttendanceMarkTypesTable = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkTypes');

        $periodList = $StudentAttendanceMarkTypesTable
            ->find('PeriodByClass', [
                'institution_class_id' => $institutionClassId,
                'academic_period_id' => $academicPeriodId,
                'day_id' => $day,
                'education_grade_id' => $educationGradeId,
                'week_start_day' => $weekStartDay, //POCOR-7183
                'week_end_day' => $weekEndDay //POCOR-7183
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
    private function getNotMarkedDay(
        $institutionId,
        $academicPeriodId,
        $institutionClassId,
        $educationGradeId,
        $attendancePeriodId,
        $subjectId,
        $day,
        $archive = false
    ) {
        $table_name = 'student_attendance_marked_records';
        $tableLocator = new TableLocator();
        if (!$archive) {
            $Records = $tableLocator->get($table_name);
        }
        if ($archive) {
            $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
            $table_name = $archiveTableAndConnection[0];
            $Records = $tableLocator->get($table_name);
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
