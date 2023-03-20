<?php
namespace Institution\Model\Table;

use ArrayObject;
use stdClass;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Collection\Collection;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\Routing\Router;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Datasource\ResultSetInterface;
use Cake\Network\Session;

//POCOR-6673
class InstitutionCurricularsTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('CurricularTypes', ['className' => 'FieldOption.CurricularTypes']);
        
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->addBehavior('Excel', ['pages' => ['index','view']]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {   
        $query = $this->request->query;
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $institutionId = $extra['institution_id'];
        $selectedAcademicPeriodId = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
       
        $this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriodId);
        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;
        $extra['elements']['control'] = [
            'name' => 'Institution.Associations/controls',
            'data' => [
                'academicPeriodOptions'=>$academicPeriodOptions,
                'selectedAcademicPeriod'=>$selectedAcademicPeriodId
            ],
            'options' => [],
            'order' => 3
        ];

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $sortable = !is_null($this->request->query('sort')) ? true : false;
        $session = $this->controller->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $curricularStudent = TableRegistry::get('institution_curricular_students');
        $users = TableRegistry::get('security_users');
        $data = $curricularStudent->find()
                     ->select(['male_students' => "(
                                 COUNT(DISTINCT(CASE WHEN security_users.gender_id = 1 THEN institution_curricular_students.student_id END)) )",
                                'female_students' => "(
                                 COUNT(DISTINCT(CASE WHEN security_users.gender_id = 2 THEN institution_curricular_students.student_id END)) )",
                                'institution_curricular_id'
                            ])
                    ->innerJoin([$users->alias() => $users->table()],
                    [$users->aliasField('id').' = ' . $curricularStudent->aliasField('student_id')])
                    ->group([$curricularStudent->aliasField('student_id')]);

        $query->select([
                'id',
                'name',
                'total_male_students',
                'total_female_students',
                'institution_id',
                'academic_period_id',
                'modified_user_id',
                'modified',
                'created_user_id',
                'created',
            ])
            ->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId'],
            $this->aliasField('institution_id') => $institutionId])
            ->group([$this->aliasField('id')]);

        if (!$sortable) {
            $query
                ->order([
                    $this->aliasField('name') => 'ASC'
                ]);
        }
        $this->controllerAction = $extra['indexButtons']['view']['url']['action'];
        $query = $this->request->query;
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('total_male_students', ['visible' => ['index'=>true,'view' => false, 'edit' => false,'add'=>false]]);
        $this->field('total_female_students', ['visible' => ['index'=>true,'view' => false,'edit' => false,'add'=>false]]);
        $this->field('total_students', ['visible' => ['index'=>true,'view' => false,'edit' =>false,'add'=>false]]);
        $this->field('curricular_type_id', ['visible' => ['index'=>false]]);
        $this->field('category', ['visible' => ['index'=>false]]);
        $this->field('staff_id', ['visible' => ['index'=>false]]);
        $this->setFieldOrder([
            'name', 'staff_id','category','total_male_students', 'total_female_students', 'total_students'
        ]);
        
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        
        $this->field('total_male_students', ['visible' => false]);
        $this->field('total_female_students', ['visible' => false]);
        $this->field('curricular_type_id', ['type' => 'select']);
        $this->field('category', ['type' => 'select']);
        $this->field('staff_id', ['type' => 'select','visible' => false]);
        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['view' => true, 'edit' => true]]);
        $this->setFieldOrder([
            'academic_period_id','name','category', 'curricular_type_id']);
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        
        $this->field('total_male_students', ['visible' => false]);
        $this->field('total_female_students', ['visible' => false]);
        $this->field('curricular_type_id', ['type' => 'select']);
        $this->field('category', ['type' => 'select']);
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('staff_id', ['type' => 'select']);
        $this->setFieldOrder([
            'academic_period_id','name','category', 'curricular_type_id','staff_id']);
    }

    public function onUpdateFieldCategory(Event $event, array $attr, $action, Request $request)
    {
        $categories = array(1 =>'Curricular', 0=>'Extracurricular');
        $entity = $attr['entity'];
        if ($action == 'add') {
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['id' => '-- ' . __('Select Category') . ' --']+$categories;
            $attr['onChangeReload'] = 'changeStatus';
        }
        elseif ($action == 'edit') {
            $attr['type'] = 'readonly';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['id' => '-- ' . __('Select Category') . ' --']+$categories;
            $attr['onChangeReload'] = 'changeStatus';
        }
        return $attr;
    }


    public function onUpdateFieldCurricularTypeId(Event $event, array $attr, $action, Request $request)
    {
        $entity->institution_curricular_id = $_SESSION['curricularId'];
        $categoryId = $this->request->data[$this->alias()]['category'];
        $type = TableRegistry::get('curricular_types');
        $this->InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        $getCurricularsType = $type->find('list')->where(['category'=>$categoryId])->toArray();
        if ($action == 'add') {
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['id' => '-- ' . __('Select Type') . ' --']+$getCurricularsType;
            $attr['onChangeReload'] = false;
        }elseif($action == 'edit'){
            $curriculardecode = $entity->institution_curricular_id;
            $tyepId = $this->InstitutionCurriculars->get($curriculardecode)->curricular_type_id;
            $attr['type'] = 'readonly';
            $attr['value'] = $tyepId;
            $attr['attr']['value'] = $type->get($tyepId)->name;
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $entity->institution_curricular_id = $_SESSION['curricularId'];
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodId = !is_null($request->data($this->aliasField('academic_period_id'))) ? $request->data($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();
        $this->InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        if ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('academic_period_id')));
                $attr['options'] = $periodOptions;
                $attr['default'] = $selectedPeriod;
                $attr['onChangeReload'] = true;
            } else if ($action == 'edit') {
                $curriculardecode = $entity->institution_curricular_id;
                $academicPeriodId = $this->InstitutionCurriculars->get($curriculardecode)->academic_period_id;
                $attr['type'] = 'readonly';
                $attr['value'] = $academicPeriodId;
                $attr['attr']['value'] = $this->AcademicPeriods->get($academicPeriodId)->name;

            }
        }
        return $attr;
    }

    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList();

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
    {
        $entity->institution_curricular_id = $_SESSION['curricularId'];
        if ($action == 'edit') {
            $staffOptions = [];
            $this->InstitutionCurriculars = TableRegistry::get('institution_curriculars');
            $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $curriculardecode = $entity->institution_curricular_id;
            $selectedPeriod = $this->InstitutionCurriculars->get($curriculardecode)->academic_period_id;

            $entity = $attr['entity'];
            if (!empty($selectedPeriod)) {
                $institutionId = $this->Session->read('Institution.Institutions.id');
                $Staff = TableRegistry::get('Institution.Staff');
                $staffOptions = $Staff
                ->find('list', ['keyField' => 'staff_id', 'valueField' => 'staff_name'])
                ->matching('Users')
                ->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
                ->where([$Staff->aliasField('institution_id') => $institutionId])
                ->order(['Users.first_name', 'Users.last_name'])
                ->toArray();
            }

            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = true;
            $attr['select'] = false;
            $attr['options'] = ['id' => '-- ' . __('Select Staff') . ' --']+$staffOptions;
            $attr['onChangeReload'] = false;
        } 
        return $attr;
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
        $entity->institution_curricular_id = $_SESSION['curricularId'];
        $curricularStaff = TableRegistry::get('institution_curricular_staff'); 
        $curricularId = $entity->institution_curricular_id;
        $currentTimeZone = date("Y-m-d H:i:s");
            if(!empty($entity->staff_id['_ids'])){
                $StaffIds = $entity->staff_id['_ids'];
                foreach($StaffIds as $staffId){
                   $checkCurricularStaff = $curricularStaff->find()->where(['staff_id'=>$staffId, 'institution_curricular_id'=>$curricularId])->first(); 

                   if(empty($checkCurricularStaff)){
                        $data = [      
                                    'id'=> Text::uuid(),  
                                    'staff_id' => $staffId,
                                    'institution_curricular_id' => $curricularId,
                                    'created_user_id' => 1,
                                    'created' => $currentTimeZone,
                                    'modified_user_id' => 1,
                                    'modified' => $currentTimeZone,
                                ];
                        $entity = $curricularStaff->newEntity($data);
                       $save =  $curricularStaff->save($entity);
                   }
                }
            }                         
    }


    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $tabElements = $this->controller->getCurricularsTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'InstitutionCurriculars');
        
        $this->field('staff_id', ['visible' => true]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        session_start();
        if(!empty($this->request->pass[1])){
            $curricularId = $this->paramsDecode($this->request->pass[1])['id'];
            $_SESSION["curricularId"] = $curricularId;
         }
    }
    public function onGetCategory(Event $event, Entity $entity)
    {
        return $entity->category ? __('Curricular') : __('Extracurricular');
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
       if ($field == 'total_male_students') {
            return  __('Male Students');
        } else if ($field == 'total_female_students') {
            return  __('Female Students');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetStaffId(Event $event, Entity $entity)
    {
        $curricularId = $entity->id ;
        $curricularStaff = TableRegistry::get('institution_curricular_staff'); 
        $users = TableRegistry::get('security_users'); 
        $data = $curricularStaff->find()->select(['openemis_no'=>$users->aliasField('openemis_no'),'first_name'=>$users->aliasField('first_name'),'middle_name'=>$users->aliasField('middle_name'),'third_name'=>$users->aliasField('third_name'),'last_name'=>$users->aliasField('last_name')])
                ->leftJoin([$users->alias() => $users->table()],
                    [$users->aliasField('id').' = ' . $curricularStaff->aliasField('staff_id')
                ])
                ->where([$curricularStaff->aliasField('institution_curricular_id') => $curricularId ])->toArray();
        $staff = [];
        foreach($data as $value){
            $staff[] = $value->openemis_no.' - '.$value->first_name.' '.$value->middle_name.' '.$value->third_name.' '.$value->last_name;
        }
        return implode(', ', $staff);
    }

    public function beforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        $curricularStaff = TableRegistry::get('institution_curricular_staff'); 
        $curricularStudent = TableRegistry::get('institution_curricular_students'); 
        $curriculars = TableRegistry::get('institution_curriculars'); 
        $users = TableRegistry::get('security_users'); 
        $checkStudent =  $curricularStudent->find()->where([$curricularStudent->aliasField('institution_curricular_id')=>$entity->id])->first();     
        $checkStaff =  $curricularStaff->find()->where([$curricularStaff->aliasField('institution_curricular_id')=>$entity->id])->first();     
        if(!empty($checkStudent) || !empty($checkStaff)){
            $message = __('Its Associated with Other Data');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
        }
    }

    public function onGetTotalStudents(Event $event, Entity $entity)
    {
        $total = $entity->total_male_students + $entity->total_female_students ;
        return $total;
    }
    
}