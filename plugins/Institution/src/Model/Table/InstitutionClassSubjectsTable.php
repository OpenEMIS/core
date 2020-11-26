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

    public function findAllSubjectsByClassPerAcademicPeriod(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];       
        $institutionClassId = $options['institution_class_id'];
        $academicPeriodId = $options['academic_period_id'];
        $day_id = (new Time($options['day_id']))->format('w');
        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $ScheduleTimetables = TableRegistry::get('Schedule.ScheduleTimetables');
        $ScheduleCurriculumLessons = TableRegistry::get('Schedule.ScheduleCurriculumLessons');
        $ScheduleNonCurriculumLessons = TableRegistry::get('Schedule.ScheduleNonCurriculumLessons');
        $ScheduleLessonDetails = TableRegistry::get('Schedule.ScheduleLessonDetails');
        $InstitutionSubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');

        $scheduleTimetablesData = $ScheduleTimetables->find()
                                    ->where([
                                        $ScheduleTimetables->aliasField('institution_class_id') => $institutionClassId,
                                        $ScheduleTimetables->aliasField('academic_period_id') => $academicPeriodId
                                    ])
                                    ->toArray();
                                   
        if (count($scheduleTimetablesData) > 0) {
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
                } else {
                    $query
                        ->select([
                             'id'=>$InstitutionSubjects->aliasField('id'),
                             'name'=>$InstitutionSubjects->aliasField('name'),
                        ])
                        ->innerJoin(
                        [$InstitutionSubjects->alias() => $InstitutionSubjects->table()],
                            [
                                $InstitutionSubjects->aliasField('id = ') . $this->aliasField('institution_subject_id')
                            ]
                        )
                        ->where([
                            $this->aliasField('institution_class_id') => $institutionClassId
                        ])
                        ->order([
                            $InstitutionSubjects->aliasField('name')=>'DESC'
                        ]);
                    }


                    $staffId = $options['user']['id'];
                        $isStaff = $options['user']['is_staff'];
                        if ($options['user']['super_admin'] == 0) { 
                            $allSubjectsPermission = $this->getRolePermissionAccessForAllSubjects($staffId, $institutionId);
                            if (!$allSubjectsPermission) {
                                $query
                                ->innerJoin(
                                [$InstitutionSubjectStaff->alias() => $InstitutionSubjectStaff->table()],
                                        [
                                    $InstitutionSubjectStaff->aliasField('staff_id = ') . $staffId,
                                    $InstitutionSubjectStaff->aliasField('institution_subject_id = ') . $InstitutionSubjects->aliasField('id')
                                ]
                                );
                            }
                        }
                            
        return $query;
    }

    public function getRolePermissionAccessForAllSubjects($userId, $institutionId)
    {
        $roles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId); 
        //$userAccessRoles = implode(', ', $roles);    
        
        $QueryResult = TableRegistry::get('SecurityRoleFunctions')->find()              
                ->leftJoin(['SecurityFunctions' => 'security_functions'], [
                    [
                        'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                    ]
                ])
                ->where([
                    'SecurityFunctions.controller' => 'Institutions',
                    'SecurityRoleFunctions.security_role_id IN'=> $roles,
                    'AND' => [ 'OR' => [ 
                                        "SecurityFunctions.`_view` LIKE '%AllSubjects.index%'",
                                        "SecurityFunctions.`_view` LIKE '%AllSubjects.view%'"
                                    ]
                              ],
                    'SecurityRoleFunctions._view' => 1,
                    'SecurityRoleFunctions._edit' => 1
                ])
                ->toArray();
                
        if(!empty($QueryResult)){
            return true;
        }
          
        return false;
    }

    
}
