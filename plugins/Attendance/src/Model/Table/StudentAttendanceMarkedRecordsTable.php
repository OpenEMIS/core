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

    public function afterSaveCommit(Event $event, Entity $entity)
    {
        
        $ClassAttendanceRecords = TableRegistry::get('Institution.ClassAttendanceRecords');
        $ClassAttendanceRecords->dispatchEvent('Model.StudentAttendances.afterSaveCommit', [$entity], $ClassAttendanceRecords);
    }

    /*POCOR-6021 starts*/
    public function findNoScheduledClass(Query $query, array $options) 
    {
        $classStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $institutionClassId = $options['institution_class_id'];
        $educationGradeId = $options['education_grade_id'];        
        $day = $options['day_id'];
        $row = [];
        
        return $query
                ->find('all')
                ->innerJoin([$classStudents->alias() => $classStudents->table()], [
                        $classStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                        $classStudents->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
                        $classStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                        $classStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    ]
                )->where([
                        $classStudents->aliasField('institution_class_id') => $institutionClassId,
                        $classStudents->aliasField('education_grade_id') => $educationGradeId,
                        $classStudents->aliasField('institution_id') => $institutionId,
                        $classStudents->aliasField('academic_period_id') => $academicPeriodId
                ])
                ->group([$classStudents->aliasField('student_id')])
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
                                $query = $this->query();
                                $query->newEntity([
                                    'institution_class_id' => $institutionClassId,
                                    'education_grade_id' => $educationGradeId,
                                    'institution_id' => $institutionId,
                                    'academic_period_id' => $academicPeriodId,
                                    'date' => $day,
                                    'period' => 0,
                                    'subject_id' => 0,
                                    'no_scheduled_class' => 1
                                ]);
                                $query->save($newEntity);
                            }
                            $row->is_Scheduled = 1;
                            return $row;
                            });
                        });
    }
    /*POCOR-6021 ends*/
}
