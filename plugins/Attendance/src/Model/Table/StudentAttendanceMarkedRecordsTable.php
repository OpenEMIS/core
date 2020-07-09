<?php
namespace Attendance\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

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
        $day = $options['day_id'];
        $period = $options['attendance_period_id'];
        $subjectId = $options['subject_id'];
        $attendanceTypeId = $options['attendance_type_id'];
        $conditions = [
                $this->aliasField('subject_id') => $subjectId,
                $this->aliasField('attendance_type_id') => $attendanceTypeId
            ]; 

        return $query
            ->where([
                $this->aliasField('institution_class_id') => $institutionClassId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('date') => $day,
                $this->aliasField('period') => $period,
                $conditions
            ]);
    }

    /*public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $institutionId = $entity['institution_id'];
        $academicPeriodId = $entity['academic_period_id'];
        $institutionClassId = $entity['institution_class_id'];
        $day = $entity['day_id'];
        $period = $entity['attendance_period_id'];
        $subjectId = $entity['subject_id'];
        $attendanceTypeId = $entity['attendance_type_id'];
        
            $conditions = [
                $this->aliasField('subject_id') => $subjectId,
                $this->aliasField('attendance_type_id') => $attendanceTypeId
            ];
        
        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
          $studentAttendanceMarkedRecordsData = $this
                                                ->find()
                                                ->where([
                                                    $this->aliasField('institution_class_id') => $institutionClassId,
                                                    $this->aliasField('institution_id') => $institutionId,
                                                    $this->aliasField('academic_period_id') => $academicPeriodId,
                                                    $this->aliasField('date') => $day,
                                                    $this->aliasField('period') => $period,
                                                    $conditions
                                                ])
                                                ->toArray();
            if (count($studentAttendanceMarkedRecordsData) > 0) {
                $this->updateAll([
                    $entity],['subject_id' => $entity['subject_id']]);
            } else {
                $this->save($entity);
            }
    }*/

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $institutionId = $entity['institution_id'];
        $academicPeriodId = $entity['academic_period_id'];
        $institutionClassId = $entity['institution_class_id'];
        $day = $entity['date'];
        $period = $entity['period'];
        $subjectId = $entity['subject_id'];
        $attendanceTypeId = $entity['attendance_type_id'];
        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
        $studentData = $this
            ->find()
            ->select([
                'count' => $this->find()->func()->count('*')])
            ->where([
                $this->aliasField('institution_class_id') => $institutionClassId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('date') => $day,
                $this->aliasField('period') => $period,
                $this->aliasField('subject_id') => $subjectId,
                $this->aliasField('attendance_type_id') => $attendanceTypeId
            ])
            ->toArray();

            if (!empty($studentData)) {
                $this->updateAll(['subject_id' => $subjectId],['subject_id' => $entity['subject_id']]);
            } else {
                $studentEntity = $this->newEntity([
                'institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId,
                'institution_class_id' => $institutionClassId,
                'date' => $day,
                'period' => $period,
                'subject_id' => $subjectId,
                'attendance_type_id' => $attendanceTypeId
            ]);
                echo "<pre>";print_r($studentEntity);die;
                //$studentEntity = $this->newEntity($studentEntity);
                $this->save($studentEntity);
               // return true;
            }
    }


    public function afterSaveCommit(Event $event, Entity $entity)
    {
        
        $ClassAttendanceRecords = TableRegistry::get('Institution.ClassAttendanceRecords');
        $ClassAttendanceRecords->dispatchEvent('Model.StudentAttendances.afterSaveCommit', [$entity], $ClassAttendanceRecords);
    }

    public function findRecord(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $institutionClassId = $options['institution_class_id'];
        $day = $options['date'];
        $period = $options['period'];
        $subjectId = $options['subject_id'];
        $attendanceTypeId = $options['attendance_type_id'];

        return $query
            ->select([
                'count' => $query->func()->count('*')])
            ->where([
                $this->aliasField('institution_class_id') => $institutionClassId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('date') => $day,
                $this->aliasField('period') => $period,
                $this->aliasField('subject_id') => $subjectId,
                $this->aliasField('attendance_type_id') => $attendanceTypeId
            ]);
            //echo "<pre>";print_r($query->toArray());die;
    } 
}
