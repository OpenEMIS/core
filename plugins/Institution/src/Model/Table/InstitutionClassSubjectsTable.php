<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\I18n\Time;

class InstitutionClassSubjectsTable extends AppTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);
        
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ScheduleTimetable' => ['index']
        ]);
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $subjectEntity = $this->InstitutionSubjects->get($entity->institution_subject_id);
        $this->InstitutionSubjects->delete($subjectEntity);
    }
    
    public function findAllSubjects(Query $query, array $options)
    {       
        $institutionClassId = $options['institution_class_id'];
        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $query
            ->select([
                 $this->aliasField('id'),
                 'institution_subject_id'=>$InstitutionSubjects->aliasField('id'),
                 'institution_subject_name'=>$InstitutionSubjects->aliasField('name'),
            ])
            ->contain(['InstitutionSubjects'])
            ->where([
                $this->aliasField('institution_class_id') => $institutionClassId
            ])
            ->order([
                $InstitutionSubjects->aliasField('name')=>'DESC'
            ]);
        
        return $query;
    }

    public function findAllSubjectsByClass(Query $query, array $options)
    {       
        $institutionClassId = $options['institution_class_id'];
        $day_id = (new Time($options['day_id']))->format('w');
        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $ScheduleTimetables = TableRegistry::get('Schedule.ScheduleTimetables');
        $ScheduleCurriculumLessons = TableRegistry::get('Schedule.ScheduleCurriculumLessons');
        $ScheduleNonCurriculumLessons = TableRegistry::get('Schedule.ScheduleNonCurriculumLessons');
        $ScheduleLessonDetails = TableRegistry::get('Schedule.ScheduleLessonDetails');


                $query
                    ->select([
                        'id' => $InstitutionSubjects->aliasField('id'),
                        'name' => $InstitutionSubjects->aliasField('name')
                ])
                    
                    ->leftJoin(
                        [$ScheduleTimetables->alias() => $ScheduleTimetables->table()],
                        [
                            $ScheduleTimetables->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id')
                        ]
                    )
                    ->innerJoin(
                        [$ScheduleLessonDetails->alias() => $ScheduleLessonDetails->table()],
                        [
                            $ScheduleLessonDetails->aliasField('institution_schedule_timetable_id = ') . $ScheduleTimetables->aliasField('id')
                        ]
                    )
                    ->innerJoin(
                        [$ScheduleCurriculumLessons->alias() => $ScheduleCurriculumLessons->table()],
                        [
                            $ScheduleCurriculumLessons->aliasField('institution_schedule_lesson_detail_id = ') . $ScheduleLessonDetails->aliasField('id')
                        ]
                    )
                    ->innerJoin(
                        [$InstitutionSubjects->alias() => $InstitutionSubjects->table()],
                        [
                            $InstitutionSubjects->aliasField('id = ') . $ScheduleCurriculumLessons->aliasField('institution_subject_id')
                        ]
                    )
                    ->leftJoin(
                        [$ScheduleNonCurriculumLessons->alias() => $ScheduleNonCurriculumLessons->table()],
                        [
                            $ScheduleNonCurriculumLessons->aliasField('institution_schedule_lesson_detail_id = ') . $ScheduleLessonDetails->aliasField('id')
                        ]
                    )
                    ->where([$ScheduleTimetables->aliasField('institution_class_id') => $institutionClassId,
                        $ScheduleLessonDetails->aliasField('day_of_week') => $day_id
                    ])
                    ->group([
                        $InstitutionSubjects->aliasField('id')
                    ]);
        return $query;

        $query
            ->select([
                 'id'=>$InstitutionSubjects->aliasField('id'),
                 'name'=>$InstitutionSubjects->aliasField('name'),
            ])
            ->contain(['InstitutionSubjects'])
            ->where([
                $this->aliasField('institution_class_id') => $institutionClassId
            ])
            ->order([
                $InstitutionSubjects->aliasField('name')=>'DESC'
            ]);
        
        return $query;
    }

    public function findAttendanceTypeName(Query $query, array $options)
    {
       $institution_class_id = $options['institution_class_id'];
       $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
       $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
       $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
                $query
                ->select([
                    'attendanceTypeName' => $StudentAttendanceTypes->aliasField('name')
                ])
                ->leftJoin(
                        [$InstitutionClassGrades->alias() => $InstitutionClassGrades->table()],
                        [
                            $InstitutionClassGrades->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id')
                        ]
                    )
                ->leftJoin(
                        [$StudentAttendanceMarkTypes->alias() => $StudentAttendanceMarkTypes->table()],
                        [
                            $StudentAttendanceMarkTypes->aliasField('education_grade_id = ') . $InstitutionClassGrades->aliasField('education_grade_id')
                        ]
                    )
                ->leftJoin(
                        [$StudentAttendanceTypes->alias() => $StudentAttendanceTypes->table()],
                        [
                            $StudentAttendanceTypes->aliasField('id = ') . $StudentAttendanceMarkTypes->aliasField('student_attendance_type_id')
                        ]
                    )
                ->where([
                    $InstitutionClassGrades->aliasField('institution_class_id') => $institution_class_id
                ])
                ->group([$this->aliasField('institution_class_id')]);
               
                return $query;

    }
}
