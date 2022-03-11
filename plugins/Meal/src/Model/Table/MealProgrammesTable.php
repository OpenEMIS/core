<?php
namespace Meal\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use App\Model\Traits\MessagesTrait;

class MealProgrammesTable extends ControllerActionTable
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    { 
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('MealProgrammeTypes', ['className' => 'Meal.MealProgrammeTypes','foreignKey' => 'type']);
        $this->belongsTo('MealTargetTypes', ['className' => 'Meal.MealTargetTypes','foreignKey' => 'targeting']);
        $this->belongsTo('MealImplementers', ['className' => 'Meal.MealImplementers','foreignKey' => 'implementer']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentMeals' => ['index', 'view']
        ]);
        $this->belongsToMany('MealNutritions', [
            'className' => 'Meal.MealNutritions',
            'joinTable' => 'meal_nutritional_records',
            'foreignKey' => 'meal_programmes_id',
            'targetForeignKey' => 'nutritional_content_id',
            'through' => 'Meal.MealNutritionalRecords',
            'dependent' => true
        ]);

        // $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        // $this->addBehavior('Area.Areapicker');
        // $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->Institutions = TableRegistry::get('Institution.Institutions');

    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $extra['selectedAcademicPeriodOptions'] = $this->getSelectedAcademicPeriod($this->request);

        $extra['elements']['control'] = [
            'name' => 'Institution.MealProgramme/controls',
            'data' => [
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriod'=> $extra['selectedAcademicPeriodOptions']
            ],
            'order' => 3
        ];

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        

        $this->field('academic_period_id',['visible' => false]);
        $this->field('area_id',['visible' => false]);
        $this->field('institution_id',['visible' => false]);
        $this->field('code');
        $this->field('name');
        $this->field('type');
        $this->field('targeting');
        $this->field('start_date');
        $this->field('end_date');
        $this->field('amount');
        $this->field('meal_nutritions',['visible' => false]);
        $this->field('implementer',['visible' => false]);

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if (array_key_exists('selectedAcademicPeriodOptions', $extra)) {
            $query->where([
                        $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']
                    ], [], true); //this parameter will remove all where before this and replace it with new where.
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // $institutionId = $entity->institution_id;
        // $entity->institution_id = $institutionId;

        $MealInstitutionProgrammes = TableRegistry::get('Meal.MealInstitutionProgrammes');
        $result=$this->find('all',['fields'=>'id'])->last();

        $record_id=$result->id;
        $institutionIds = $entity->institution_id;
        $institutionIdsData = $institutionIds['_ids'];
        foreach($institutionIdsData AS $key => $value)
        {
            try{
                $data = $MealInstitutionProgrammes->newEntity([
                    'meal_programme_id' => $record_id,
                    'institution_id' => $value,
                    'created_user_id' => 2
                ]);
    
                $saveData = $MealInstitutionProgrammes->save($data);
            }
            catch (PDOException $e) {
                echo "<pre>";print_r($e);die;
            }
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $typeOptions = $this->MealNutritions->find('list')->toArray();
        $institutionsOptions = $this->Institutions->find('list')->toArray();
     
        // echo "<pre>"; print_r($institutionsOptions); die();
        $this->field('academic_period_id',['select' => false]);
        $this->field('code');
        $this->field('name');
        $this->field('type',['select' => false]);
        $this->field('targeting');
        $this->field('start_date');
        $this->field('end_date');
        $this->field('amount');
        $this->field('meal_nutritions', [
            'type' => 'chosenSelect',
            'attr' => [
                'label' => __('Nutritional Content')
            ],
            'options' => $typeOptions
        ]);

        $this->field('implementer');
        // $this->field('institution_id', [
        //     'attr' => [
        //         'label' => __('Beneficiary institutions')
        //     ],
        //     'options' => $institutionsOptions
        //     // 'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        // ]);
        $this->field('area_id', ['title' => __('Area Education'), 'source_model' => 'Area.Areas', 'displayCountry' => false,'attr' => ['label' => __('Area Education')]]);
        
    }
   
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

            $attr['options'] = $periodOptions;
           
            $attr['default'] = $selectedPeriod;
        } else if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $entity->academic_period->name;
        }
        return $attr;
    }

    public function onUpdateFieldType(Event $event, array $attr, $action, Request $request)
    {
        list($levelOptions, $selectedLevel) = array_values($this->getSelectOptions());
        $attr['options'] = $levelOptions;
        if ($action == 'add') {
            $attr['default'] = $selectedLevel;
        }

        return $attr;
    }

    public function getSelectOptions()
    {
        $MealTypes = TableRegistry::get('Meal.MealProgrammeTypes');
        $levelOptions = $MealTypes
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->toArray();
           
         $selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

         return compact('levelOptions', 'selectedLevel');
    }

    public function onUpdateFieldTargeting(Event $event, array $attr, $action, Request $request)
    {
        list($levelOptions, $selectedLevel) = array_values($this->getTargetingOptions());
        $attr['options'] = $levelOptions;
        if ($action == 'add') {
            $attr['default'] = $selectedLevel;
        }

        return $attr;
    }

    public function getMealOptions($querystringMeal)
    {
        if (!empty($querystringMeal)) {
            $list = $this
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                 ->where([ $this->aliasField('academic_period_id') => $querystringMeal ])
                ->toArray();
        }
        else{
            $list = $this
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->toArray();
        }

        return $list;
    }

    public function getTargetingOptions()
    {
        $MealTrageting = TableRegistry::get('Meal.MealTargetTypes');
        $levelOptions = $MealTrageting
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->toArray();
           
         $selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

         return compact('levelOptions', 'selectedLevel');
    }

    public function onUpdateFieldMealNutritions(Event $event, array $attr, $action, Request $request)
    {
        list($levelOptions, $selectedLevel) = array_values($this->getNutritionalOptions());
        $attr['options'] = $levelOptions;
        if ($action == 'add') {
            $attr['default'] = $selectedLevel;
        }

        return $attr;
    }

     public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'MealNutritions'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity) {
 
        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {     
        $this->setupFields($entity);
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $extra)
    {
    // echo "<pre>";    print_r($entity); die();

            $MealNutritions = TableRegistry::get('meal_nutritional_records');
            $conditions = [
                $MealNutritions->aliasField('meal_programmes_id') => $extra['MealProgrammes']['id']
            ];    

            $MealNutritions->deleteAll($conditions);
            $MealNutritions->newEntity();

    }

    private function setupFields(Entity $entity = null) {

        $attr = [];
        if (!is_null($entity)) {
            $attr['attr'] = ['entity' => $entity];
        }

        $this->field('academic_period_id',['select' => false]);
        $this->field('code');
        $this->field('name');
        $this->field('targeting');
        $this->field('start_date');
        $this->field('end_date');
        $this->field('amount');
        $this->field('area_id', ['type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => false]);
        $this->field('institution_id', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $this->field('type',['select' => false]);
        $this->field('meal_nutritions', [
            'type' => 'chosenSelect',
            'attr' => [
                'label' => __('Nutritional Content')
            ],
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);

        $this->field('implementer');
     
    }

    public function getNutritionalOptions()
    {
        $MealNutritions = TableRegistry::get('Meal.MealNutritions');
        $levelOptions = $MealNutritions
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->toArray();
           
         $selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

         return compact('levelOptions', 'selectedLevel');
    }

    public function onUpdateFieldImplementer(Event $event, array $attr, $action, Request $request)
    {
        list($levelOptions, $selectedLevel) = array_values($this->getImplementerOptions());
        $attr['options'] = $levelOptions;
        if ($action == 'add') {
            $attr['default'] = $selectedLevel;
        }

        return $attr;
    }

    public function getImplementerOptions()
    {
        $MealImplementers = TableRegistry::get('Meal.MealImplementers');
        $levelOptions = $MealImplementers
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->toArray();
           
         $selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

         return compact('levelOptions', 'selectedLevel');
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

    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';

        if ($this->action == 'index' || $this->action == 'view' || $this->action == 'edit') {
            if (isset($request->query) && array_key_exists('period', $request->query)) {
                $selectedAcademicPeriod = $request->query['period'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }

        return $selectedAcademicPeriod;
    } 

    public function getMealProgrammesOptions($options)
    {
        $MealInstitutionProgrammes = TableRegistry::get('Meal.MealInstitutionProgrammes');
        $MealProgramme = TableRegistry::get('Meal.MealProgrammes');
        $list = $MealProgramme
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->innerJoin(
                [$MealInstitutionProgrammes->alias() => $MealInstitutionProgrammes->table()], [
                    $MealProgramme->aliasField('id = ') . $MealInstitutionProgrammes->aliasField('meal_programme_id')
                ]
            )
            ->where([
                $MealProgramme->aliasField('academic_period_id') => $options['academid_period_id'],
                $MealInstitutionProgrammes->aliasField('institution_id') => $options['institution_id']
            ])
            ->toArray();
        return $list;
    } 
    public function findMealInstitutionProgrammes(Query $query, array $options){
        $institutionId = $options['institution_id'];  
        return $query
        ->where([
            $this->aliasField('institution_id') => $institutionId])
        ->orWhere([ 
            $this->aliasField('institution_id') => 0 ]);
    }

     public function onGetAreaId(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            $areaName = $entity->Areas['name'];
            // Getting the system value for the area
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $areaLevel = $ConfigItems->value('institution_area_level_id');

            // Getting the current area id
            $areaId = $entity->area_id;
            try {
                if ($areaId > 0) {
                    $path = $this->Areas
                    ->find('path', ['for' => $areaId])
                    ->contain('AreaLevels')
                    ->toArray();

                    foreach ($path as $value) {
                        if ($value['area_level']['level'] == $areaLevel) {
                            $areaName = $value['name'];
                        }
                    }
                }
            } catch (InvalidPrimaryKeyException $ex) {
                $this->log($ex->getMessage(), 'error');
            }
            return $areaName;
        }
        return $areaName;
        // return $entity->area_id;;
    } 

     public function onGetInstitutionId(Event $event, Entity $entity)
    {
        if ($entity->institution) {
            return $entity->institution->code_name;
        } else {
            return __('Private Candidate');
        }
    } 

    public function onUpdateFieldAreaId(Event $event, array $attr, $action, Request $request)
    {

                    $Areas = TableRegistry::get('Area.Areas');
                    $entity = $attr['entity'];

                    if ($action == 'add' || $action == 'edit') {
                        $areaOptions = $Areas
                            ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                            ->order([$Areas->aliasField('order')]);

                        $attr['type'] = 'chosenSelect';
                        $attr['attr']['multiple'] = true;
                        $attr['select'] = true;
                        $attr['options'] = ['0' => __('All Areas')] + $areaOptions->toArray();
                        $attr['onChangeReload'] = true;
                    } else {
                        $attr['type'] = 'hidden';
                    }
           
        return $attr;

    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
            $areaId = isset($request->data) ? $request->data['MealProgrammes']['area_id']['_ids'] : 0;
            $institutionList = [];
            $InstitutionsTable = TableRegistry::get('Institution.Institutions');
            $InstitutionStatusesTable = TableRegistry::get('Institution.Statuses');
            $activeStatus = $InstitutionStatusesTable->getIdByCode('ACTIVE');
            if ($areaId > 0) {
                $institutionQuery = $InstitutionsTable
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code_name'
                ])
                ->where([
                    $InstitutionsTable->aliasField('area_id IN') => $areaId,
                    $InstitutionsTable->aliasField('institution_status_id') => $activeStatus
                ])
                ->order([
                    $InstitutionsTable->aliasField('code') => 'ASC',
                    $InstitutionsTable->aliasField('name') => 'ASC'
                ]);
            } 

            else{
                $institutionQuery = $InstitutionsTable
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code_name'
                ])
                ->where([
                    $InstitutionsTable->aliasField('institution_status_id') => $activeStatus
                ])
                ->order([
                    $InstitutionsTable->aliasField('code') => 'ASC',
                    $InstitutionsTable->aliasField('name') => 'ASC'
                ]);
            }
            $institutionList = $institutionQuery->toArray();
            if (count($institutionList) > 1) {
             $institutionOptions = ['0' => __('All Institutions')] + $institutionList;
         } else {
            $institutionOptions =  $institutionList;
        }

                    // $institutionOptions = ['' => '-- '.__('Select').' --'] + $institutionList;
        $attr['type'] = 'chosenSelect';
        $attr['onChangeReload'] = true;
        $attr['attr']['multiple'] = true;
        $attr['options'] = $institutionOptions;
        $attr['attr']['required'] = true;
        
        return $attr;
    }
    
}
