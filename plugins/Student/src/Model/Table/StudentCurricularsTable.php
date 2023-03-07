<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\ResultSet;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;

//POCOR-6673
class StudentCurricularsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_curricular_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionCurriculars', ['className' => 'Institution.InstitutionCurriculars']);
        $this->belongsTo('CurricularPositions', ['className' => 'FieldOption.CurricularPositions']);
        $this->toggle('add', false);
        $this->toggle('search', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);
    }
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
        $session = $this->request->session();
        $sId = $session->read('Student.Students.id');
        $userData = $this->Session->read();
        if($sId != null){
            $sId_id = $sId;
        }else{
            $sId_id =  $userData['Auth']['User']['id'];
        }
        $InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        $curricular_types = TableRegistry::get('curricular_types');
        $academicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $institutionId = $this->Session->read('Institution.Institutions.id');
        if($this->controller->name == 'Profiles'){
            $query
            ->select([
                        $this->aliasField('id'),
                        'academic_period_id'=>$academicPeriods->aliasField('name'),
                        'type'=>$curricular_types->aliasField('name'),
                        'category'=>$InstitutionCurriculars->aliasField('category'),
                ])
             ->LeftJoin([$InstitutionCurriculars->alias() => $InstitutionCurriculars->table()],
                    [$InstitutionCurriculars->aliasField('id').' = ' . $this->aliasField('institution_curricular_id')
                ])
             ->LeftJoin([$academicPeriods->alias() => $academicPeriods->table()],
                    [$academicPeriods->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('academic_period_id')
                    ])
                ->LeftJoin([$curricular_types->alias() => $curricular_types->table()],
                    [$curricular_types->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('curricular_type_id')
                ])->where([$this->aliasField('student_id') => $sId_id]);
            
        }else {
        $query
            ->select([
                        $this->aliasField('id'),
                        'academic_period_id'=>$academicPeriods->aliasField('name'),
                        'type'=>$curricular_types->aliasField('name'),
                        'category'=>$InstitutionCurriculars->aliasField('category'),
                ])
             ->LeftJoin([$InstitutionCurriculars->alias() => $InstitutionCurriculars->table()],
                    [$InstitutionCurriculars->aliasField('id').' = ' . $this->aliasField('institution_curricular_id')
                ])
             ->LeftJoin([$academicPeriods->alias() => $academicPeriods->table()],
                    [$academicPeriods->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('academic_period_id')
                    ])
                ->LeftJoin([$curricular_types->alias() => $curricular_types->table()],
                    [$curricular_types->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('curricular_type_id')
                ])->where([$this->aliasField('student_id') => $sId_id,$InstitutionCurriculars->aliasField('institution_id')=>$institutionId]);
            }
        if (!$sortable) {
            $query
                ->order([
                    $this->aliasField('student_id') => 'ASC'
                ]);
        }
        $this->controllerAction = $extra['indexButtons']['view']['url']['action'];
        $query = $this->request->query;
        $this->field('student_id', ['visible' => false]);
        $this->field('institution_curricular_id', ['visible' => true]);
        $this->field('curricular_position_id', ['visible' => true]);
        $this->field('start_date', ['visible' => false]);
        $this->field('end_date', ['visible' => false]);
        $this->field('hours', ['visible' => false]);
        $this->field('points', ['visible' => false]);
        $this->field('location', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => true]);
        $this->field('type', ['visible' => true]);
        $this->field('category', ['visible' => true]);
        $this->setFieldOrder([
        'academic_period_id', 'institution_curricular_id','category','type', 'curricular_position_id']);
        if ($this->controller->name == 'Profiles') {
            unset($settings['indexButtons']['view']);
        }

	}

    public function onGetCategory(Event $event, Entity $entity)
    {
        return $entity->category ? __('Curricular') : __('Extracurricular');
    }

    public function beforeFind( Event $event, Query $query)
    {
        $userData = $this->Session->read();
        $session = $this->request->session();
        if ($userData['Auth']['User']['is_guardian'] == 1) { 
            if ($this->request->controller == 'GuardianNavs') {
                $studentId = $session->read('Student.Students.id');
            }else {
                $sId = $userData['Student']['ExaminationResults']['student_id']; 
                if ($sId) {
                    $studentId = $this->ControllerAction->paramsDecode($sId)['id'];
                }
                $studentId = $userData['Student']['Students']['id'];
            }
        } else {
            $studentId = $userData['Auth']['User']['id'];
        }

        if ($this->request->controller == 'GuardianNavs') {
            $where[$this->aliasField('student_id')] = $studentId;
        } else {
            if(!empty($userData['System']['User']['roles']) & !empty($userData['Student']['Students']['id'])) {
                $where[$this->aliasField('student_id')] = $userData['Student']['Students']['id'];
            } else {
                if (!empty($studentId)) {
                    $where[$this->aliasField('student_id')] = $studentId;
                }
            }
        }


        $InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        $curricular_types = TableRegistry::get('curricular_types');
        $academicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $query->find('all')
            ->select([
                        $this->aliasField('id'),
                        'academic_period_id'=>$academicPeriods->aliasField('name'),
                        'type'=>$curricular_types->aliasField('name'),
                        'category'=>$InstitutionCurriculars->aliasField('category'),
                ])
             ->LeftJoin([$InstitutionCurriculars->alias() => $InstitutionCurriculars->table()],
                    [$InstitutionCurriculars->aliasField('id').' = ' . $this->aliasField('institution_curricular_id')
                ])
             ->LeftJoin([$academicPeriods->alias() => $academicPeriods->table()],
                    [$academicPeriods->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('academic_period_id')
                    ])
                ->LeftJoin([$curricular_types->alias() => $curricular_types->table()],
                    [$curricular_types->aliasField('id').' = ' . $InstitutionCurriculars->aliasField('curricular_type_id')
                ])->where([$this->aliasField('student_id') => $studentId]);
                return $query ;

                
 
    }


    
}
