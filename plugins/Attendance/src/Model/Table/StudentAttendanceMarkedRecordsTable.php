<?php

namespace Attendance\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ResultSetInterface;
use Cake\Datasource\ConnectionManager; //POCOR-7023
use Cake\Log\Log; //POCOR-9652

class StudentAttendanceMarkedRecordsTable extends AppTable
{
    const NOT_VALID = -1;
    const NOT_MARKED = 0;
    const MARKED = 1;
    const PARTIAL_MARKED = 2;
    const DAY_COLUMN_PREFIX = 'day_';
    public function initialize(array $config): void
    {
        $this->setTable('student_attendance_marked_records');
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'add', 'edit']
        ]);
    }

    //POCOR-7023 starts
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $path_uri = '/restful/v2/Attendance-StudentAttendanceMarkedRecords.json';
        $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : ''; //POCOR-9617
        if (is_int(strpos($_SERVER['REQUEST_URI'], $path_uri)) && in_array($requestMethod, ['POST', 'PUT', 'PATCH'])) { //POCOR-9617: skip on GET (internal saves from findNoScheduledClass)
            $institution_id = $entity['institution_id'];
            $academic_period_id = $entity['academic_period_id'];
            $institution_class_id = $entity['institution_class_id'];
            $education_grade_id = $entity['education_grade_id'];
            $date = date('Y-m-d', strtotime($entity['date']));
            $connection = ConnectionManager::get('default');
            // $statement = $connection->prepare("SELECT
            //                 education_systems.academic_period_id,
            //                 correct_grade.id AS correct_grade_id,
            //                 student_attendance_marked_records.*
            //             FROM
            //                 `student_attendance_marked_records`
            //             INNER JOIN education_grades wrong_grade ON
            //                 wrong_grade.id = student_attendance_marked_records.education_grade_id
            //             INNER JOIN education_grades correct_grade ON
            //                 correct_grade.code = wrong_grade.code
            //             INNER JOIN education_programmes ON correct_grade.education_programme_id = education_programmes.id
            //             INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
            //             INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
            //             INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id AND education_systems.academic_period_id = student_attendance_marked_records.academic_period_id
            //             WHERE
            //                 (correct_grade.id != student_attendance_marked_records.education_grade_id) AND student_attendance_marked_records.academic_period_id = ".$academic_period_id." Group by correct_grade_id LIMIT 1");

            //  Start POCOR-7375

            $statement = $connection->prepare("SELECT
                        education_systems.academic_period_id,
                        correct_grade.id AS correct_grade_id,
                        student_attendance_marked_records.*
                    FROM
                        `student_attendance_marked_records`
                    INNER JOIN education_grades wrong_grade ON
                        wrong_grade.id = student_attendance_marked_records.education_grade_id
                    INNER JOIN education_grades correct_grade ON
                        correct_grade.code = wrong_grade.code
                    INNER JOIN education_programmes ON correct_grade.education_programme_id = education_programmes.id
                    INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
                    INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
                    INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id AND education_systems.academic_period_id = student_attendance_marked_records.academic_period_id
                    WHERE
                        (correct_grade.id != student_attendance_marked_records.education_grade_id) AND student_attendance_marked_records.academic_period_id = " . $academic_period_id . " AND student_attendance_marked_records.institution_id = " . $institution_id . " AND student_attendance_marked_records.institution_class_id = " . $institution_class_id . " AND student_attendance_marked_records.education_grade_id = " . $education_grade_id . " Group by correct_grade_id");

            //  End POCOR-7375

            $statement->execute();
            $row = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $StudentAttendanceMarkedRecords = TableRegistry::getTableLocator()->get('student_attendance_marked_records');
            $studentMarkTypeStatusGrades = TableRegistry::getTableLocator()->get('student_mark_type_status_grades');
            $studentMarkTypeStatuses = TableRegistry::getTableLocator()->get('student_mark_type_statuses');
            $studentAttendanceMarkTypes = TableRegistry::getTableLocator()->get('student_attendance_mark_types');
            $studentAttendanceTypes = TableRegistry::getTableLocator()->get('student_attendance_types');
            if (!empty($row)) {
                $data = $StudentAttendanceMarkedRecords
                    ->find()
                    ->select([
                        $StudentAttendanceMarkedRecords->aliasField('institution_id'),
                        $StudentAttendanceMarkedRecords->aliasField('academic_period_id'),
                        $StudentAttendanceMarkedRecords->aliasField('institution_class_id'),
                        $StudentAttendanceMarkedRecords->aliasField('education_grade_id'),
                        $StudentAttendanceMarkedRecords->aliasField('date'),
                        $StudentAttendanceMarkedRecords->aliasField('period'),
                        $StudentAttendanceMarkedRecords->aliasField('subject_id'),
                        $studentMarkTypeStatusGrades->aliasField('education_grade_id'),
                        $studentMarkTypeStatuses->aliasField('student_attendance_mark_type_id'),
                        $studentAttendanceMarkTypes->aliasField('name'),
                        $studentAttendanceTypes->aliasField('id'),
                        $studentAttendanceTypes->aliasField('code'),
                        $studentAttendanceTypes->aliasField('name')
                    ])
                    ->leftJoin([$studentMarkTypeStatusGrades->alias() => $studentMarkTypeStatusGrades->table()], [
                        $studentMarkTypeStatusGrades->aliasField('education_grade_id = ') . $row[0]['correct_grade_id']
                    ])
                    ->leftJoin([$studentMarkTypeStatuses->alias() => $studentMarkTypeStatuses->table()], [
                        $studentMarkTypeStatuses->aliasField('id = ') . $studentMarkTypeStatusGrades->aliasField('student_mark_type_status_id'),
                        $studentMarkTypeStatuses->aliasField('academic_period_id = ') . $StudentAttendanceMarkedRecords->aliasField('academic_period_id')
                    ])
                    ->leftJoin([$studentAttendanceMarkTypes->alias() => $studentAttendanceMarkTypes->table()], [
                        $studentAttendanceMarkTypes->aliasField('id = ') . $studentMarkTypeStatuses->aliasField('student_attendance_mark_type_id')
                    ])
                    ->leftJoin([$studentAttendanceTypes->alias() => $studentAttendanceTypes->table()], [
                        $studentAttendanceTypes->aliasField('id = ') . $studentAttendanceMarkTypes->aliasField('student_attendance_type_id')
                    ])
                    ->where([
                        $StudentAttendanceMarkedRecords->aliasField('institution_id') => $entity['institution_id'],
                        $StudentAttendanceMarkedRecords->aliasField('academic_period_id') => $entity['academic_period_id'],
                        $StudentAttendanceMarkedRecords->aliasField('institution_class_id') => $entity['institution_class_id'],
                        $StudentAttendanceMarkedRecords->aliasField('education_grade_id') => $entity['education_grade_id']
                    ])
                    ->group([$StudentAttendanceMarkedRecords->aliasField('education_grade_id')])
                    ->toArray();
                if (empty($data)) {
                    $response = array('error' => 'No record found for this request.');
                    $entity->errors($response);
                    return false;
                } else {
                    if (!empty($data[0]->student_attendance_types) && $data[0]->student_attendance_types['code'] == 'DAY') {
                        if (!empty($entity['subject_id'])) {
                            $response = array('error' => 'The Education Grade for which you are trying to send the API attendance request is configured to mark attendance per Period. Please ensure that the subject_id parameter is equal to 0.');
                            $entity->errors($response);
                            return false;
                        }
                    } else if (!empty($data[0]->student_attendance_types) && $data[0]->student_attendance_types['code'] == 'SUBJECT') {
                        if (!empty($entity['period']) && $entity['period'] > 1) {
                            $response = array('error' => 'The Education Grade for which you are trying to send the API attendance request is configured to mark attendance per Subject. Please ensure that the period_id parameter is equal to 1.');
                            $entity->errors($response);
                            return false;
                        }
                    }
                }
            } else {
                $data = $StudentAttendanceMarkedRecords
                    ->find()
                    ->select([
                        $StudentAttendanceMarkedRecords->aliasField('institution_id'),
                        $StudentAttendanceMarkedRecords->aliasField('academic_period_id'),
                        $StudentAttendanceMarkedRecords->aliasField('institution_class_id'),
                        $StudentAttendanceMarkedRecords->aliasField('education_grade_id'),
                        $StudentAttendanceMarkedRecords->aliasField('date'),
                        $StudentAttendanceMarkedRecords->aliasField('period'),
                        $StudentAttendanceMarkedRecords->aliasField('subject_id'),
                        $studentMarkTypeStatusGrades->aliasField('education_grade_id'),
                        $studentMarkTypeStatuses->aliasField('student_attendance_mark_type_id'),
                        $studentAttendanceMarkTypes->aliasField('name'),
                        $studentAttendanceTypes->aliasField('id'),
                        $studentAttendanceTypes->aliasField('code'),
                        $studentAttendanceTypes->aliasField('name')
                    ])
                    ->leftJoin([$studentMarkTypeStatusGrades->alias() => $studentMarkTypeStatusGrades->table()], [
                        $studentMarkTypeStatusGrades->aliasField('education_grade_id = ') . $education_grade_id
                    ])
                    ->leftJoin([$studentMarkTypeStatuses->alias() => $studentMarkTypeStatuses->table()], [
                        $studentMarkTypeStatuses->aliasField('id = ') . $studentMarkTypeStatusGrades->aliasField('student_mark_type_status_id'),
                        $studentMarkTypeStatuses->aliasField('academic_period_id = ') . $StudentAttendanceMarkedRecords->aliasField('academic_period_id')
                    ])
                    ->leftJoin([$studentAttendanceMarkTypes->alias() => $studentAttendanceMarkTypes->table()], [
                        $studentAttendanceMarkTypes->aliasField('id = ') . $studentMarkTypeStatuses->aliasField('student_attendance_mark_type_id')
                    ])
                    ->leftJoin([$studentAttendanceTypes->alias() => $studentAttendanceTypes->table()], [
                        $studentAttendanceTypes->aliasField('id = ') . $studentAttendanceMarkTypes->aliasField('student_attendance_type_id')
                    ])
                    ->where([
                        $StudentAttendanceMarkedRecords->aliasField('institution_id') => $entity['institution_id'],
                        $StudentAttendanceMarkedRecords->aliasField('academic_period_id') => $entity['academic_period_id'],
                        $StudentAttendanceMarkedRecords->aliasField('institution_class_id') => $entity['institution_class_id'],
                        $StudentAttendanceMarkedRecords->aliasField('education_grade_id') => $entity['education_grade_id']
                    ])
                    ->group([$StudentAttendanceMarkedRecords->aliasField('education_grade_id')])
                    ->toArray();
                if (empty($data)) {
                    $response = array('error' => 'No record found for this request.');
                    $entity->errors($response);
                    return false;
                } else {
                    if (!empty($data[0]->student_attendance_types) && $data[0]->student_attendance_types['code'] == 'DAY') {
                        if (!empty($entity['subject_id'])) {
                            $response = array('error' => 'The Education Grade for which you are trying to send the API attendance request is configured to mark attendance per Period. Please ensure that the subject_id parameter is equal to 0.');
                            $entity->errors($response);
                            return false;
                        }
                    } else if (!empty($data[0]->student_attendance_types) && $data[0]->student_attendance_types['code'] == 'SUBJECT') {
                        if (!empty($entity['period']) && $entity['period'] > 1) {
                            $response = array('error' => 'The Education Grade for which you are trying to send the API attendance request is configured to mark attendance per Subject. Please ensure that the period_id parameter is equal to 1.');
                            $entity->errors($response);
                            return false;
                        }
                    }
                }
            }
        }
    } //POCOR-7023 ends

    public function findPeriodIsMarked(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $institutionClassId = $options['institution_class_id'];
        $educationGradeId = $options['education_grade_id'];
        $day = $options['day_id'];

        $period = $options['attendance_period_id'];
        $subjectId = $options['subject_id'];
        $data = $this->markedRecordAfterSave($options); //POCOR-7143
        // POCOR-8874 start
        $condition = [];

        if ($subjectId != 0) {
            $condition = [
                $this->aliasField('institution_class_id') => $institutionClassId,
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('date') => $day,
                // $this->aliasField('period') => $period,
                $this->aliasField('subject_id = ') => $subjectId
            ];
        } else {
            $condition = [
                $this->aliasField('institution_class_id') => $institutionClassId,
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('date') => $day,
                $this->aliasField('period') => $period,
                // $this->aliasField('subject_id = ') => $subjectId
            ];
        }

        return $query
        ->where($condition);
        // POCOR-8874 end

        // return $query
        //     ->where([
        //     $this->aliasField('institution_class_id') => $institutionClassId,
        //     $this->aliasField('education_grade_id') => $educationGradeId,
        //     $this->aliasField('institution_id') => $institutionId,
        //     $this->aliasField('academic_period_id') => $academicPeriodId,
        //     $this->aliasField('date') => $day,
        //     $this->aliasField('period') => $period,
        //     $this->aliasField('subject_id = ') => $subjectId
        // ]);
    }

    //POCOR-7143[START]
    public function markedRecordAfterSave($options)
    {
        $ClassAttendanceRecords = TableRegistry::getTableLocator()->get('Institution.ClassAttendanceRecords');
        $institutionClassId = $options['institution_class_id'];
        $educationGradeId = $options['education_grade_id'];
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $date = $options['day_id'];
        $explodedData = explode("-", $date);
        $numberOfperiodByClass = $this->numberOfperiodByClass($options);

        $year = (int) $explodedData[0];
        $month = (int) $explodedData[1];
        $day = (int) $explodedData[2];

        $StudentAttendanceMarkedRecords = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkedRecords');
        $totalMarkedCount = $StudentAttendanceMarkedRecords
            ->find()
            ->where([
                $StudentAttendanceMarkedRecords->aliasField('institution_id') => $institutionId,
                $StudentAttendanceMarkedRecords->aliasField('academic_period_id') => $academicPeriodId,
                $StudentAttendanceMarkedRecords->aliasField('institution_class_id') => $institutionClassId,
                $StudentAttendanceMarkedRecords->aliasField('education_grade_id') => $educationGradeId,
                $StudentAttendanceMarkedRecords->aliasField('date') => $date
            ])
            ->count();

        $StudentAttendanceMarkTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkTypes');
        $attendancePerDay = $StudentAttendanceMarkTypes->getAttendancePerDayByClass($institutionClassId, $academicPeriodId);

        $ClassAttendanceRecordsData = $ClassAttendanceRecords
            ->find()
            ->where([
                $ClassAttendanceRecords->aliasField('institution_class_id') => $institutionClassId,
                $ClassAttendanceRecords->aliasField('academic_period_id') => $academicPeriodId,
                $ClassAttendanceRecords->aliasField('year') => $year,
                $ClassAttendanceRecords->aliasField('month') => $month
            ])
            ->first();
        if (empty($ClassAttendanceRecordsData)) {
            $markedType = self::NOT_MARKED;
        } else if ($totalMarkedCount > count($attendancePerDay)) {
            $markedType = self::MARKED;
        } else {
            $markedType = self::PARTIAL_MARKED;
        }
        if (count($numberOfperiodByClass) == $totalMarkedCount) {
            $markedType = self::MARKED;
        }

        $entityData = [
            'institution_class_id' => $institutionClassId,
            'academic_period_id' => $academicPeriodId,
            'year' => $year,
            'month' => $month,
            self::DAY_COLUMN_PREFIX . $day => $markedType
        ];

        $entity = $ClassAttendanceRecords->newEntity($entityData);
        $ClassAttendanceRecords->save($entity);
    }
    //POCOR-7143[END]

    public function numberOfperiodByClass($options)
    {
        $StudentAttendanceMarkTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkTypes');
        $institionClassId = $options['institution_class_id'];
        $academicPeriodId = $options['academic_period_id'];
        $dayId = $options['day_id'];
        $educationGradeId = $options['education_grade_id'];
        // return "Hi";
        // $attendanceOptions = $this->getAttendancePerDayOptionsByClass($institionClassId, $academicPeriodId, $dayId, $educationGradeId);
        $attendanceOptions = $StudentAttendanceMarkTypes->getAttendancePerDayOptionsByClass($institionClassId, $academicPeriodId, $dayId, $educationGradeId);
        return $attendanceOptions;
        // ->formatResults(function (ResultSetInterface $results) use ($attendanceOptions) {
        //     return $attendanceOptions;
        // });
    }

    public function afterSaveCommit(EventInterface $event, Entity $entity)
    {

        $ClassAttendanceRecords = TableRegistry::getTableLocator()->get('Institution.ClassAttendanceRecords');
        $ClassAttendanceRecords->dispatchEvent('Model.StudentAttendances.afterSaveCommit', [$entity], $ClassAttendanceRecords);
    }

    /*POCOR-6021 starts*/
    public function findNoScheduledClass(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $institutionClassId = $options['institution_class_id'];
        $educationGradeId = $options['education_grade_id'];
        $day = $options['day_id'];
        $period = $options['attendance_period_id']; //POCOR-8383
        $subjectId = isset($options['subject_id']) ? (int)$options['subject_id'] : 0; //POCOR-9617

        // Log::debug('[TEMP-LOG] findNoScheduledClass: START class=' . $institutionClassId . ' date=' . $day . ' period=' . $period . ' subject=' . $subjectId);

        //POCOR-9617: start
        //POCOR-9617: raw SQL existence check — bypasses ORM, zero entity hydration, safe on large datasets
        $connection = ConnectionManager::get('default');
        $tableName = $this->getTable();
        $periodSql = ($period === null) ? 'period IS NULL' : 'period = ' . (int)$period;

        //POCOR-9652: start - enforce XOR rule: period-based → period=X,subject_id=0; subject-based → period=0,subject_id=X
        $storedPeriod    = ($subjectId > 0) ? 0          : (int)$period;
        $storedSubjectId = ($subjectId > 0) ? $subjectId : 0;
        // Log::debug('[TEMP-LOG] findNoScheduledClass: storedPeriod=' . $storedPeriod . ' storedSubjectId=' . $storedSubjectId);

        // Toggle: if no_scheduled_class=1 already exists for this period/subject, delete it (undo)
        $alreadySetSql = "SELECT 1 FROM `{$tableName}`
            WHERE institution_class_id = {$institutionClassId}
              AND education_grade_id = {$educationGradeId}
              AND institution_id = {$institutionId}
              AND academic_period_id = {$academicPeriodId}
              AND `date` = '{$day}'
              AND period = {$storedPeriod}
              AND subject_id = {$storedSubjectId}
              AND no_scheduled_class = 1
            LIMIT 1";
        $alreadySet = !empty($connection->execute($alreadySetSql)->fetchAll('assoc'));
        // Log::debug('[TEMP-LOG] findNoScheduledClass: alreadySet=' . ($alreadySet ? 'YES — will UNDO' : 'NO — will SET'));

        if ($alreadySet) {
            $deleted = $this->deleteAll([
                'institution_id'       => $institutionId,
                'academic_period_id'   => $academicPeriodId,
                'institution_class_id' => $institutionClassId,
                'education_grade_id'   => $educationGradeId,
                'date'                 => $day,
                'period'               => $storedPeriod,
                'subject_id'           => $storedSubjectId,
                'no_scheduled_class'   => 1,
            ]);
            // Log::debug('[TEMP-LOG] findNoScheduledClass: UNDO complete — deleted=' . $deleted . ' rows');
            return $this->find()->where([
                $this->aliasField('institution_class_id') => $institutionClassId,
                $this->aliasField('institution_id')       => $institutionId,
                $this->aliasField('academic_period_id')   => $academicPeriodId,
                $this->aliasField('date')                 => $day,
                $this->aliasField('period')               => $storedPeriod,
                $this->aliasField('subject_id')           => $storedSubjectId,
                $this->aliasField('no_scheduled_class')   => 1,
            ])->limit(1); //POCOR-9652: row was just deleted, so this returns 0 rows → total=0 → JS clears state
        }
        //POCOR-9652: end

        $existsSql = "SELECT 1 FROM `{$tableName}`
            WHERE institution_class_id = {$institutionClassId}
              AND education_grade_id = {$educationGradeId}
              AND institution_id = {$institutionId}
              AND academic_period_id = {$academicPeriodId}
              AND `date` = '{$day}'
              AND period = {$storedPeriod}
              AND subject_id = {$storedSubjectId}
            LIMIT 1";
        $existsResult = $connection->execute($existsSql)->fetchAll('assoc');
        $existsCount = count($existsResult);

        // Log::debug('[TEMP-LOG] findNoScheduledClass: markerRowExists=' . $existsCount);

        if ($existsCount > 0) {
            //POCOR-9652: use storedPeriod/storedSubjectId so subject-based rows store period=0,subject_id=X
            $updateQuery = $this->query();
            $updateQuery->update()
                ->set(['period' => $storedPeriod, 'subject_id' => $storedSubjectId, 'no_scheduled_class' => 1])
                ->where([
                    $this->aliasField('institution_class_id') => $institutionClassId,
                    $this->aliasField('education_grade_id')   => $educationGradeId,
                    $this->aliasField('institution_id')       => $institutionId,
                    $this->aliasField('academic_period_id')   => $academicPeriodId,
                    $this->aliasField('date')                 => $day,
                    $this->aliasField('period')               => $storedPeriod,
                    $this->aliasField('subject_id')           => $storedSubjectId,
                ])
                ->execute();
            // Log::debug('[TEMP-LOG] findNoScheduledClass: SET via UPDATE');
        } else {
            //POCOR-9652: use storedPeriod/storedSubjectId so subject-based rows store period=0,subject_id=X
            $newRecord = $this->newEntity([
                'institution_class_id' => $institutionClassId,
                'education_grade_id'   => $educationGradeId,
                'institution_id'       => $institutionId,
                'academic_period_id'   => $academicPeriodId,
                'date'                 => $day,
                'period'               => $storedPeriod,
                'subject_id'           => $storedSubjectId,
                'no_scheduled_class'   => 1,
            ]);
            $this->save($newRecord);
            // Log::debug('[TEMP-LOG] findNoScheduledClass: SET via INSERT');
        }

        $InstitutionStudentAbsenceDetails = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentAbsenceDetails');
        $absenceConditions = [
            $InstitutionStudentAbsenceDetails->aliasField('institution_id') => $institutionId,
            $InstitutionStudentAbsenceDetails->aliasField('academic_period_id') => $academicPeriodId,
            $InstitutionStudentAbsenceDetails->aliasField('institution_class_id') => $institutionClassId,
            $InstitutionStudentAbsenceDetails->aliasField('date') => $day,
            $InstitutionStudentAbsenceDetails->aliasField('period') => $period,
        ];
        if ($subjectId > 0) {
            $absenceConditions[$InstitutionStudentAbsenceDetails->aliasField('subject_id')] = $subjectId;
        }
        $deleted = $InstitutionStudentAbsenceDetails->deleteAll($absenceConditions);
        // Log::debug('[TEMP-LOG] findNoScheduledClass: cleared absence details=' . $deleted . ' rows');
        //POCOR-9617: end

        //POCOR-7143[START]
        //POCOR-9617: raw SQL existence check for POCOR-7143 block — bypasses ORM memory overhead
        //POCOR-9652: use storedPeriod so subject-based rows (period=0) are found correctly
        $markedCheckSql = "SELECT 1 FROM `{$tableName}`
            WHERE institution_id = {$institutionId}
              AND academic_period_id = {$academicPeriodId}
              AND institution_class_id = {$institutionClassId}
              AND education_grade_id = {$educationGradeId}
              AND `date` = '{$day}'
              AND period = {$storedPeriod}
              AND subject_id = {$storedSubjectId}
            LIMIT 1";
        $markedCheckResult = $connection->execute($markedCheckSql)->fetchAll('assoc');
        if (!empty($markedCheckResult)) {
            $explodedData = explode("-", $day);
            $year = (int) $explodedData[0];
            $month = (int) $explodedData[1];
            $daydata = (int) $explodedData[2];
            $ClassAttendanceRecords = TableRegistry::getTableLocator()->get('Institution.ClassAttendanceRecords');
            $ClassAttendanceRecords->updateAll(
                [self::DAY_COLUMN_PREFIX . $daydata => self::PARTIAL_MARKED],
                [
                    $ClassAttendanceRecords->aliasField('academic_period_id') => $academicPeriodId,
                    $ClassAttendanceRecords->aliasField('institution_class_id') => $institutionClassId,
                    $ClassAttendanceRecords->aliasField('year') => $year,
                    $ClassAttendanceRecords->aliasField('month') => $month
                ]
            );
            // Log::debug('[TEMP-LOG] findNoScheduledClass: updated ClassAttendanceRecords day=' . $daydata);
        }
        //POCOR-7143[END]

        //POCOR-9617: return a fresh query that confirms the saved record exists,
        //so the restful API returns total > 0 and the JS sets isMarked = true
        //POCOR-9652: use storedPeriod/storedSubjectId so subject-based rows are found correctly
        $query = $this->find()->where([
            $this->aliasField('institution_class_id') => $institutionClassId,
            $this->aliasField('education_grade_id')   => $educationGradeId,
            $this->aliasField('institution_id')       => $institutionId,
            $this->aliasField('academic_period_id')   => $academicPeriodId,
            $this->aliasField('date')                 => $day,
            $this->aliasField('period')               => $storedPeriod,
            $this->aliasField('subject_id')           => $storedSubjectId,
            $this->aliasField('no_scheduled_class')   => 1,
        ])->limit(1);
        // Log::debug('[TEMP-LOG] findNoScheduledClass: END — returning confirmation query');
        //POCOR-9617: end

        return $query;
    }
    /*POCOR-6021 ends*/

}
