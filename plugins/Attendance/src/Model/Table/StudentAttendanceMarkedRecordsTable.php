<?php
namespace Attendance\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ResultSetInterface;

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
        if ($totalMarkedCount >= count($attendancePerDay)) {
            $markedType = self::MARKED;
        } else {
            $markedType = self::PARTIAL_MARKED;
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
