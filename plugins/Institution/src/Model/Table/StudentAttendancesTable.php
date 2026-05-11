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
use Cake\Utility\Inflector;
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
        $day = $options['day_id'];
        $institutionId = $options['institution_id'];
        $institutionClassId = $options['institution_class_id'];
        $educationGradeId = $options['education_grade_id'];
        $academicPeriodId = $options['academic_period_id'];
        $attendancePeriodId = $options['attendance_period_id'];
        $weekId = $options['week_id'];
        $weekStartDay = $options['week_start_day'];
        $weekEndDay = $options['week_end_day'];
        $subjectId = $options['subject_id'];
        $attendanceBy = $options['attendance_by']; // POCOR-8874

        // POCOR-9572: Use Archive pattern for both daily and weekly modes
        $weekly = ($day == -1);
        $daily = !$weekly;
        $archive = false; // We're working with current data, not archives

        // POCOR-9572: Debug logging (commented out for production)
        // Log::debug('[Attendance] ========== START findClassStudentsWithAbsence ==========');
        // Log::debug('[Attendance] Mode: ' . ($daily ? 'DAILY' : 'WEEKLY') . ', Class: ' . $institutionClassId . ', Day: ' . $day);

        // Use proven Archive function pattern for consistency
        $query = $this->getAttendanceBasicQuery(
            $query,
            $academicPeriodId,
            $institutionClassId,
            $educationGradeId,
            $institutionId
        );
        // Log::debug('[Attendance] Step 1 - After getAttendanceBasicQuery: ' . (clone $query)->count() . ' students');

        // Handle subject filter if needed
        if ($subjectId != 0) {
            $query = $this->getAttendanceQueryWithSubjectId($query, $subjectId);
            // Log::debug('[Attendance] Step 2 - After getAttendanceQueryWithSubjectId: ' . (clone $query)->count() . ' students');
        } else {
            $subjectId = null;
        }

        // Filter withdrawn students
        $query = $this->getAttendanceQueryWithoutWithdrawn(
            $query,
            $daily,
            $day,
            $institutionId,
            $academicPeriodId,
            $educationGradeId,
            $weekStartDay,
            $weekEndDay,
            $archive
        );
        // Log::debug('[Attendance] Step 3 - After getAttendanceQueryWithoutWithdrawn: ' . (clone $query)->count() . ' students');

        if ($daily) {
            // --- DAILY MODE: Use Archive pattern ---
            $query = $this->getAttendanceDailyQueryWithDayCondition($query, $day);
            // Log::debug('[Attendance] Step 4 - After getAttendanceDailyQueryWithDayCondition: ' . (clone $query)->count() . ' students');

            $query = $this->getAttendanceDailyQueryWithDetails($query, $attendancePeriodId, $day, $subjectId, $attendanceBy, $archive);
            // Log::debug('[Attendance] Step 5 - After getAttendanceDailyQueryWithDetails: ' . (clone $query)->count() . ' students');

            $query = $this->getAttendanceDailyQueryWithAbsenceTypes($query, $archive);
            $query = $this->getAttendanceDailyQueryWithMarkedRecords($query, $day, $attendancePeriodId, $subjectId, $attendanceBy, $archive);
            $query = $this->getAttendanceDailyQueryWithAbsenceReasons($query, $archive);
            $query = $this->getAttendanceDailySelectFields($query, $day, $archive);
            // Log::debug('[Attendance] Step 6 - After all daily query setup complete');

            // POCOR-9572: Return flat fields - no nested objects
            // Angular will access fields directly: student_name, absence_type_id, etc.

        } else {
            // --- WEEKLY MODE: Continue with weekly-specific setup ---
            // Add week overlap condition
            $query = $this->getOverlapWeekCondition($query, $weekStartDay, $weekEndDay);
            // Log::debug('[Attendance] Step 4 - After getOverlapWeekCondition: ' . (clone $query)->count() . ' students');

            // Get absence data for all days in the week
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
                $attendanceBy, // POCOR-9572
                false // archive = false for current data
            );
            // Log::debug('[Attendance] Step 5 - After getWeekDaysAbsenceArray: got data for ' . count($WeekDaysAbsenceArray) . ' student-day combinations');

            // Build weekly SELECT fields
            $query = $this->getAttendanceWeeklySelectFields($query);
            // Log::debug('[Attendance] Step 6 - After getAttendanceWeeklySelectFields: ' . (clone $query)->count() . ' students');

            // Format results with weekly attendance data
            $query = $this->getAbsenceWeeklyQueryFormatResults($query, $WeekDaysAbsenceArray, $weekStartDay, $weekEndDay);
            // Log::debug('[Attendance] Step 7 - After getAbsenceWeeklyQueryFormatResults: formatResults applied');

            // POCOR-9572: Return flat fields - Angular will access student_name, openemis_no, week_attendance directly
        }

        // Log::debug('[Attendance] ========== END findClassStudentsWithAbsence ==========');
        return $query;
    }
//    public function findClassStudentsWithAbsence(Query $query, array $options)
//    {
//        $institutionId = $options['institution_id'];
//        $institutionClassId = $options['institution_class_id'];
//        $educationGradeId = $options['education_grade_id'];
//        $academicPeriodId = $options['academic_period_id'];
//        $attendancePeriodId = $options['attendance_period_id'];
//        $weekId = $options['week_id'];
//        $weekStartDay = $options['week_start_day'];
//        $weekEndDay = $options['week_end_day'];
//        $day = $options['day_id'];
//        $subjectId = $options['subject_id'];
//        $attendanceBy = $options['attendance_by']; // POCOR-8874
//
//        // Logging can be uncommented for debugging if needed
//        // $this->log("institutionId = $institutionId", 'debug');
//        // $this->log("institutionClassId = $institutionClassId", 'debug');
//        // $this->log("educationGradeId = $educationGradeId", 'debug');
//        // $this->log("academicPeriodId = $academicPeriodId", 'debug');
//        // $this->log("weekId = $weekId", 'debug');
//        // $this->log("weekStartDay = $weekStartDay", 'debug');
//        // $this->log("weekEndDay = $weekEndDay", 'debug');
//        // $this->log("day = $day", 'debug');
//        // $this->log("subjectId = $subjectId", 'debug');
//
//
//        $weekly = $day == -1;
//        $dayly = !$weekly;
//        $query = $this->getAttendanceBasicQueryNew(
//            $query,
//            $academicPeriodId,
//            $institutionClassId,
//            $educationGradeId,
//            $institutionId
//        );
//        if ($subjectId != 0) {
//            $query = $this->getAttendanceQueryWithSubjectId(
//                $query,
//                $subjectId
//            );
//        } else {
//            $subjectId = null;
//        }
//        $query = $this->_filterWithdrawnStudents(
//            $query,
//            $options
//        );
////        $InstitutionSubjectStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');
////        $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.Students');
////        $this->Users = TableRegistry::getTableLocator()->get('Security.Users');
////        /* POCOR-5912 condition for week filter starts */
////        $overlapDateCondition['OR'] = [];
////        $overlapDateCondition['OR'][] = [$InstitutionStudents->aliasField('start_date') . ' >= ' => $weekStartDay, $InstitutionStudents->aliasField('start_date') . ' <= ' => $weekEndDay];
////        $overlapDateCondition['OR'][] = [$InstitutionStudents->aliasField('end_date') . ' >= ' => $weekStartDay, $InstitutionStudents->aliasField('end_date') . ' <= ' => $weekEndDay];
////        $overlapDateCondition['OR'][] = [$InstitutionStudents->aliasField('start_date') . ' <= ' => $weekStartDay, $InstitutionStudents->aliasField('end_date') . ' >= ' => $weekEndDay];
////        /* POCOR-5912 condition for week filter ends */
////        /* POCOR-5919 condition for day filter starts */
////        if ($day != -1) {
////            $conditionQuery = [
////                $InstitutionStudents->aliasField('start_date <= ') => $day,
////                'OR' => [
////                    $InstitutionStudents->aliasField('end_date is ') => null,
////                    $InstitutionStudents->aliasField('end_date >= ') => $day,
////
////                ]
////            ];
////        }
////        /* POCOR-5919 condition for day filter ends */
////        /* POCOR-7956 fetch status starts */
////        $StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
////        $statuses = $StudentStatuses->findCodeList();
////        /* POCOR-7956 fetch status starts */
////        if ($day == -1) {
////            $findDay[] = $weekStartDay;
////            $findDay[] = $weekEndDay;
////        } else {
////            $findDay = $day;
////        }
////        if ($subjectId != 0 || $attendanceBy == 'subject') {
////            $query
////                ->select([
////                    $this->aliasField('academic_period_id'),
////                    $this->aliasField('institution_class_id'),
////                    $this->aliasField('institution_id'),
////                    $this->aliasField('student_id'),
////                    $this->Users->aliasField('id'),
////                    $this->Users->aliasField('openemis_no'),
////                    $this->Users->aliasField('first_name'),
////                    $this->Users->aliasField('middle_name'),
////                    $this->Users->aliasField('third_name'),
////                    $this->Users->aliasField('last_name'),
////                    $this->Users->aliasField('preferred_name'), // POCOR-9011
////                    $this->Users->aliasField('gender_id') // POCOR-9011
////                ])
////                ->contain([$this->Users->getAlias(), 'InstitutionClasses'])
////                ->leftJoin(
////                    [$InstitutionSubjectStudents->getAlias() => $InstitutionSubjectStudents->getTable()],
////                    [
////                        $InstitutionSubjectStudents->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
////                        $InstitutionSubjectStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
////                    ]
////                )
////                //POCOR-5900 start (Filter for check start date of student)
////                ->leftJoin(
////                    [$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()],
////                    [
////                        $InstitutionStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
////                    ]
////                )
////                //POCOR-5900 end
////                ->where([
////                    $this->aliasField('academic_period_id') => $academicPeriodId,
////                    $this->aliasField('institution_class_id') => $institutionClassId,
////                    $this->aliasField('education_grade_id') => $educationGradeId,
////                    $InstitutionSubjectStudents->aliasField('institution_subject_id') => $subjectId,
////                    // //POCOR-5900 condition
////                    $InstitutionStudents->aliasField('institution_id') => $institutionId,
////                    $InstitutionStudents->aliasField('academic_period_id') => $academicPeriodId,
////                    $InstitutionStudents->aliasField('education_grade_id') => $educationGradeId,
////                    $InstitutionStudents->aliasField('student_status_id IN') => [$statuses['REPEATED'], $statuses['CURRENT'], $statuses['TRANSFERRED'], $statuses['WITHDRAWN'], $statuses['GRADUATED'], $statuses['PROMOTED']],
////                    $overlapDateCondition,
////                    $conditionQuery
////                ])
////                ->group([
////                    $InstitutionSubjectStudents->aliasField('student_id')
////                ])
////                ->order([
////                    $this->Users->aliasField('id')
////                ]);
////        } else {
////            $query
////                ->select([
////                    $this->aliasField('academic_period_id'),
////                    $this->aliasField('institution_class_id'),
////                    $this->aliasField('institution_id'),
////                    $this->aliasField('student_id'),
////                    $this->Users->aliasField('id'),
////                    $this->Users->aliasField('openemis_no'),
////                    $this->Users->aliasField('first_name'),
////                    $this->Users->aliasField('middle_name'),
////                    $this->Users->aliasField('third_name'),
////                    $this->Users->aliasField('last_name'),
////                    $this->Users->aliasField('preferred_name'),
////                    $this->Users->aliasField('gender_id'), // POCOR-9011
////                ])
////                ->contain([$this->Users->getAlias(), 'InstitutionClasses'])
////                //POCOR-5900 start (Filter for check start date of student)
////                ->leftJoin(
////                    [$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()],
////                    [
////                        $InstitutionStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
////                    ]
////                )
////                //POCOR-5900 end
////
////                ->where([
////                    $this->aliasField('academic_period_id') => $academicPeriodId,
////                    $this->aliasField('institution_class_id') => $institutionClassId,
////                    $this->aliasField('education_grade_id') => $educationGradeId,
////                    //POCOR-5900 condition
////                    $InstitutionStudents->aliasField('institution_id') => $institutionId,
////                    $InstitutionStudents->aliasField('academic_period_id') => $academicPeriodId,
////                    $InstitutionStudents->aliasField('education_grade_id') => $educationGradeId,
////                    $InstitutionStudents->aliasField('student_status_id IN') => [$statuses['REPEATED'], $statuses['CURRENT'], $statuses['TRANSFERRED'], $statuses['WITHDRAWN'], $statuses['GRADUATED'], $statuses['PROMOTED']],
////                    $overlapDateCondition,
////                    $conditionQuery
////                ])
////                ->group([
////                    $InstitutionStudents->aliasField('student_id')
////                ])
////                ->order([
////                    $this->Users->aliasField('first_name')
////                ]);
////            // echo "<pre>";print_r($query);die;
////        }
////
////        if ($day != -1) {
////            // single day
////            $query
////                ->formatResults(
////                    function (ResultSetInterface $results) use ($findDay, $attendancePeriodId, $subjectId, $educationGradeId, $attendanceBy) { //POCOR-8874 add param attendanceBy
////                        $StudentAbsencesPeriodDetails = TableRegistry::getTableLocator()->get('Institution.StudentAbsencesPeriodDetails');
////                        $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.Students');
////                        return $results->map(function ($row) use ($StudentAbsencesPeriodDetails, $findDay, $attendancePeriodId, $subjectId, $educationGradeId, $InstitutionStudents, $attendanceBy) { // POCOR-8874 add param attendanceBy
////                            $academicPeriodId = $row->academic_period_id;
////                            $institutionClassId = $row->institution_class_id;
////                            $studentId = $row->student_id;
////                            $institutionId = $row->institution_id;
////                            $PRESENT = 0;
////                            // POCOR-9011 start
////                            $row->gender = __('Not Set');
////                            if($row['user']['gender_id'] == 2){
////                                $row->gender = __('Female');
////                            }
////                            if($row['user']['gender_id'] == 1){
////                                $row->gender = __('Male');
////                            }
////                            // POCOR-9011 end
////                            $conditions = [];
////                            $conditions = [
////                                $StudentAbsencesPeriodDetails->aliasField('academic_period_id = ') => $academicPeriodId,
////                                $StudentAbsencesPeriodDetails->aliasField('institution_class_id = ') => $institutionClassId,
////                                $StudentAbsencesPeriodDetails->aliasField('education_grade_id = ') => $educationGradeId,
////                                $StudentAbsencesPeriodDetails->aliasField('student_id = ') => $studentId,
////                                $StudentAbsencesPeriodDetails->aliasField('institution_id = ') => $institutionId,
////                                // $StudentAbsencesPeriodDetails->aliasField('period = ') => $attendancePeriodId, // commented due to POCOR-8874
////                                $StudentAbsencesPeriodDetails->aliasField('date = ') => $findDay,
////                                // $StudentAbsencesPeriodDetails->aliasField('subject_id = ') => $subjectId,
////                            ];
////                            if ($subjectId) {
////                                $SubId[] = [$StudentAbsencesPeriodDetails->aliasField('subject_id = ') => $subjectId];
////
////                                $conditions = array_merge($conditions, $SubId[0]);
////                            }
////                            //POCOR-8874 start
////                            else {
////                                $SubId[] = [$StudentAbsencesPeriodDetails->aliasField('period = ') => $attendancePeriodId];
////                                $conditions = array_merge($conditions, $SubId[0]);
////                            }
////                            //POCOR-8874 end
////                            $absenceReason = array();
////                            $absenceType = array();
////                            $result = $StudentAbsencesPeriodDetails
////                                ->find()
////                                ->contain(['AbsenceTypes'])
////                                ->select([
////                                    $StudentAbsencesPeriodDetails->aliasField('date'),
////                                    $StudentAbsencesPeriodDetails->aliasField('period'),
////                                    $StudentAbsencesPeriodDetails->aliasField('comment'),
////                                    $StudentAbsencesPeriodDetails->aliasField('absence_type_id'),
////                                    $StudentAbsencesPeriodDetails->aliasField('student_absence_reason_id'),
////                                    'AbsenceTypes.code'
////                                ])
////                                ->where($conditions)
////                                ->all();
////                            if (!$result->isEmpty()) {
////                                $entity = $result->first();
////                                $data = [
////                                    'date' => $entity->date,
////                                    'period' => $entity->period,
////                                    'comment' => $entity->comment,
////                                    'absence_type_id' => $entity->absence_type_id ?? 0,
////                                    'student_absence_reason_id' => $entity->student_absence_reason_id,
////                                    'absence_type_code' => $entity->absence_type->code
////                                ];
////                                if (isset($this->request) && ('excel' === $this->request->getAttribute('params')['pass'][0])) {
////                                    $StudentAbsenceReasons = TableRegistry::getTableLocator()->get('Institution.StudentAbsenceReasons');
////                                    if (isset($entity->student_absence_reason_id)) {
////                                        $studentAbsenceReason = $StudentAbsenceReasons
////                                            ->find()
////                                            ->select([
////                                                'name' => $StudentAbsenceReasons->aliasField('name')
////                                            ])
////                                            ->where(
////                                                [$StudentAbsenceReasons->aliasField('id = ') => $entity->student_absence_reason_id]
////                                            )->first();
////                                        if (!empty($studentAbsenceReason)) {
////                                            $absenceReason['name'] = $studentAbsenceReason->name;
////                                        }
////                                    }
////                                    $AbsenceTypes = TableRegistry::getTableLocator()->get('Institution.AbsenceTypes');
////                                    if (isset($entity->absence_type_id) && $entity->absence_type_id != 0) {
////                                        $absenceType = $AbsenceTypes
////                                            ->find()
////                                            ->select([
////                                                'name' => $AbsenceTypes->aliasField('name'),
////                                                'code' => $AbsenceTypes->aliasField('code')
////                                            ])
////                                            ->where([$AbsenceTypes->aliasField('id = ') => $entity->absence_type_id])->first();
////                                        if (!empty($absenceType)) {
////                                            $absenceType['name'] = $absenceType->name;
////                                            $absenceType['code'] = $absenceType->code;
////                                        }
////                                    }
////                                }
////                            } else {
////                                // $StudentAttendanceMarkedRecords = TableRegistry::getTableLocator()->get('Institution.StudentAttendanceMarkedRecords');
////                                $StudentAttendanceMarkedRecords = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkedRecords');
////                                $isMarkedRecords = $StudentAttendanceMarkedRecords
////                                    ->find()
////                                    ->select([
////                                        $StudentAttendanceMarkedRecords->aliasField('date'),
////                                        $StudentAttendanceMarkedRecords->aliasField('period')
////                                    ])
////                                    //POCOR-5900 start (Filter for check start date of student)
////                                    ->leftJoin(
////                                        [$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()],
////                                        [
////                                            $InstitutionStudents->aliasField('institution_id = ') . $StudentAttendanceMarkedRecords->aliasField('institution_id'),
////                                        ]
////                                    )
////                                    //POCOR-5900 end
////                                    ->where([
////                                        $StudentAttendanceMarkedRecords->aliasField('academic_period_id = ') => $academicPeriodId,
////                                        $StudentAttendanceMarkedRecords->aliasField('institution_class_id = ') => $institutionClassId,
////                                        $StudentAttendanceMarkedRecords->aliasField('education_grade_id = ') => $educationGradeId,
////                                        $StudentAttendanceMarkedRecords->aliasField('institution_id = ') => $institutionId,
////                                        $StudentAttendanceMarkedRecords->aliasField('date = ') => $findDay,
////                                        $StudentAttendanceMarkedRecords->aliasField('subject_id = ') => $subjectId,
////                                        $InstitutionStudents->aliasField('start_date') . ' <= ' => $findDay
////                                    ])->toArray();
////
////                                if (!empty($isMarkedRecords)) {
////                                    $data = [
////                                        'date' => $findDay,
////                                        'period' => (int) $attendancePeriodId,
////                                        'comment' => null,
////                                        'absence_type_id' => $PRESENT,
////                                        'student_absence_reason_id' => null,
////                                        'absence_type_code' => null
////                                    ];
////                                } else {
////                                    $data = [
////                                        'date' => $findDay,
////                                        'period' => (int) $attendancePeriodId,
////                                        'comment' => null,
////                                        'absence_type_id' => 0,
////                                        'student_absence_reason_id' => null,
////                                        'absence_type_code' => null
////                                    ];
////                                }
////                            }
////                            $row->institution_student_absences = $data;
////                            $StudentAttendanceMarkedRecords = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkedRecords');
////                            $getRecord = $StudentAttendanceMarkedRecords->find('all')
////                                ->where([
////                                    $StudentAttendanceMarkedRecords->aliasField('institution_class_id') => $institutionClassId,
////                                    $StudentAttendanceMarkedRecords->aliasField('education_grade_id') => $educationGradeId,
////                                    $StudentAttendanceMarkedRecords->aliasField('institution_id') => $institutionId,
////                                    $StudentAttendanceMarkedRecords->aliasField('academic_period_id') => $academicPeriodId,
////                                    $StudentAttendanceMarkedRecords->aliasField('date') => $findDay,
////                                    $StudentAttendanceMarkedRecords->aliasField('no_scheduled_class') => 1,
////                                    $StudentAttendanceMarkedRecords->aliasField('period IS') => $attendancePeriodId //POCOR-8383
////                                ])->first();
////                            if (!empty($getRecord)) {
////                                $row->is_NoClassScheduled = 1;
////                            } else {
////                                $row->is_NoClassScheduled = 0;
////                            }
////                            //POCOR-8874 start
////                            $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
////                            $getSubject = $InstitutionSubjects->find('all')
////                                ->where([
////                                    $InstitutionSubjects->aliasField('id') => $subjectId,
////                                ])->first();
////                            $row->subject = $getSubject->name;
////                            //POCOR-8874 end
////                            if (isset($this->request) && ('excel' === $this->request->getAttribute('params')['pass'][0])) {
////
////                                $row->attendance = '';
////
////                                if ($row->is_NoClassScheduled == 1) { //POCOR-7929
////                                    $row->attendance = 'No scheduled class';
////                                } else if (isset($data['absence_type_id']) && ($data['absence_type_id'] == $PRESENT)) {
////                                    $row->attendance = 'Present';
////                                } else if (isset($data['absence_type_code']) && ($data['absence_type_code'] == 'EXCUSED' || $data['absence_type_code'] == 'UNEXCUSED')) {
////                                    $row->attendance = 'Absent - ' . (isset($absenceType['name'])) ? $absenceType['name'] : '';
////                                } else if (isset($data['absence_type_code']) && $data['absence_type_code'] == 'LATE') {
////                                    $row->attendance = 'Late';
////                                } else {
////                                    $row->attendance = 'NOTMARKED';
////                                }
////                                $row->comment = $data['comment'];
////                                $row->student_absence_reasons = (isset($absenceReason['name'])) ? $absenceReason['name'] : NULL;
////                                $row->name = $row['user']['first_name'] . ' ' . $row['user']['last_name'];
////                                $row->class = $row['institution_class']['name'];
////                                $row->date = date("d/m/Y", strtotime($findDay));
////                                $row->StudentStatuses = $row['_matchingData']['StudentStatuses']['name'];
////                                $row->studentId = $row['student_id'];
////                                $row->attendanceBy = $attendanceBy; //POCOR-8874
////                                $row->period = "Period " . $attendancePeriodId; //POCOR-8874
////                                $row->test = 1;
////                            }
////                            return $row;
////                        });
////                    }
////                );
////        } else {
////            // all day
////            $StudentAttendanceMarkTypesTable = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkTypes');
////            $AcademicPeriodsTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
////            $periodList = $StudentAttendanceMarkTypesTable
////                ->find('PeriodByClass', [
////                    'institution_class_id' => $institutionClassId,
////                    'academic_period_id' => $academicPeriodId,
////                    'day_id' => $day,
////                    'education_grade_id' => $educationGradeId,
////                    'week_start_day' => $weekStartDay, //POCOR-7183
////                    'week_end_day' => $weekEndDay //POCOR-7183
////                ])->toArray();
////
////            $dayList = $AcademicPeriodsTable
////                ->find('DaysForPeriodWeek', [
////                    'academic_period_id' => $academicPeriodId,
////                    'week_id' => $weekId,
////                    'institution_id' => $institutionId,
////                    'exclude_all' => true
////                ])->toArray();
////
////            $studentListResult = $this
////                ->find('list', [
////                    'keyField' => 'student_id',
////                    'valueField' => 'student_id'
////                ])
////                ->matching($this->StudentStatuses->getAlias(), function ($q) {
////                    return $q->where([
////                        $this->StudentStatuses->aliasField('code') => 'CURRENT'
////                    ]);
////                })
////                ->where([
////                    $this->aliasField('academic_period_id') => $academicPeriodId,
////                    $this->aliasField('institution_class_id') => $institutionClassId,
////                ])->all();
////            if (!$studentListResult->isEmpty()) {
////                $studentList = $studentListResult->toArray();
////                //                $this->log('$studentList','debug');
////                //                $this->log($studentList,'debug');
////                $StudentAbsencesPeriodDetails = TableRegistry::getTableLocator()->get('Institution.StudentAbsencesPeriodDetails');
////                $StudentAttendanceMarkedRecords = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkedRecords');
////                if (empty($studentList)) { //POCOR-8022
////                    $studentList = [0];
////                }
////                $result = $StudentAbsencesPeriodDetails
////                    ->find()
////                    ->contain(['AbsenceTypes'])
////                    ->select([
////                        $StudentAbsencesPeriodDetails->aliasField('student_id'),
////                        $StudentAbsencesPeriodDetails->aliasField('date'),
////                        $StudentAbsencesPeriodDetails->aliasField('period'),
////                        $StudentAbsencesPeriodDetails->aliasField('subject_id'), //POCOR-8874
////                        $StudentAbsencesPeriodDetails->aliasField('absence_type_id'),
////                        'code' => 'AbsenceTypes.code'
////                    ])
////                    ->where([
////                        $StudentAbsencesPeriodDetails->aliasField('academic_period_id = ') => $academicPeriodId,
////                        $StudentAbsencesPeriodDetails->aliasField('institution_class_id = ') => $institutionClassId,
////                        $StudentAbsencesPeriodDetails->aliasField('education_grade_id = ') => $educationGradeId,
////                        $StudentAbsencesPeriodDetails->aliasField('student_id IN ') => $studentList,
////                        $StudentAbsencesPeriodDetails->aliasField('institution_id = ') => $institutionId,
////                        $StudentAbsencesPeriodDetails->aliasField('subject_id = ') => $subjectId,
////                        // $StudentAbsencesPeriodDetails->aliasField('period = ') => $attendancePeriodId, //POCOR-8874
////                        'AND' => [
////                            $StudentAbsencesPeriodDetails->aliasField('date >= ') => $weekStartDay,
////                            $StudentAbsencesPeriodDetails->aliasField('date <= ') => $weekEndDay,
////                        ]
////                    ])->toArray();
////
////                $isMarkedRecords = $StudentAttendanceMarkedRecords
////                    ->find()
////                    ->select([
////                        $StudentAttendanceMarkedRecords->aliasField('date'),
////                        $StudentAttendanceMarkedRecords->aliasField('period'),
////                        $StudentAttendanceMarkedRecords->aliasField('subject_id'), //POCOR-8874
////                        $StudentAttendanceMarkedRecords->aliasField('no_scheduled_class') //POCOR-7929
////                    ])
////                    ->where([
////                        $StudentAttendanceMarkedRecords->aliasField('academic_period_id = ') => $academicPeriodId,
////                        $StudentAttendanceMarkedRecords->aliasField('institution_class_id = ') => $institutionClassId,
////                        $StudentAttendanceMarkedRecords->aliasField('education_grade_id = ') => $educationGradeId,
////                        $StudentAttendanceMarkedRecords->aliasField('institution_id = ') => $institutionId,
////                        $StudentAttendanceMarkedRecords->aliasField('subject_id = ') => $subjectId,
////                        // $StudentAttendanceMarkedRecords->aliasField('period = ') => $attendancePeriodId, //POCOR-8874
////                        $StudentAttendanceMarkedRecords->aliasField('date >= ') => $weekStartDay,
////                        $StudentAttendanceMarkedRecords->aliasField('date <= ') => $weekEndDay
////                    ])->toArray();
////
////                $studentAttenanceData = [];
////                foreach ($studentList as $value) {
////                    $studentId = $value;
////                    if (!isset($studentAttenanceData[$studentId])) {
////                        $studentAttenanceData[$studentId] = [];
////                    }
////
////                    foreach ($dayList as $day) {
////                        $dayId = $day['day'];
////                        $date = $day['date'];
////                        if (!isset($studentAttenanceData[$studentId][$dayId])) {
////                            $studentAttenanceData[$studentId][$dayId] = [];
////                        }
////
////                        // print_r($periodList);die;
////                        foreach ($periodList as $period) {
////                            $periodId = $period['id'];
////                            if (!isset($studentAttenanceData[$studentId][$dayId][$periodId])) {
////                                $studentAttenanceData[$studentId][$dayId][$periodId] = 'NOTMARKED';
////                                if (!empty($isMarkedRecords)) { //POCOR-7183 add if check isMarkedRecords condition not empty
////                                    foreach ($isMarkedRecords as $entity) {
////                                        $entityDate = $entity->date->format('Y-m-d');
////                                        $entityPeriod = $entity->period;
////                                        $entitySubject = $entity->subject_id; //POCOR-8874
////
////                                        //POCOR-7929 start
////                                        if ($entityDate == $date && $entity->no_scheduled_class == 1) {
////                                            $studentAttenanceData[$studentId][$dayId][$periodId] = 'NoScheduledClicked';
////                                            break;
////                                        } //POCOR-7929 end
////                                        else if ($entityDate == $date && $entityPeriod == $periodId) {
////                                            $studentAttenanceData[$studentId][$dayId][$periodId] = 'PRESENT';
////                                            break;
////                                        }
////                                        //POCOR-8874 start
////                                        else if ($entityDate == $date && $entitySubject == $subjectId && $attendanceBy == 'subject') {
////                                            $studentAttenanceData[$studentId][$dayId][$periodId] = 'PRESENT';
////                                            break;
////                                        }
////                                        //POCOR-8874 end
////                                    }
////                                }
////                            }
////                            if (!empty($result)) { //POCOR-7183 add if check result condition not empty
////                                foreach ($result as $entity) {
////                                    $entityDateFormat = $entity->date->format('Y-m-d');
////                                    $entityStudentId = $entity->student_id;
////                                    $entityPeriod = $entity->period;
////                                    $entitySubject = $entity->subject_id; //POCOR-8874
////
////                                    if ($studentId == $entityStudentId && $entityDateFormat == $date && ($entityPeriod == $periodId || ($entitySubject == $subjectId && $attendanceBy == 'subject'))) { //POCOR-8874 add condition to check subject id
////                                        if (isset($this->request) && ('excel' === $this->request->pass[0])) {
////                                            if ($entity->code == 'EXCUSED' || $entity->code == 'UNEXCUSED') {
////                                                $studentAttenanceData[$studentId][$dayId][$periodId] = 'ABSENT';
////                                                break;
////                                            } else {
////                                                $studentAttenanceData[$studentId][$dayId][$periodId] = $entity->code;
////                                                break;
////                                            }
////                                        } else {
////                                            $studentAttenanceData[$studentId][$dayId][$periodId] = $entity->code;
////                                            break;
////                                        }
////                                    }
////                                }
////                            }
////                        }
////                    }
////                }
////
////                $query
////                    ->formatResults(function (ResultSetInterface $results) use ($studentAttenanceData, $weekStartDay, $weekEndDay, $periodList, $attendanceBy,$subjectId) { //POCOR-8874 add params attendanceBy and subjectId
////                        return $results->map(function ($row) use ($studentAttenanceData, $weekStartDay, $weekEndDay, $periodList, $attendanceBy,$subjectId) { //POCOR-8874 add params attendanceBy and subjectId
////                            $studentId = $row->student_id;
////                            if (isset($studentAttenanceData[$studentId])) {
////                                $row->week_attendance = $studentAttenanceData[$studentId];
////
////                                $row->current = date("d/m/Y", strtotime($weekStartDay)) . ' - ' . date("d/m/Y", strtotime($weekEndDay));
////                                // print_r($row);die;
////                                if (isset($this->request) && ('excel' === $this->request->getAttribute('params')['pass'][0])) { //POCOR-8874
////                                    // if (isset($this->request) && ('excel' === $this->request->pass[0])) { //POCOR -8874 commented this line because it is not working
////                                    $row->name = $row['user']['openemis_no'] . ' - ' . $row['user']['first_name'] . ' ' . $row['user']['last_name'];
////
////                                    foreach ($periodList as $Period) {
////                                        $row->period .= $Period['name']." "; //POCOR-8874
////                                    }
////
////                                    foreach ($studentAttenanceData[$studentId] as $key => $value) {
////
////                                        //POCOR-7929 start
////                                        foreach ($periodList as $Key => $PeriodData) {
////                                            $id = (int) $PeriodData['id'];
////                                            if ($value[$id] == "NoScheduledClicked") {
////                                                $value[$id] = "No Scheduled Classes";
////                                            }
////                                            //POCOR-8874 start
////                                            if ($attendanceBy == 'period') {
////                                                $row->{'week_attendance_status_' . $key . '-' . $PeriodData['name']} = $value[$id];
////                                                //POCOR-7929 end
////                                            } else {
////                                                $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
////                                                $getSubject = $InstitutionSubjects->find('all')
////                                                    ->where([
////                                                        $InstitutionSubjects->aliasField('id') => $subjectId,
////                                                    ])->first();
////                                                $row->{'week_attendance_status_' . $key . '-' . $getSubject->name} = $value[$id];
////                                                //POCOR-7929 end
////                                                $row->subject = $getSubject->name; //POCOR-8874
////                                            }
////                                            //POCOR-8874 end
////                                        }
////                                    }
////                                }
////                            }
////                            $row->attendanceBy = $attendanceBy; //POCOR-8874
////
////                            return $row;
////                        });
////                    });
////            }
////        }
////        //POCOR-6547[START]
////        if ($day != -1) {
////            $studentId = [];
////            // $studentWithdraw = TableRegistry::getTableLocator()->get('institution_student_withdraw');
////            $studentWithdraw = TableRegistry::getTableLocator()->get('Institution.StudentWithdraw');
////
////            //POCOR-7183 starts
////            if (!empty($findDay[0]) && !empty($findDay[1]) && !empty($day['date'])) {
////                $DayCondititon = [
////                    $studentWithdraw->aliasField('effective_date >= ') => $findDay[0],
////                    $studentWithdraw->aliasField('effective_date <= ') => $findDay[1]
////                ];
////            } else {
////                $DayCondititon = [$studentWithdraw->aliasField('effective_date <= ') => $findDay];
////            } //POCOR-7183 ends
////            $studentWithdrawData = $studentWithdraw->find()
////                ->select([
////                    // 'student_id' => $InstitutionStudents->aliasField('student_id'),
////                ])
////                /*POCOR-6062 starts*/
////                ->leftJoin([$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()], [
////                    $InstitutionStudents->aliasField('student_id = ') . $studentWithdraw->aliasField('student_id'),
////                    $InstitutionStudents->aliasField('education_grade_id = ') . $studentWithdraw->aliasField('education_grade_id'),
////                    $InstitutionStudents->aliasField('academic_period_id = ') . $studentWithdraw->aliasField('academic_period_id'),
////                    $InstitutionStudents->aliasField('institution_id = ') . $studentWithdraw->aliasField('institution_id')
////                ])/*POCOR-6062 ends*/
////                ->where([
////                    $studentWithdraw->aliasField('institution_id') => $institutionId,
////                    $studentWithdraw->aliasField('academic_period_id') => $academicPeriodId,
////                    $studentWithdraw->aliasField('education_grade_id') => $educationGradeId,
////                    // $studentWithdraw->aliasField('effective_date >= ') => $day,
////                    $DayCondititon, //POCOR-7183
////                    $InstitutionStudents->aliasField('student_status_id !=') => 1 //POCOR-6062
////                ])->toArray();
////        } else {
////            $studentId = [];
////            // $studentWithdraw = TableRegistry::getTableLocator()->get('institution_student_withdraw');
////            $studentWithdraw = TableRegistry::getTableLocator()->get('Institution.StudentWithdraw');
////            $studentWithdrawData = $studentWithdraw->find()
////                ->select([
////                    'student_id' => $InstitutionStudents->aliasField('student_id'),
////                ])
////                /*POCOR-6062 starts*/
////                ->leftJoin([$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()], [
////                    $InstitutionStudents->aliasField('student_id = ') . $studentWithdraw->aliasField('student_id'),
////                    $InstitutionStudents->aliasField('education_grade_id = ') . $studentWithdraw->aliasField('education_grade_id'),
////                    $InstitutionStudents->aliasField('academic_period_id = ') . $studentWithdraw->aliasField('academic_period_id'),
////                    $InstitutionStudents->aliasField('institution_id = ') . $studentWithdraw->aliasField('institution_id')
////                ])/*POCOR-6062 ends*/
////                ->where([
////                    $studentWithdraw->aliasField('institution_id') => $institutionId,
////                    $studentWithdraw->aliasField('academic_period_id') => $academicPeriodId,
////                    $studentWithdraw->aliasField('education_grade_id') => $educationGradeId,
////                    //$studentWithdraw->aliasField('effective_date >= ') => $day,
////                    // $studentWithdraw->aliasField('effective_date <= ') => $findDay,
////                    $InstitutionStudents->aliasField('student_status_id !=') => 1 //POCOR-6062
////                ])
////                ->toArray();
////        }
////        //POCOR-6547[END]
////        if ($studentWithdrawData) {
////            $studentId = [];
////            $WithDrawstudentId = [];
////            $CurrentStudentId = [];
////            $InstitutionStudentsCurrentData = []; //POCOR-8022
////            $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents'); //POCOR-7902
////            foreach ($studentWithdrawData as $studenetVal) {
////                $WithDrawstudentId[] = $studenetVal['student_id'];
////            }
////            if (empty($WithDrawstudentId)) { //POCOR-8022
////                $WithDrawstudentId = [0];
////            }
////            //            $this->log('$WithDrawstudentId', 'debug');
////            //            $this->log($WithDrawstudentId, 'debug');
////            //POCOR-7902 start
////            if (!empty($WithDrawstudentId)) { //POCOR-8022
////                $whereWDR = [
////                    $InstitutionStudents->aliasField('institution_id') => $institutionId,
////                    $InstitutionStudents->aliasField('academic_period_id') => $academicPeriodId,
////                    $InstitutionStudents->aliasField('education_grade_id') => $educationGradeId,
////                    $InstitutionStudents->aliasField('student_status_id') => 1,
////                    $InstitutionStudents->aliasField('student_id IN') => $WithDrawstudentId
////                ];
////                //                $this->log('$whereWDR', 'debug');
////                //                $this->log($whereWDR, 'debug');
////                $InstitutionStudentsCurrentData = $InstitutionStudents
////                    ->find()
////                    ->select([
////                        'student_id' => 'InstitutionStudents.student_id'
////                    ])
////                    ->where($whereWDR) //POCOR-8022
////                    ->enableAutoFields(true)
////                    ->toArray();
////            }
////            if (!empty($InstitutionStudentsCurrentData)) { //POCOR-8022
////                foreach ($InstitutionStudentsCurrentData as $CurrentstudenetVal) {
////                    $CurrentStudentId[] = $CurrentstudenetVal['student_id'];
////                }
////            }
////            $studentId = array_diff($WithDrawstudentId, $CurrentStudentId); //POCOR-7902 end
////            if (empty($studentId)) { //POCOR-8022
////                $studentId = [0];
////            }
////            $whereWDR2 = [$this->aliasField('student_id NOT IN') => $studentId]; //POCOR-8022
////            //            $this->log('$whereWDR', 'debug');
////            //            $this->log($whereWDR, 'debug');
////            $query->where($whereWDR2); //POCOR-8022
////
////        }
//        return $query;
//    }

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
        $attendanceBy = $options['attendance_by'] ?? 'period'; // POCOR-9572
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

            $query = $this->getAttendanceDailyQueryWithDetails($query, $attendancePeriodId, $day, $subjectId, $attendanceBy, $archive);
            //            $this->log("step 6", 'debug');

            $query = $this->getAttendanceDailyQueryWithAbsenceTypes($query, $archive);
            //            $this->log("step 7", 'debug');

            $query = $this->getAttendanceDailyQueryWithMarkedRecords($query, $day, $attendancePeriodId, $subjectId, $attendanceBy, $archive);
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
                $attendanceBy, // POCOR-9572
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
            'key' => 'StudentAttendances.student_name',
            'field' => 'student_name',
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
                                'field' => 'week_attendance_status_' . $options[$value] . '-' . $PeriodData['id'],
                                'type' => 'string',
                                'label' => $options[$value] . '-' . $PeriodData['name']
                            ];
                        }
                        //POCOR-7929 end
                    } else {
                        $newArray[] = [
                            'key' => 'StudentAttendances.week_attendance_status_' . $options[$value] . '-' . $getSubject->name,
                            'field' => 'week_attendance_status_' . $options[$value] . '-' . $getSubject->id,
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

        // POCOR-9572: Execute query once and cache results for all onExcelGet* methods
        $query = $this->findClassStudentsWithAbsence($sheet['query'], $options);
        $this->_absenceDataCache = [];

        if ($query) {
            $results = $query->all();

            foreach ($results as $row) {
                $studentId = $row->student_id;
                $date = $row->date ?? '';
                $this->_absenceDataCache[$studentId][$date] = $row;
            }
        }
    }

    // POCOR-9572: Get attendance status for Excel export
    public function onExcelGetAttendance(EventInterface $event, Entity $entity)
    {
        $date = $entity->date ?? null;
        if (isset($this->_absenceDataCache[$entity->student_id][$date])) {
            $row = $this->_absenceDataCache[$entity->student_id][$date];
            // marked_date is NULL when attendance was never marked for this day.
            // Only show a status when the teacher actually submitted attendance.
            if (empty($row->marked_date)) {
                return '';
            }
            $absenceTypeName = $row->absence_type_name ?? 'Present';
            return __($absenceTypeName);
        }
        return ''; // No record in cache — attendance not marked
    }

    // POCOR-9572: Excel export methods for flat field structure
    public function onExcelGetAttendanceBy(EventInterface $event, Entity $entity)
    {
        // Get attendance mode from request
        $attendanceBy = $this->request->getQuery()['attendance_by'] ?? 'period';
        return $attendanceBy;
    }

    public function onExcelGetPeriod(EventInterface $event, Entity $entity)
    {
        // POCOR-9572: Use shared cache from onExcelUpdateFields
        $date = $entity->date ?? null;
        if (isset($this->_absenceDataCache[$entity->student_id][$date])) {
            $row = $this->_absenceDataCache[$entity->student_id][$date];
            // POCOR-9643 return blank when attendance was never marked for this day
            if (empty($row->marked_date)) {
                return '';
            }
            $period = $row->period ?? 1;
            return 'Period ' . $period;
        }
        return ''; // attendance not marked
    }

    public function onExcelGetSubject(EventInterface $event, Entity $entity)
    {
        // Get subject_id from request
        $subjectId = $this->request->getQuery()['subject_id'] ?? 0;

        if ($subjectId != 0) {
            $InstitutionSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjects');
            $getSubject = $InstitutionSubjects->find('all')
                ->where([
                    $InstitutionSubjects->aliasField('id') => $subjectId,
                ])->first();

            if ($getSubject) {
                return $getSubject->name;
            }
        }

        return '';
    }

    public function onExcelGetStudentStatuses(EventInterface $event, Entity $entity)
    {
        $date = $entity->date ?? null;
        if (isset($this->_absenceDataCache[$entity->student_id][$date])) {
            $row = $this->_absenceDataCache[$entity->student_id][$date];
            return $row->student_status ?? '';
        }
        return '';
    }

    public function onExcelGetClass(EventInterface $event, Entity $entity)
    {
        // Get cached data for this student
        static $absenceDataCache = null;
        if ($absenceDataCache === null) {
            $absenceDataCache = [];
            $query = $this->_absenceData;
            if ($query) {
                $results = $query->all();
                foreach ($results as $row) {
                    $studentId = $row->student_id;
                    $date = $row->date ?? '';
                    $absenceDataCache[$studentId][$date] = $row;
                }
            }
        }
        $date = $entity->date ?? null;
        if (isset($this->_absenceDataCache[$entity->student_id][$date])) {
            $row = $this->_absenceDataCache[$entity->student_id][$date];
            return $row->class_name ?? '';
        }
        return '';
    }

    public function onExcelGetStudentAbsenceReasons(EventInterface $event, Entity $entity)
    {
        // POCOR-9572: Use shared cache from onExcelUpdateFields
        $date = $entity->date ?? null;
        if (isset($this->_absenceDataCache[$entity->student_id][$date])) {
            $row = $this->_absenceDataCache[$entity->student_id][$date];
            return $row->student_absence_reason ?? '';
        }
        return '';
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
//                $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
//                $systemDateFormat = $ConfigItems->value('date_format') ?: 'Y-m-d';

                // Convert to Chronos (safe DateTime subclass)
                $date = Chronos::createFromFormat('Y-m-d', $rawDate);
//                $date = Chronos::createFromFormat($systemDateFormat, $rawDate);
                $normalizedDate = $date->format('Y-m-d');
            } catch (\Exception $e) {
//                Log::warning("Invalid date format in Attendance params: '$rawDate' using format '$systemDateFormat'");
                $normalizedDate = $rawDate; // fallback — still use raw string, but might fail later
            }
        }

        // POCOR-9572: Determine period based on attendance_by mode
        // Subject-based: period = 0, subject_id = actual ID
        // Period-based: period = actual ID, subject_id = 0
        $attendanceBy = $options['attendance_by'] ?? 'period';
        $period = ($attendanceBy === 'subject') ? 0 : (int)$options['attendance_period_id'];

        $p = [
            'institution_id'       => (int)$options['institution_id'],
            'institution_class_id' => (int)$options['institution_class_id'],
            'education_grade_id'   => (int)$options['education_grade_id'],
            'academic_period_id'   => (int)$options['academic_period_id'],
            'period'               => $period,
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
     * @param Query $query
     * @param $academicPeriodId
     * @param $institutionClassId
     * @param $educationGradeId
     * @param $institutionId
     * @return Query
     */

    private function getAttendanceBasicQueryNew(
        Query $query,
              $academicPeriodId,
              $institutionClassId,
              $educationGradeId,
              $institutionId
    ) {
        $InstitutionStudents = self::getDynamicTableInstance('institution_students');
        $Users = self::getDynamicTableInstance('security_users');
        $Statuses = self::getDynamicTableInstance('student_statuses');
        $query
            ->select([
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_class_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('student_id'),
                $Users->aliasField('id'),
                $Users->aliasField('id'),
                $Users->aliasField('openemis_no'),
                $Users->aliasField('first_name'),
                $Users->aliasField('middle_name'),
                $Users->aliasField('third_name'),
                $Users->aliasField('last_name'),
                $Users->aliasField('preferred_name'),
                $InstitutionStudents->aliasField('student_status_id'),
                $Statuses->aliasField('name')
            ])
            ->innerJoin(
                [$Users->getAlias() => $Users->getTable()],
                [
                    $Users->aliasField('id = ') . $this->aliasField('student_id'),
                ]
            )
            ->innerJoin(
                [$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()],
                [
                    $InstitutionStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $InstitutionStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                ]
            )->innerJoin(
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
                $Statuses->aliasField('code in ') => ['REPEATED', 'CURRENT', 'TRANSFERRED','WITHDRAWN','GRADUATED','PROMOTED'],
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
                $Users->aliasField('preferred_name'),
                $Users->aliasField('gender_id') // POCOR-9572: Add gender field
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
        $studentWithdraw = self::getDynamicTableInstance('institution_student_withdraw');
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
        $InstitutionStudents = self::getDynamicTableInstance('institution_students');
        $studentWithdrawData = $studentWithdraw->find()
            ->select([
                'student_id' => $studentWithdraw->aliasField('student_id')
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
                $InstitutionStudents->aliasField('student_status_id !=') => 1 ,//POCOR-6062
                //POCOR-9667 Only consider withdrawn rows if there is no active record for the current year
                $InstitutionStudents->aliasField('start_date <=') => $dayly ? $day : $weekEndDay,
                'OR' => [
                    $InstitutionStudents->aliasField('end_date IS') => null,
                    $InstitutionStudents->aliasField('end_date >=') => $dayly ? $day : $weekStartDay
                ]
            ])
            ->toArray();
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
    
    // private function getAttendanceQueryWithoutWithdrawnbkp(Query $query, $dayly, $day, $institutionId, $academicPeriodId, $educationGradeId, $weekStartDay, $weekEndDay, $archive = false)
    // {
    //     if ($archive) {
    //         return $query;
    //     }
    //     $studentWithdraw = self::getDynamicTableInstance('institution_student_withdraw');
    //     if ($dayly) {
    //         $DayCondititon = [$studentWithdraw->aliasField('effective_date <= ') => $day];
    //     }
    //     if (!$dayly) {
    //         $DayCondititon = [
    //             $studentWithdraw->aliasField('effective_date >= ') => $weekStartDay,
    //             $studentWithdraw->aliasField('effective_date <= ') => $weekEndDay
    //         ];
    //     }
    //     $withdrawStudentIds = [];
    //     $InstitutionStudents = self::getDynamicTableInstance('institution_students');
    //     $studentWithdrawData = $studentWithdraw->find()
    //         ->select([
    //             'student_id' => $studentWithdraw->aliasField('student_id')
    //         ])
    //         /*POCOR-6062 starts*/
    //         ->leftJoin([$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()], [
    //             $InstitutionStudents->aliasField('student_id = ') . $studentWithdraw->aliasField('student_id'),
    //             $InstitutionStudents->aliasField('education_grade_id = ') . $studentWithdraw->aliasField('education_grade_id'),
    //             $InstitutionStudents->aliasField('academic_period_id = ') . $studentWithdraw->aliasField('academic_period_id'),
    //             $InstitutionStudents->aliasField('institution_id = ') . $studentWithdraw->aliasField('institution_id')
    //         ])/*POCOR-6062 ends*/
    //         ->where([
    //             $studentWithdraw->aliasField('institution_id') => $institutionId,
    //             $studentWithdraw->aliasField('academic_period_id') => $academicPeriodId,
    //             $studentWithdraw->aliasField('education_grade_id') => $educationGradeId,
    //             $DayCondititon, //POCOR-7183
    //             $InstitutionStudents->aliasField('student_status_id !=') => 1 //POCOR-6062
    //         ])->toArray();
    //     //POCOR-6547[END]
    //     if ($studentWithdrawData) {
    //         foreach ($studentWithdrawData as $withdrawStudent) {
    //             $withdrawStudentIds[] = $withdrawStudent['student_id'];
    //         }
    //         if (!empty($withdrawStudentIds)) {
    //             $query->where([$this->aliasField('student_id NOT IN') => $withdrawStudentIds]);
    //         }
    //     }
    //     return $query;
    // }

    /**
     * POCOR-8224 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $alias, array $options = []): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($alias, $options);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $alias);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($alias);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias, $options);
    }

    private function _filterWithdrawnStudents(Query $query, array $options)
    {
        $day = $options['day_id'];
        $institutionId = $options['institution_id'];

        // Log::debug("[Withdraw] >>> Начало фильтрации отчислений");

        // 1. Получаем список ID всех студентов, которые сейчас в запросе (в классе)
        $classStudentIds = (clone $query)
            ->select(['student_id' => $this->aliasField('student_id')])
            ->group([$this->aliasField('student_id')])
            ->extract('student_id')
            ->toArray();

        if (empty($classStudentIds)) {
            // Log::debug("[Withdraw] Класс пуст, фильтрация не требуется.");
            return $query;
        }
        // Log::debug("[Withdraw] Студентов в классе перед проверкой: " . count($classStudentIds) . " (IDs: " . implode(',', $classStudentIds) . ")");

        // 2. Ищем, кто из ЭТОГО списка отчислен
        $studentWithdraw = TableRegistry::getTableLocator()->get('Institution.StudentWithdraw');
        $withdrawQuery = $studentWithdraw->find()
            ->select(['student_id'])
            ->where([
                'StudentWithdraw.institution_id' => $institutionId,
                'StudentWithdraw.student_id IN' => $classStudentIds
            ]);

        // Условие по дате
        if ($day != -1) {
            $withdrawQuery->where(['StudentWithdraw.effective_date <=' => $day]);
        } else {
            $withdrawQuery->where([
                'StudentWithdraw.effective_date >=' => $options['week_start_day'],
                'StudentWithdraw.effective_date <=' => $options['week_end_day']
            ]);
        }

        $withdrawnIds = $withdrawQuery->extract('student_id')->toArray();

        // 3. Если нашли отчисленных, исключаем их из основного запроса
        if (!empty($withdrawnIds)) {
            // Log::debug("[Withdraw] Найдено отчисленных для исключения: " . count($withdrawnIds) . " (IDs: " . implode(',', $withdrawnIds) . ")");
            $query->where([$this->aliasField('student_id NOT IN') => $withdrawnIds]);
        } else {
            // Log::debug("[Withdraw] Отчисленных студентов в данном классе не обнаружено.");
        }

        // Log::debug("[Withdraw] <<< Фильтрация завершена");
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
     * @param $day
     * @return Query
     */

    private function getAttendanceDailyQueryWithDayConditionNew(Query $query, $day)
    {
        //        $this->log("getAttendanceDailyQueryWithDayCondition $day", 'debug');
        $InstitutionStudents = self::getDynamicTableInstance('institution_students');
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
    private function getAttendanceDailyQueryWithDetails(Query $query, $attendancePeriodId, $day, $subjectId, $attendanceBy, $archive = false)
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
            $Details->aliasField('date = "')
                . $day . '"'
        ];

        // POCOR-9572: Use attendance_by parameter to determine mode
        if ($attendanceBy === 'subject' && $subjectId) {
            // Subject-based: period = 0 (or NULL), match specific subject
            $options[] = '(' . $Details->aliasField('period') . ' = 0 OR ' . $Details->aliasField('period') . ' IS NULL)';
            $options[] = $Details->aliasField('subject_id = ') . $subjectId;
        } else {
            // Period-based: match specific period, subject_id = 0 (or NULL)
            $period = !empty($attendancePeriodId) ? $attendancePeriodId : 0;
            $options[] = $Details->aliasField('period = ') . $period;
            $options[] = '(' . $Details->aliasField('subject_id') . ' = 0 OR ' . $Details->aliasField('subject_id') . ' IS NULL)';
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
    private function getAttendanceDailyQueryWithMarkedRecords(Query $query, $day, $attendancePeriodId, $subjectId, $attendanceBy, $archive = false)
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
            $Records->aliasField('date = "') . $day . '"'
        ];

        // POCOR-9572: Add period/subject filters to match the attendance mode
        if ($attendanceBy === 'subject' && $subjectId) {
            // Subject-based: period = 0, match specific subject
            $options[] = '(' . $Records->aliasField('period') . ' = 0 OR ' . $Records->aliasField('period') . ' IS NULL)';
            $options[] = $Records->aliasField('subject_id = ') . $subjectId;
        } else {
            // Period-based: match specific period, subject_id = 0
            $period = !empty($attendancePeriodId) ? $attendancePeriodId : 0;
            $options[] = $Records->aliasField('period = ') . $period;
            $options[] = '(' . $Records->aliasField('subject_id') . ' = 0 OR ' . $Records->aliasField('subject_id') . ' IS NULL)';
        }

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

        // POCOR-9572: CRITICAL FIX - Must use same table loading pattern as JOIN functions
        // to ensure alias matches the JOINed table (was causing "Unknown column" SQL error)
        $tableLocator = new TableLocator();
        if (!$archive) {
            $Details = $tableLocator->get('institution_student_absence_details');
            $Records = $tableLocator->get('student_attendance_marked_records');
        }
        if ($archive) {
            $table_name = 'institution_student_absence_details';
            $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
            $table_name = $archiveTableAndConnection[0];
            $Details = $tableLocator->get($table_name);

            $table_name = 'student_attendance_marked_records';
            $archiveTableAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
            $table_name = $archiveTableAndConnection[0];
            $Records = $tableLocator->get($table_name);
        }
        $first_name = $Users->aliasField('first_name');
        $last_name = $Users->aliasField('last_name');
        $gender_id = $Users->aliasField('gender_id'); // POCOR-9572
        $absence_type_id = $Types->aliasField('id');
        $absence_type_code = $Types->aliasField('code');
        $absence_type_name = $Types->aliasField('name');
        $student_absence_reason_id = $Details->aliasField('student_absence_reason_id');

        // POCOR-9572: Build gender CASE expression using CakePHP's expression builder
        $genderExpression = $query->newExpr()
            ->case()
            ->when(['Users.gender_id' => 1])
            ->then('Male')
            ->when(['Users.gender_id' => 2])
            ->then('Female')
            ->else('Not Set');

        // POCOR-9572: Base fields for both grid and Excel
        $selectFields = [
            $this->aliasField('id'),
            $this->aliasField('student_id'), // POCOR-9572: Add student_id field
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
            'gender' => $genderExpression, // POCOR-9572: Gender transformation
            'absence_type_id' => "COALESCE($absence_type_id, 0)",
            'absence_type_code' => "COALESCE($absence_type_code, 'PRESENT')",
            'absence_type_name' => "COALESCE($absence_type_name, 'Present')",
            'no_scheduled_class' => $Records->aliasField('no_scheduled_class'),
            'user_id' => $this->aliasField('student_id')
        ];

        $query->select($selectFields);
        return $query;
    }
    private function getAttendanceDailySelectFieldsNew(Query $query, $day, $archive=false)
    {
        $Statuses = self::getDynamicTableInstance('student_statuses');
        $Users = self::getDynamicTableInstance('security_users');
        $Types = self::getDynamicTableInstance('absence_types');
        $Classes = self::getDynamicTableInstance('institution_classes');
        $Reasons = self::getDynamicTableInstance('student_absence_reasons');
        $Details =  self::getDynamicTableInstance('institution_student_absence_details');
            $Records =  self::getDynamicTableInstance('student_attendance_marked_records');

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
        $attendanceBy = 'period', // POCOR-9572
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
                    $periodId,
                    $subjectId,
                    $date,
                    $archive
                );
                $wideQuery = clone $query;
                $wideQuery = $this->getAttendanceDailyQueryWithDetails($wideQuery, $periodId, $date, $subjectId, $attendanceBy, $archive);
                $wideQuery = $this->getAttendanceDailyQueryWithAbsenceTypes($wideQuery, $archive);
                $wideQuery = $this->getAttendanceDailyQueryWithMarkedRecords($wideQuery, $date, $periodId, $subjectId, $attendanceBy, $archive);
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
        $sampleKeys = array_slice(array_keys($WeekDaysAbsenceArray), 0, 2);
        $sample = array_intersect_key($WeekDaysAbsenceArray, array_flip($sampleKeys));
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
                        foreach ($WeekDaysAbsenceArray[$studentId] as $key => $value) {
                            foreach ($value as $period_key => $period_value) {
                                if ($period_value == 'NOTMARKED') {
                                    $period_value = '';
                                } else {

                                }
                                $row->{'week_attendance_status_' . $key . '-' . $period_key} = $period_value;
                            }
                        }
                    }

                    return $row;
                });
            });
        return $query;
    }
}
