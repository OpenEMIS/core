<?php
namespace Attendance\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ResultSetInterface;
use Cake\Datasource\ConnectionManager;//POCOR-7023

class StudentAttendanceMarkedRecordsTable extends AppTable
{
    const NOT_VALID = -1;
    const NOT_MARKED = 0;
    const MARKED = 1;
    const PARTIAL_MARKED = 2;
    const DAY_COLUMN_PREFIX = 'day_';
    public function initialize(array $config)
    {
        $this->table('student_attendance_marked_records');
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'add', 'edit']
        ]);
    }

    //POCOR-7023 starts
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    { 
        $path_uri = '/restful/v2/Attendance-StudentAttendanceMarkedRecords.json';
        if(is_int(strpos($_SERVER['REQUEST_URI'], $path_uri))){
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
                        (correct_grade.id != student_attendance_marked_records.education_grade_id) AND student_attendance_marked_records.academic_period_id = ".$academic_period_id." AND student_attendance_marked_records.institution_id = ".$institution_id." AND student_attendance_marked_records.institution_class_id = ".$institution_class_id." AND student_attendance_marked_records.education_grade_id = ".$education_grade_id." Group by correct_grade_id");
                
            //  End POCOR-7375
            
            $statement->execute();
            $row = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $StudentAttendanceMarkedRecords = TableRegistry::get('student_attendance_marked_records');
            $studentMarkTypeStatusGrades = TableRegistry::get('student_mark_type_status_grades');
            $studentMarkTypeStatuses = TableRegistry::get('student_mark_type_statuses');
            $studentAttendanceMarkTypes = TableRegistry::get('student_attendance_mark_types');
            $studentAttendanceTypes = TableRegistry::get('student_attendance_types');
            if(!empty($row)){
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
                if(empty($data)){
                    $response = array('error'=> 'No record found for this request.');
                    $entity->errors($response);    
                    return false;
                }else{
                    if(!empty($data[0]->student_attendance_types) && $data[0]->student_attendance_types['code'] == 'DAY'){
                        if(!empty($entity['subject_id'])){
                            $response = array('error'=> 'The Education Grade for which you are trying to send the API attendance request is configured to mark attendance per Period. Please ensure that the subject_id parameter is equal to 0.');
                            $entity->errors($response);    
                            return false;
                        }
                    }else if(!empty($data[0]->student_attendance_types) && $data[0]->student_attendance_types['code'] == 'SUBJECT'){
                        if(!empty($entity['period']) && $entity['period'] > 1){
                            $response = array('error'=> 'The Education Grade for which you are trying to send the API attendance request is configured to mark attendance per Subject. Please ensure that the period_id parameter is equal to 1.');
                            $entity->errors($response);    
                            return false;
                        }
                    }
                }
            }else{
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
                if(empty($data)){
                    $response = array('error'=> 'No record found for this request.');
                    $entity->errors($response);    
                    return false;
                }else{
                    if(!empty($data[0]->student_attendance_types) && $data[0]->student_attendance_types['code'] == 'DAY'){
                        if(!empty($entity['subject_id'])){
                            $response = array('error'=> 'The Education Grade for which you are trying to send the API attendance request is configured to mark attendance per Period. Please ensure that the subject_id parameter is equal to 0.');
                            $entity->errors($response);    
                            return false;
                        }
                    }else if(!empty($data[0]->student_attendance_types) && $data[0]->student_attendance_types['code'] == 'SUBJECT'){
                        if(!empty($entity['period']) && $entity['period'] > 1){
                            $response = array('error'=> 'The Education Grade for which you are trying to send the API attendance request is configured to mark attendance per Subject. Please ensure that the period_id parameter is equal to 1.');
                            $entity->errors($response);    
                            return false;
                        }
                    }
                }
            }
        }
    }//POCOR-7023 ends

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

        return $query
            ->where([
                $this->aliasField('institution_class_id') => $institutionClassId,
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('date') => $day,
                $this->aliasField('period') => $period,
                $this->aliasField('subject_id = ') => $subjectId
            ]);
            
    }

    //POCOR-7143[START]
    public function markedRecordAfterSave($options)
    {
        $ClassAttendanceRecords = TableRegistry::get('Institution.ClassAttendanceRecords');
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

        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
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
        
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
        $attendancePerDay = $StudentAttendanceMarkTypes->getAttendancePerDayByClass($institutionClassId, $academicPeriodId);

        $ClassAttendanceRecordsData = $ClassAttendanceRecords
            ->find()
            ->where([$ClassAttendanceRecords->aliasField('institution_class_id') => $institutionClassId,
                    $ClassAttendanceRecords->aliasField('academic_period_id') => $academicPeriodId,
                    $ClassAttendanceRecords->aliasField('year') => $year,
                    $ClassAttendanceRecords->aliasField('month') => $month
                    ])
            ->first();
        if(empty($ClassAttendanceRecordsData)){
            $markedType = self::NOT_MARKED;
        }
        else if ($totalMarkedCount > count($attendancePerDay)) {
            $markedType = self::MARKED;
        } else {
            $markedType = self::PARTIAL_MARKED;
        }
        if(count($numberOfperiodByClass) == $totalMarkedCount){
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
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
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

    public function afterSaveCommit(Event $event, Entity $entity)
    {
        
        $ClassAttendanceRecords = TableRegistry::get('Institution.ClassAttendanceRecords');
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


        $row = [];
        
        return $query
                ->formatResults(function (ResultSetInterface $results) use ($institutionClassId, $educationGradeId, $institutionId, $academicPeriodId, $day) { 
                            return $results->map(function ($row) use ($institutionClassId, $educationGradeId, $institutionId, $academicPeriodId, $day) {
                                    $getRecord = $this->find('all')
                                            ->where([
                                                $this->aliasField('institution_class_id') => $institutionClassId,
                                                $this->aliasField('education_grade_id') => $educationGradeId,
                                                $this->aliasField('institution_id') => $institutionId,
                                                $this->aliasField('academic_period_id') => $academicPeriodId,
                                                $this->aliasField('date') => $day
                                        ])->toArray();
                                    if (!empty($getRecord)) {
                                        $this->deleteAll([
                                            $this->aliasField('institution_class_id') => $institutionClassId,
                                            $this->aliasField('education_grade_id') => $educationGradeId,
                                            $this->aliasField('institution_id') => $institutionId,
                                            $this->aliasField('academic_period_id') => $academicPeriodId,
                                            $this->aliasField('date') => $day,
                                            $this->aliasField('no_scheduled_class') => 0,
                                        ]);
                                            $query = $this->query();
                                            $query ->update()
                                                    ->set(['period' => 0, 'subject_id' => 0, 'no_scheduled_class' => 1])
                                                    ->where([
                                                        $this->aliasField('institution_class_id') => $institutionClassId,
                                                        $this->aliasField('education_grade_id') => $educationGradeId,
                                                        $this->aliasField('institution_id') => $institutionId,
                                                        $this->aliasField('academic_period_id') => $academicPeriodId,
                                                        $this->aliasField('date') => $day
                                                    ])
                                                    ->execute();
                                                
                                    } else {
                                        $newRecord = $this->newEntity([
                                                'institution_class_id' => $institutionClassId,
                                                'education_grade_id' => $educationGradeId,
                                                'institution_id' => $institutionId,
                                                'academic_period_id' => $academicPeriodId,
                                                'date' => $day,
                                                'period' => 0,
                                                'subject_id' => 0,
                                                'no_scheduled_class' => 1
                                            ]);
                                        $this->save($newRecord);
                                    }


                                    //POCOR-7143[START]
                                    $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
                                    $totalMarkedCount = $StudentAttendanceMarkedRecords
                                        ->find()
                                        ->where([
                                            $StudentAttendanceMarkedRecords->aliasField('institution_id') => $institutionId,
                                            $StudentAttendanceMarkedRecords->aliasField('academic_period_id') => $academicPeriodId,
                                            $StudentAttendanceMarkedRecords->aliasField('institution_class_id') => $institutionClassId,
                                            $StudentAttendanceMarkedRecords->aliasField('education_grade_id') => $educationGradeId,
                                            $StudentAttendanceMarkedRecords->aliasField('date') => $day 
                                        ])
                                        ->first();
                                        if(!empty($totalMarkedCount)){
                                            $explodedData = explode("-", $day);
                                            $year = (int) $explodedData[0];
                                            $month = (int) $explodedData[1];
                                            $daydata = (int) $explodedData[2];
                                            $ClassAttendanceRecords = TableRegistry::get('Institution.ClassAttendanceRecords');
                                            $ClassAttendanceRecords->updateAll(
                                                [self::DAY_COLUMN_PREFIX . $daydata => self::PARTIAL_MARKED],
                                                [
                                                    $ClassAttendanceRecords->aliasField('academic_period_id') => $academicPeriodId,
                                                    $ClassAttendanceRecords->aliasField('institution_class_id') => $institutionClassId,
                                                    $ClassAttendanceRecords->aliasField('year') => $year,
                                                    $ClassAttendanceRecords->aliasField('month') => $month
                                                ]
                                            );
                                        }
                                        //POCOR-7143[END]


                                    $row->is_Scheduled = 1;
                                    return $row;
                            });
                        });
                        
    }
    /*POCOR-6021 ends*/
}
