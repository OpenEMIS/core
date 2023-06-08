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
        $this->hasMany('InstitutionCurricularStaff', ['className' => 'Institution.InstitutionCurricularStaff', 'dependent' => true, 'cascadeCallbacks' => true]);
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
       // $this->field('staff_id', ['type' => 'select']);
        $this->setFieldOrder([
            'academic_period_id','name','category', 'curricular_type_id']);
        $entity->institution_curricular_id = $_SESSION['curricularId'];
        $curricularStaff = TableRegistry::get('institution_curricular_staff');
        $getStaff = $curricularStaff->find()->select(['staff_id'])
                    ->where([$curricularStaff->aliasField('institution_curricular_id') => $entity->institution_curricular_id]);
        $staff = [];
        if(!empty($getStaff)){
            foreach($getStaff as $value){
                $staff[] = $value->staff_id; 
            }
        }

        $InstitutionStaff = TableRegistry::get('institution_staff');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $UserData = TableRegistry::get('User.Users');
        $session = $this->controller->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $this->InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $curriculardecode = $entity->institution_curricular_id;
        $selectedPeriod = $this->InstitutionCurriculars->get($curriculardecode)->academic_period_id;
        $join = [];
        $join[''] = [
        'type' => 'inner',
        'table' => "(SELECT institution_staff.staff_id user_id
                        FROM institution_staff
                        INNER JOIN academic_periods
                        ON (((institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.start_date AND institution_staff.end_date >= academic_periods.start_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.end_date AND institution_staff.end_date >= academic_periods.end_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date >= academic_periods.start_date AND institution_staff.end_date <= academic_periods.end_date)) OR (institution_staff.end_date IS NULL AND institution_staff.start_date <= academic_periods.end_date))
                        WHERE academic_periods.id = $selectedPeriod
                        AND institution_staff.institution_id = $institutionId
                        AND institution_staff.staff_status_id = 1
                        GROUP BY institution_staff.staff_id
                            ) subq",
                            'conditions' => ['subq.user_id = Users.id'],
                ];
        $requestorOptions = $UserData
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name_with_id'
            ])
            ->select([
                    $UserData->aliasField('id'),
                    $UserData->aliasField('openemis_no'),
                    $UserData->aliasField('first_name'),
                    $UserData->aliasField('middle_name'),
                    $UserData->aliasField('third_name'),
                    $UserData->aliasField('last_name')
            ])->order([$UserData->aliasField('first_name'),]);

          $data =   $requestorOptions->join($join)->toArray();
        
          $this->field('staff_id', [
            'type' => 'chosenSelect',
            'attr' => [
                'label' => __('Staff')
            ]
        ]);
        $this->fields['staff_id']['options'] = $data;
        
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
        if($categoryId == null){
            $categoryId = $categoryData ? 0 : 1;
        }
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

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
        $entity->institution_curricular_id = $_SESSION['curricularId'];
        $curricularStaff = TableRegistry::get('institution_curricular_staff'); 
        $curricularId = $entity->institution_curricular_id;
        $currentTimeZone = date("Y-m-d H:i:s");
            if(!empty($entity->staff_id['_ids'])){
                $StaffIds = $entity->staff_id['_ids'];
                $checkCurricularStaff = $curricularStaff->find()->where(['institution_curricular_id'=>$curricularId])->toArray(); 
                   if(!empty($checkCurricularStaff)){
                       $deleteStaff =  $curricularStaff->deleteAll(['institution_curricular_id' => $curricularId]);
                   }
                foreach($StaffIds as $staffId){
                   $CurricularStaff = $curricularStaff->find()->where(['staff_id'=>$staffId, 'institution_curricular_id'=>$curricularId])->first(); 

                   if(empty($CurricularStaff)){
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

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //show staff selected in multiselected dropdown, chosenselec
        $entity->institution_curricular_id = $_SESSION['curricularId'];
        $curricularStaff = TableRegistry::get('institution_curricular_staff');
        $getStaff = $curricularStaff->find()->select(['staff_id'])
                    ->where([$curricularStaff->aliasField('institution_curricular_id') => $entity->institution_curricular_id])->toArray();
        $staff = [];
        $count = 0 ;
        if(!empty($getStaff)){
            foreach($getStaff as $key => $value){
                $staff[$key] = ['id' => $value->staff_id]; 
            }
        }
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use($staff) {
            return $results->map(function ($row) use($staff) {
                $row['staff_id'] = $staff;
                return $row;
            });
        });
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $selectedAcademicPeriodId = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $query
            ->select([
                'name' => $this->aliasField('name'),
                'category' => $this->aliasField('category'),
                'CurricularType' => 'CurricularTypes.name',
                'Institution_name' => 'Institutions.name',
                'Institution_code' => 'Institutions.code',
                'academic_period_name' => 'AcademicPeriods.name',
                'female_students' => $this->aliasField('total_female_students'),
                'male_students' => $this->aliasField('total_male_students'),
            ])
            ->contain(['Institutions','CurricularTypes','AcademicPeriods'])
            ->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriodId, $this->aliasField('institution_id') => $institutionId]);

    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) {

        $newArray = [];
        $newArray[] = [
            'key' => 'name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Name')
        ];
        $newArray[] = [
            'key' => 'category_name',
            'field' => 'category_name',
            'type' => 'string',
            'label' => __('Category')
        ];
        $newArray[] = [
            'key' => 'CurricularType',
            'field' => 'CurricularType',
            'type' => 'string',
            'label' => __('Curricular Type')
        ];
        $newArray[] = [
            'key' => 'Institution_name',
            'field' => 'Institution_name',
            'type' => 'string',
            'label' => __('Institution name')
        ];
        $newArray[] = [
            'key' => 'Institution_code',
            'field' => 'Institution_code',
            'type' => 'string',
            'label' => __('Institution code')
        ];
        $newArray[] = [
            'key' => 'academic_period_name',
            'field' => 'academic_period_name',
            'type' => 'string',
            'label' => __('Academic Period')
        ];
        $newArray[] = [
            'key' => 'total_female_students',
            'field' => 'total_female_students',
            'type' => 'string',
            'label' => __('Female Students')
        ];
        $newArray[] = [
            'key' => 'total_male_students',
            'field' => 'total_male_students',
            'type' => 'string',
            'label' => __('Male Students')
        ];
        $fields->exchangeArray($newArray);
    }

    public function onExcelGetCategoryName(Event $event, Entity $entity)
    {
        if($entity->category == 1){
            return 'Curricular';
        }else{
             return 'Extracurricular';
        }
    }
    
}