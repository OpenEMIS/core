<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Utility\Security; 
use Cake\Datasource\ConnectionManager;

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
        $this->toggle('add', true);
        $this->toggle('search', true);
        $this->toggle('edit', false);
        $this->toggle('view', true);
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
                        'start_date'=>$this->aliasField('start_date'),
                        'end_date'=>$this->aliasField('end_date'),
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

        $this->field('student_id', ['visible' => false]);
        $this->field('institution_curricular_id', ['visible' => true]);
        $this->field('curricular_position_id', ['visible' => true]);
        $this->field('start_date', ['visible' => true]);
        $this->field('end_date', ['visible' => true]);
        $this->field('hours', ['visible' => false]);
        $this->field('points', ['visible' => false]);
        $this->field('location', ['visible' => false]);
        $this->field('comments', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => true]);
        $this->field('type', ['visible' => ['index'=>true,'view' => true,'edit' => false,'add'=>false]]);
        $this->field('category',  ['visible' => ['index'=>false,'view' => true,'edit' => false,'add'=>false]]);

        $this->field('education_grade', ['visible' => true]);
        $this->field('institution_class', ['visible' => true]);
        $this->field('curricular_category', ['visible' => true]);
        $this->setFieldOrder([
        'academic_period_id','education_grade','institution_class', 'curricular_category','institution_curricular_id','type', 'curricular_position_id','start_date','end_date']);
        if ($this->controller->name == 'Profiles') {
            unset($settings['indexButtons']['view']);
        }

	}

    public function onGetEducationGrade(Event $event, Entity $entity)
    {    
        $session = $this->request->session();
        $sId = $session->read('Student.Students.id');
        $ins_id = $entity->institution_curricular->institution_id;
        $academic_period_id = $entity->institution_curricular->academic_period_id;
        $connection = ConnectionManager::get('default');
        $ins_class_rec = $connection->query("SELECT education_grades.name,education_programmes.name FROM institution_class_students LEFT JOIN education_grades ON education_grades.id=institution_class_students.education_grade_id LEFT JOIN education_programmes ON education_programmes.id=education_grades.education_programme_id WHERE institution_class_students.student_id=".$sId.' AND institution_class_students.academic_period_id='.$academic_period_id.' AND institution_class_students.institution_id='.$ins_id.' Order by institution_class_students.id desc limit 1');
        $ins_class_data = $ins_class_rec->fetch();
        return (!empty( $ins_class_data)) ?  $ins_class_data[1] .' - '.$ins_class_data[0] : '--';
    }

    public function onGetInstitutionClass(Event $event, Entity $entity)
    {    
        $session = $this->request->session();
        $sId = $session->read('Student.Students.id');
        $ins_id = $entity->institution_curricular->institution_id;
        $academic_period_id = $entity->institution_curricular->academic_period_id;
        $connection = ConnectionManager::get('default');
        $ins_class_rec = $connection->query("SELECT institution_classes.name FROM institution_class_students LEFT JOIN institution_classes ON institution_classes.id=institution_class_students.institution_class_id  WHERE institution_class_students.student_id=".$sId.' AND institution_class_students.academic_period_id='.$academic_period_id.' AND institution_class_students.institution_id='.$ins_id.' Order by institution_class_students.id desc limit 1');
        $ins_class_data = $ins_class_rec->fetch();
        return (!empty( $ins_class_data)) ?  $ins_class_data[0] : '--';
    }


    public function onGetType(Event $event, Entity $entity)
    {
        $connection = ConnectionManager::get('default');
        $results = $connection->query("SELECT name FROM curricular_types WHERE id=".$entity->institution_curricular->curricular_type_id);
        $curr_type = $results->fetch();
        return (!empty( $curr_type)) ?  $curr_type[0] : '--';

    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        $connection = ConnectionManager::get('default');
        $results = $connection->query("SELECT name FROM academic_periods WHERE id=".$entity->institution_curricular->academic_period_id);
        $acadmeic_data = $results->fetch();
        return (!empty( $acadmeic_data)) ?  $acadmeic_data[0] : '--';

    }

    public function onGetCurricularCategory(Event $event, Entity $entity)
    {
        return $entity['institution_curricular']['category'] ? __('Curricular') : $entity->category ? __('Curricular') : __('Extracurricular');    
        
    }
    public function onGetCategory(Event $event, Entity $entity)
    {
        return $entity['institution_curricular']['category'] ? __('Curricular') : __('Extracurricular');
    }
    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {       
        $this->setupTabElements();
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        
        $this->field('academic_period_id', ['visible' => true]);
        // $this->field('category', ['visible' => true]);
        $this->field('education_grade', ['visible' => true]);
        $this->field('institution_class', ['visible' => true]);
        $this->field('student_id', ['visible' => true]);
        $this->field('curricular_category', ['visible' => true]);
        $this->field('type', ['visible' => true]);

        $this->field('curricular_position_id', ['visible' => true]);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        // $this->field('student_id', ['visible' => false]);
        $InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        $result = $InstitutionCurriculars
        ->find()
        ->select(['id','name'])
        ->all();

        $ic_arr = [];
        if(!empty( $result)){
            foreach( $result as $key => $val){
                $ic_arr[$val->id] = $val->name;
            }
        }

        $curricularPosition = TableRegistry::get('curricular_positions');
        $result1 = $curricularPosition
        ->find()
        ->select(['id','name'])
        ->all();

        $cp_arr = [];
        if(!empty( $result1)){
            foreach( $result1 as $key => $val){
                $cp_arr[$val->id] = $val->name;
            }
        }
        $session = $this->request->session();
        $sId = $session->read('Student.Students.id');
        $this->field('institution_curricular_id', ['type' => 'select', 'options' => $ic_arr]);
        $this->field('start_date');
        $this->field('end_date');
        $this->field('curricular_position_id', ['type' => 'select', 'options' => $cp_arr]);
        $this->field('hours');
        $this->field('points');
        $this->field('location');
        $this->field('comments', ['type' => 'text']);
        $this->field('student_id', ['type' => 'hidden','value'=> $sId]);
        $this->field('id', ['type' => 'hidden','value'=> Security::hash(time(), 'sha256')]);
    }

  
   
}
