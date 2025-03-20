<?php
namespace Meal\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use App\Model\Traits\MessagesTrait;
use Cake\Http\ServerRequest;
use AllowDynamicProperties;

#[AllowDynamicProperties] class MealProgrammesTable extends ControllerActionTable
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
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
       $this->hasMany('MealFoodRecords',
           ['className' => 'Meal.MealFoodRecords',
               'foreignKey' => 'meal_programmes_id']); //POCOR-7363

        // $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        // $this->addBehavior('Area.Areapicker');
        // $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->Institutions = TableRegistry::get('Institution.Institutions');
        // $this->AreaLevels = TableRegistry::get('Area.AreaLevels'); //POCOR-6920

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
        // $this->field('area_level_id',['visible' => false]); //POCOR-6920
        $this->field('institution_id',['visible' => false]);
        $this->field('code');
        $this->field('name');
        $this->field('type');
        $this->field('targeting');
        $this->field('start_date');
        $this->field('end_date');
        $this->field('amount');
        $this->field('meal_nutritions',['visible' => false]);
        $this->field('food_type_id',['visible' => false]);//POCOR-7363
        $this->field('implementer',['visible' => false]);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Meals Programme','Meals');
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if (isset($extra['selectedAcademicPeriodOptions'])) {
            $query->where([
                        $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']
                    ], [], true); //this parameter will remove all where before this and replace it with new where.
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {

        $this->updateMealInstitutionRecords($entity);
        $this->updateMealFoodRecords($entity);
    }
    /**
     * Updates institution records for a meal programme after save, ensuring only changes are applied.
     * Area ID is fetched from the institution's data before inserting into MealInstitutionProgrammes.
     */
    private function updateMealInstitutionRecords(Entity $entity)
    {
        // Check if institution_id is dirty, return early if not changed
        if (!$entity->isDirty('institution_id')) {
            return;
        }
        $record_id = $entity->id;
        if(empty($record_id)){
            Log::debug('Record id not found, skipping update');
            return;
        }

        $MealInstitutionProgrammes = TableRegistry::getTableLocator()->get('Meal.MealInstitutionProgrammes');
        $InstitutionTable = TableRegistry::getTableLocator()->get('Institution.Institutions');

        $newInstitutionIds = array_map('intval', $entity->institution_id['_ids'] ?? []);

        // Fetch existing institution records for this meal programme
        $existingInstitutionRecords = $MealInstitutionProgrammes->find()
            ->select(['institution_id'])
            ->distinct(['institution_id'])
            ->where(['meal_programme_id' => $record_id])
            ->extract('institution_id')
            ->toArray();

        $existingInstitutionIds = array_map('intval', $existingInstitutionRecords);

        // Determine which institutions to add and which to remove
        $toAdd = array_diff($newInstitutionIds, $existingInstitutionIds);
        $toRemove = array_diff($existingInstitutionIds, $newInstitutionIds);

        // Remove only institutions that are no longer selected
        if (!empty($toRemove)) {
            $MealInstitutionProgrammes->deleteAll([
                'meal_programme_id' => $record_id,
                'institution_id IN' => $toRemove
            ]);
            Log::debug('Removed institution ids: ' . implode(', ', $toRemove));
        }

        // Add new institutions
        foreach ($toAdd as $institutionId) {
            // Fetch area_id from the institution
            $institution = $InstitutionTable->find()
                ->select(['area_id'])
                ->where(['id' => $institutionId])
                ->first();

            $MealInstitution = $MealInstitutionProgrammes->newEntity([
                'meal_programme_id' => $record_id,
                'institution_id' => $institutionId,
                'area_id' => $institution->area_id ?? null, // Store the institution's area_id
                'created_user_id' => $this->Auth->user('id'),
                'created' => date('Y-m-d H:i:s')
            ]);
            $MealInstitutionProgrammes->save($MealInstitution);
        }
        Log::debug('Added institution ids: ' . implode(', ', $toAdd));
    }
    /**
     * Updates food type records for a meal programme after save, ensuring only changes are applied.
     */
    private function updateMealFoodRecords(Entity $entity)
    {
        // Check if food_type_id is dirty, return early if not changed
        if (!$entity->isDirty('food_type_id')) {
//            Log::debug('Food type not changed, skipping update');
            return;
        }
        $record_id = $entity->id;
        if(empty($record_id)){
//            Log::debug('Record id not found, skipping update');
            return;
        }

        $MealFoodRecordsTable = TableRegistry::getTableLocator()->get('Meal.MealFoodRecords');
        $newFoodTypeIds = array_map('intval', $entity->food_type_id['_ids'] ?? []);

        // Fetch existing food type records for this meal programme
        $existingFoodRecords = $MealFoodRecordsTable->find()
            ->select(['food_type_id'])
            ->where(['meal_programmes_id' => $record_id])
            ->extract('food_type_id')
            ->toArray();

        $existingFoodTypeIds = array_map('intval', $existingFoodRecords);

        // Determine which food types to add and which to remove
        $toAdd = array_diff($newFoodTypeIds, $existingFoodTypeIds);
        $toRemove = array_diff($existingFoodTypeIds, $newFoodTypeIds);

        // Remove only foods that are no longer selected
        if (!empty($toRemove)) {
            $MealFoodRecordsTable->deleteAll([
                'meal_programmes_id' => $record_id,
                'food_type_id IN' => $toRemove
            ]);
//            Log::debug('Removed food types: ' . implode(', ', $toRemove));
        }

        // Add new food types
        foreach ($toAdd as $foodTypeId) {
            $MealFood = $MealFoodRecordsTable->newEntity([
                'meal_programmes_id' => $record_id,
                'food_type_id' => $foodTypeId
            ]);
            $MealFoodRecordsTable->save($MealFood);
//            Log::debug('Added food type: ' . $foodTypeId);
        }
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $extra)
    {
//        //START: POCOR-6608
//        $InstitutionTable = TableRegistry::get('Institution.Institutions');
//        $MealInstitutionProgrammes = TableRegistry::get('Meal.MealInstitutionProgrammes');
//        $conditions1 = [
//            $MealInstitutionProgrammes->aliasField('meal_programme_id') => $extra['MealProgrammes']['id']
//        ];
//
//        $MealInstitutionProgrammes->deleteAll($conditions1);
//        $MealInstitutionProgrammesNew = TableRegistry::get('Meal.MealInstitutionProgrammes');
//
//        $areaIdsData = $entity['area_id']['_ids'];
//        $areaIdsData = $areaIdsData[0];//POCOR-6882
//        $institutionIds = $entity->institution_id;
//        $institutionIdsData = $institutionIds['_ids'];
//        $InstitutionStatusesTable = TableRegistry::getTableLocator()->get('Institution.Statuses');
//        $activeStatus = $InstitutionStatusesTable->getIdByCode('ACTIVE');
//        $where = [$InstitutionTable->aliasField('institution_status_id') => $activeStatus];
////        dd($institutionIds);
//        $institutionData = $InstitutionTable->find()
//            ->select([
//                'id' => $InstitutionTable->aliasField('id'),
//                'area_id' => $InstitutionTable->aliasField('area_id'),
//            ])
//            ->where($where)
//            ->toArray();
//        if($institutionIdsData[0] < 1 || $institutionIdsData[0] == ''){
//            foreach ($institutionData as $institution) {
//                try{
////                    dd($institution);
//                    $existData = $MealInstitutionProgrammesNew->find('all',['conditions'=>[
//                        'meal_programme_id' => $extra['MealProgrammes']['id'],
//                        'institution_id' => $institution->id,
//                        'area_id' => $institution->area_id,
//                    ]])->first();
//                    if(!$existData){
//                        $date = date('Y-m-d H:i:s');
//                        $data = $MealInstitutionProgrammesNew->newEntity([
//                            'meal_programme_id' => $extra['MealProgrammes']['id'],
//                            'institution_id' => $institution->id,
//                            'area_id' => $institution->area_id,
//                            'created_user_id' => $this->Auth->user('id'),
//                            'created' => $date
//                        ]);
//
//                        $saveData = $MealInstitutionProgrammesNew->save($data);
//                    }
//
//                }
//                catch (PDOException $e) {
//                    echo "<pre>";print_r($e);die;
//                }
//            }
//        }else{
//            foreach($institutionIdsData AS $key => $value)
//            {
//                try{
//                    $date = date('Y-m-d H:i:s');
//                    $mealDataOnEdit = [
//                        'meal_programme_id' =>  $extra['MealProgrammes']['id'],
//                        'institution_id' => $value,
//                        'area_id' => null,
//                        'created_user_id' => 2,
//                        'created' => $date
//                    ];
//
//                    $MealInstitutionProgrammes
//                        ->query()
//                        ->insert(['meal_programme_id', 'institution_id','area_id','created_user_id','created'])
//                        ->values($mealDataOnEdit)
//                        ->execute();
//                }
//                catch (PDOException $e) {
//                    echo "<pre>";print_r($e);die;
//                }
//            }
//        }
//
//        if($areaIdsData == -1){  //update $areaIdsData[0] to $areaIdsData
//            $MealInstitutionProgrammesNew->updateAll(
//                ['area_id' => $areaIdsData],
//                ['meal_programme_id' =>  $extra['MealProgrammes']['id']]
//            );
//
//        }else{
//            foreach($institutionIdsData AS $key => $value){
//                $where[$InstitutionTable->aliasField('id')] = $value;
//                $institutionData = $InstitutionTable->find()
//                    ->select([
//                        $InstitutionTable->aliasField('area_id'),
//                    ])
//                    ->where($where)
//                    ->first();
//                $MealInstitutionProgrammes->updateAll(
//                    ['area_id' => $institutionData->area_id],
//                    ['meal_programme_id' =>  $extra['MealProgrammes']['id'], 'institution_id'=> $value]
//                );
//            }
//        }
//        //END: POCOR-6608
//        $MealNutritions = TableRegistry::get('Meal.MealNutritionalRecords');
//        $conditions = [
//            $MealNutritions->aliasField('meal_programmes_id') => $extra['MealProgrammes']['id']
//        ];
//
//        $MealNutritions->deleteAll($conditions);
//        // $MealNutritions->newEntity(); //POCOR-7485
        // create records for new
        // delete related records for deleted
        // delete records for deleted
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $typeOptions = $this->MealNutritions->find('list')->toArray();
        $institutionsOptions = $this->Institutions->find('list')->toArray();
        $foodTable= TableRegistry::get('Meal.FoodTypes');//POCOR_7363
        $foodTypeOptions = $foodTable->find('list')->toArray();//POCOR-7363
        // $AreaLevelsOptions = $this->AreaLevels->find('list')->toArray(); //POCOR-6920
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
        //POCOR-7363 start
        $this->field('food_type_id', [
            'type' => 'chosenSelect',
            'attr' => [
                'label' => __('Food Type')
            ],
            'options' =>  $foodTypeOptions
        ]);
          //POCOR-7363 end
        $this->field('implementer');
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request){
        if ($action == 'add') {
            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->getQuery('period')));

            $attr['options'] = $periodOptions;

            $attr['default'] = $selectedPeriod;
        } else if ($action == 'edit') {

            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->getQuery('period')));

            $attr['options'] = $periodOptions;

            $attr['default'] = $selectedPeriod;
            $attr['type'] = 'readonly';

            // $entity = $attr['entity'];

            // $attr['type'] = 'readonly';
            // $attr['value'] = $entity->academic_period_id;
            // $attr['attr']['value'] = $entity->academic_period->name;
        }
        return $attr;
    }

    // public function onUpdateFieldType(Event $event, array $attr, $action, Request $request)
    public function onUpdateFieldType(Event $event, array $attr, $action)
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
        $serverRequest = $this->request;
        $MealTypes = TableRegistry::get('Meal.MealProgrammeTypes');
        $levelOptions = $MealTypes
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->toArray();

         $selectedLevel = !is_null($serverRequest->getAttribute('query')['level']) ? $serverRequest->getAttribute('query')['level'] : key($levelOptions);

         return compact('levelOptions', 'selectedLevel');
    }

    // public function onUpdateFieldTargeting(Event $event, array $attr, $action, Request $request)
    public function onUpdateFieldTargeting(Event $event, array $attr, $action)
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
        $serverRequest = new ServerRequest();
        $MealTrageting = TableRegistry::get('Meal.MealTargetTypes');
        $levelOptions = $MealTrageting
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->toArray();

         $selectedLevel = !is_null($serverRequest->getAttribute('query')['level']) ? $serverRequest->getAttribute('query')['level'] : key($levelOptions);

         return compact('levelOptions', 'selectedLevel');
    }

    // public function onUpdateFieldMealNutritions(Event $event, array $attr, $action, Request $request)
    public function onUpdateFieldMealNutritions(Event $event, array $attr, $action)
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
       //POCOR-7363 start
        $query->contain(['MealFoodRecords']);
         $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $arr =[];
                foreach($row->meal_food_records as $key=> $food){
                    $arr[$key] = ['id'=>$food['food_type_id']];
                }
                $row['food_type_id'] = $arr;

                return $row;
            });
        });

        //POCOR-7363 end
        $MealInstitutionProgrammes = TableRegistry::get('Meal.MealInstitutionProgrammes');
        $MealsProgrammeId = $this->paramsDecode($this->request->getParam('pass')[1]);
        $MealInstitutionProgrammesData = $MealInstitutionProgrammes
                            ->find()
                            ->contain(['Institutions'])
                            ->where([$MealInstitutionProgrammes->aliasField('meal_programme_id')=>$MealsProgrammeId['id']])
                            ->toArray();
        if(!empty($MealInstitutionProgrammesData)){
            $query->contain([
                'MealNutritions'
            ]);
            //START : POCOR-6608
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    $MealInstitutionProgrammes = TableRegistry::get('Meal.MealInstitutionProgrammes');
                    $MealInstitutionProgrammesData = $MealInstitutionProgrammes
                                ->find()
                                ->contain(['Institutions'])
                                ->where([$MealInstitutionProgrammes->aliasField('meal_programme_id')=>$row->id])
                                ->all();

                    $MealInstitutionProgrammes = TableRegistry::get('Meal.MealInstitutionProgrammes');
                    foreach($MealInstitutionProgrammesData AS $institutionData){
                        $institutionArr[] = $institutionData->institution_id;
                    }
                    if(!empty($institutionArr)){
                        $Institutions = TableRegistry::get('Institution.Institutions');
                        $InstitutionsResult = $Institutions
                            ->find()
                            ->where(['id IN' => $institutionArr])
                            ->all();
                        foreach($InstitutionsResult AS $InstitutionsResultData){
                            $InstitutionsData[] =  $InstitutionsResultData;
                        }
                        $row['institution_id'] = $InstitutionsResult;

                        $AreaResult = $MealInstitutionProgrammes
                            ->find()
                            ->select([$MealInstitutionProgrammes->aliasField('area_id')])
                            ->where(['meal_programme_id' => $row->id])
                            ->all();
                        if(!empty($AreaResult)){
                            foreach($AreaResult AS $AreaData){
                                $areaArr[] = $AreaData->area_id;
                            }
                            $Areas = TableRegistry::get('Area.Areas');
                            if($areaArr[0] == -1){
                                $AreasResult = $Areas
                                ->find()
                                ->all();
                            }else{
                                $AreasResult = $Areas
                                ->find()
                                ->where(['id IN' => $areaArr])
                                ->all();
                            }
                            foreach($AreasResult AS $AreaResultData){
                                $AreaDataVal[] =  $AreaResultData;
                            }
                            $row['area_id'] = $AreaDataVal;
                        }
                    }
                    return $row ;
                });
            });
        }else{
            // echo "Hell";die;
            $query->contain([
                'MealNutritions'
            ]);
            return $query ;
        }

        //END : POCOR-6608
    }

    public function viewAfterAction(Event $event, Entity $entity) {

        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {   $this->fields['id']['type'] = 'hidden';
        $this->setupFields($entity);
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
        $this->field('area_administrative_id', [
            'attr' => [
                'label' => __('Area Educationas')
            ],
            'visible' => ['index' => false, 'view' => true, 'edit' => false, 'add' => true]
        ]);
        $this->field('area_id',
            ['attr' => ['entity' => $entity],
                'entity' => $entity,
                'type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => false]);
        $this->field('institution_id', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true],
            'attr' => ['entity' => $entity],
        ]);
        $this->field('type',['select' => false]);
        $this->field('meal_nutritions', [
            'type' => 'chosenSelect',
            'attr' => [
                'label' => __('Nutritional Content')
            ],
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        //POCOR-7363 start
        $this->field('food_type_id', [
            'type' => 'chosenSelect',
            'attr' => [
                'label' => __('Food Type')
            ],
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]

        ]);
        //POCOR-7363 end
        $this->field('implementer');

    }

    public function getNutritionalOptions()
    {
        $serverRequest = new ServerRequest();
        $MealNutritions = TableRegistry::get('Meal.MealNutritions');
        $levelOptions = $MealNutritions
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->toArray();

         $selectedLevel = !is_null($serverRequest->getAttribute('query')['level']) ? $serverRequest->getAttribute('query')['level'] : key($levelOptions);

         return compact('levelOptions', 'selectedLevel');
    }

    // public function onUpdateFieldImplementer(Event $event, array $attr, $action, Request $request)
    public function onUpdateFieldImplementer(Event $event, array $attr, $action)
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
        $serverRequest = new ServerRequest();
        $MealImplementers = TableRegistry::get('Meal.MealImplementers');
        $levelOptions = $MealImplementers
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->toArray();

         $selectedLevel = !is_null($serverRequest->getAttribute('query')['level']) ? $serverRequest->getAttribute('query')['level'] : key($levelOptions);

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
                [$MealInstitutionProgrammes->getAlias() => $MealInstitutionProgrammes->getTable()], [
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
        // return $entity->area_id;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'amount':
                return __('Cost');
            case 'name':
                return __('Name');
            case 'start_date':
                return __('Start Date');
            case 'end_date':
                return __('End Date');
            case 'modified_user_id':
                return __('Modified By');
             case 'modified':
                return __('Modified');
             case 'created_user_id':
                return __('Created By');
             case 'created':
                return __('Created On');
             case 'code':
                return __('Code');
            case 'academic_period_id':
                return __('Academic Period');
            case 'targeting':
                return __('Target');
            case 'institution_id':
                return __('Institutions');
            case 'implementer':
                return __('Implementer');
            case 'type':
                return __('Meal Programme Types');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    /*
    *Get the list of area field to show in view and edit page
    * @auther Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return array
    * ticket POCOR-6608
    */

    public function onGetAreaAdministrativeId(Event $event, Entity $entity)
    {
        $MealInstitutionProgrammes = TableRegistry::get('Meal.MealInstitutionProgrammes');
        $result = $MealInstitutionProgrammes
            ->find()
            ->select([$MealInstitutionProgrammes->aliasField('area_id')])
            ->where(['meal_programme_id' => $entity->id])
            ->all();

        foreach($result AS $AreaData){
            $areaArr[] = $AreaData->area_id;
        }
        $Areas = TableRegistry::get('Area.Areas');
        if(!empty($areaArr)){
            $AreasResult = $Areas
            ->find('list')
            ->where(['id IN' => $areaArr])
            ->toArray();
            foreach($AreasResult AS $AreaResultData){
                $AreaDataVal[] =  $AreaResultData;
            }
        }
        return (!empty($AreaDataVal))? implode(', ', $AreaDataVal): 'All area';
    }

    public function onGetInstitutionId(Event $event, Entity $entity)
    {
        // if ($entity->institution) {
        //     return $entity->institution->code_name;
        // } else {
        //     return __('Private Candidate');
        // }

        // START: POCOR-6608
        $MealInstitutionProgrammes = TableRegistry::get('Meal.MealInstitutionProgrammes');
        $result = $MealInstitutionProgrammes
            ->find()
            ->select([$MealInstitutionProgrammes->aliasField('institution_id')])
            ->where(['meal_programme_id' => $entity->id])
            ->all();
        foreach($result AS $institutionData){
            $institutionArr[] = $institutionData->institution_id;
        }
        if(!empty($institutionArr)){
            $Institutions = TableRegistry::get('Institution.Institutions');
            $InstitutionsResult = $Institutions
                ->find('list')
                ->where(['id IN' => $institutionArr])
                ->toArray();
            foreach($InstitutionsResult AS $InstitutionsResultData){
                $InstitutionsData[] =  $InstitutionsResultData;
            }
        }
        return (!empty($InstitutionsData))? implode(', ', $InstitutionsData): ' ';
        // END: POCOR-6608
    }

    /*
    * Function is get area_level_id
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return $attr
    * @ticket POCOR-6920
    */

    // public function onUpdateFieldAreaLevelId(Event $event, array $attr, $action, Request $request)
    // {
    //     $attr['onChangeReload'] = true;
    //     $areaLevelId = isset($request->data) ? $request->data['MealProgrammes']['area_level_id']['_ids'] : 0;
    //     return $attr;
    // }
    /**
     * Handles updating the area list and ensures no child areas are listed if a parent area is already selected.
     */
    public function onUpdateFieldAreaId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $MealsProgrammeId = null;
        if ($action == 'edit') {

            $entity = $attr['entity'];
            $MealsProgrammeId = $entity->id;
        }
        $areaId = $this->getSelectedAreasIds($request, $MealsProgrammeId);
//        $selectedAreas = $this->getSelectedAreas($request);

        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $areaOptionsQuery = $Areas->find('list', [
            'keyField' => 'id',
            'valueField' => 'code_name'
        ])->order([$Areas->aliasField('order')]);

        if ($action === 'add' || $action === 'edit') {
            $areaOptionsList = $areaOptionsQuery->toArray();
            $filteredOptions = $this->removeChildAreasIfParentSelected($areaId, $areaOptionsList);
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = true;
            $attr['onChangeReload'] = true;
            $attr['options'] = $filteredOptions;
            $attr['value'] = $areaId;
            $this->request = $this->request->withData('MealProgrammes.area_id._ids', $areaId);
        } else {
            $attr['type'] = 'hidden';
        }

        return $attr;
    }


    // public function onUpdateFieldAreaId(Event $event, array $attr, $action, Request $request)
    /**
     * Handles updating the institution list based on selected areas.
     */
    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, ServerRequest $request)
    {

        $request = $this->request;
        $MealsProgrammeId = null;
        if ($action == 'edit') {
            $entity = $attr['entity'];
            $MealsProgrammeId = $entity->id;
        }
        $selectedAreaIds = $this->getSelectedAreas($request);
//        dd($request);

        $institutionOptions = $this->getInstitutionOptions($selectedAreaIds);
        $selectedInstitutionIds = $this->getSelectedInstitutionsIds($request, $MealsProgrammeId, $institutionOptions);

        // Set attributes for select field
        $attr['type'] = 'chosenSelect';
        $attr['onChangeReload'] = true;
        $attr['attr']['multiple'] = true;
        $attr['options'] = $institutionOptions;
        $attr['attr']['required'] = true;
        $attr['value'] = $selectedInstitutionIds;
        $attr['attr']['value'] = $selectedInstitutionIds;

        return $attr;
    }

    /**
     * Fetches available institutions based on selected areas, including all descendant areas.
     */
    private function getInstitutionOptions(array $areaIdArray): array
    {

        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');

        // Get all descendant areas
        $allAreas = [];
        $this->getDescendantAreas($areaIdArray, $Areas, $allAreas);
//        dd([$areaIdArray, $allAreas]);
        $allAreas = array_merge($areaIdArray, $allAreas);
//        dd($allAreas);
        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $InstitutionStatusesTable = TableRegistry::getTableLocator()->get('Institution.Statuses');
        $activeStatus = $InstitutionStatusesTable->getIdByCode('ACTIVE');
        $conditions = [
            $InstitutionsTable->aliasField('institution_status_id') => $activeStatus,
            $InstitutionsTable->aliasField('classification') => 1
        ];
        if (!empty($allAreas)) {
            $conditions[$InstitutionsTable->aliasField('area_id IN')] = array_filter($allAreas, fn($id) => $id != -1);
        }else{
            $conditions[$InstitutionsTable->aliasField('area_id')] = -1;
        }

        $institutionQuery = $InstitutionsTable->find('list', [
            'keyField' => 'id',
            'valueField' => 'code_name'
        ])
            ->where($conditions);
        $options = $institutionQuery->order([
            $InstitutionsTable->aliasField('code') => 'ASC',
            $InstitutionsTable->aliasField('name') => 'ASC'
        ])->toArray();
        if(count($options) > 0){
            $options = [-1 => __('All Institutions')] + $options;
        }
        return $options;
    }

    /**
     * Retrieves area IDs from request data.
     */
    private function getSelectedAreasIds(ServerRequest $request, $MealsProgrammeId = null): array
    {
        $areaIds = $request->getData('MealProgrammes.area_id._ids') ?? [];
        if(empty($areaIds)){
            $areaIds = [];
        }
        // Fetch present area IDs if editing

        if ($MealsProgrammeId) {
            $presentAreaIds = [];
            $MealInstitutionProgrammes = TableRegistry::getTableLocator()->get('Meal.MealInstitutionProgrammes');
            $presentAreaIds = $MealInstitutionProgrammes->find()
                ->select(['area_id'])
                ->distinct(['area_id'])
                ->where(['meal_programme_id' => $MealsProgrammeId])
                ->extract('area_id')
                ->toArray();
            $areaIds = array_merge($areaIds, $presentAreaIds);
        }
        if (empty($areaIds)) {
            return [];
        }
        return array_unique((array) $areaIds);
    }


    /**
     * Retrieves selected institutions for multi-select.
     */
    private function getSelectedInstitutionsIds(ServerRequest $request, $MealsProgrammeId = null, $institutionOptions = []): array
    {
        $institutionIds = $request->getData('MealProgrammes.institution_id._ids') ?? [];

        if(empty($institutionIds)){
            $institutionIds = [];
        }
        // Fetch present institution IDs if editing
        if ($MealsProgrammeId) {
            $MealInstitutionProgrammes = TableRegistry::getTableLocator()->get('Meal.MealInstitutionProgrammes');
            $where = ['meal_programme_id' => $MealsProgrammeId];
            $presentInstitutionIds = $MealInstitutionProgrammes->find()
                ->select(['institution_id'])
                ->distinct(['area_id'])
                ->where($where)
                ->extract('institution_id')
                ->toArray();
            $institutionIds = array_merge($institutionIds, $presentInstitutionIds);
//            dd(array_keys($institutionOptions));
            $institutionIds = array_intersect($institutionIds, array_keys($institutionOptions));


        }
        if (empty($institutionIds)) {
            return [];
        }
        if(in_array("-1", $institutionIds)){
            $institutionIds = array_keys($institutionOptions);
            unset($institutionIds[array_search("-1", $institutionIds)]);
        }
//        dd($institutionIds);
        return array_unique((array) $institutionIds);
    }

  /**
     * Retrieves selected areas for multi-select.
     */
    private function getSelectedAreas(ServerRequest $request)
    {
        $areaIds = $request->getData('MealProgrammes.area_id._ids') ?? [];
        $hasStrings = count(array_filter($areaIds, 'is_string')) > 0;

        if ($hasStrings) {
            // Step 1: Keep only string values
            $filtered = array_filter($areaIds, 'is_string');

            // Step 2: Convert strings to integers
            $areaIds = array_map('intval', $filtered);
        }

        return is_string($areaIds) ? explode(',', $areaIds) : array_unique((array) $areaIds);
    }

    /**
     * Recursively removes child areas if their parent is already selected.
     */
    private function removeChildAreasIfParentSelected(array $selectedAreas, array $areaOptions)
    {
        if (empty($selectedAreas)) {
            return $areaOptions;
        }

        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');

        // Get all descendant areas recursively
        $allChildAreas = [];
        $this->getDescendantAreas($selectedAreas, $Areas, $allChildAreas);

        return array_diff_key($areaOptions, array_flip($allChildAreas));
    }

    /**
     * Recursively fetches all descendant areas.
     */
    private function getDescendantAreas(array $parentIds, $Areas, array &$allChildAreas)
    {
        if(empty($parentIds)) {
            $allChildAreas = [];
            return;
        }

        $childAreas = $Areas->find()
            ->select(['id'])
            ->where(['parent_id IN' => $parentIds])
            ->extract('id')
            ->toArray();

        if (!empty($childAreas)) {
            $allChildAreas = array_merge($allChildAreas, $childAreas);
            $this->getDescendantAreas($childAreas, $Areas, $allChildAreas);

        }
    }
    /*
    * Get the list Meals Programmes
    * @auther Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return array
    * ticket POCOR-6609
    */

    public function getMealInstitutionProgrammes($options)
     {
        $MealInstitutionProgrammes = TableRegistry::get('Meal.MealInstitutionProgrammes');
        $MealProgramme = TableRegistry::get('Meal.MealProgrammes');
        $list = $MealProgramme
             ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->innerJoin(
                [$MealInstitutionProgrammes->getAlias() => $MealInstitutionProgrammes->getTable()], [
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

    /*
    * Delete data from `institution_meal_programmes` which is related to `meal_programmes` table
    * @auther Anubhav Jain <anubhav.jain@mail.valuecoders.com>
    * ticket POCOR-6681
    */
    public function deleteAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $MealInstitutionProgrammes = TableRegistry::get('Institution.InstitutionDistributions');
        $InstitutionProgrammes = $MealInstitutionProgrammes
                ->find('all')->select(['id'])
                ->where([
                    $MealInstitutionProgrammes->aliasField('meal_programmes_id') => $entity->id
                ])->toArray();
        if(!empty($InstitutionProgrammes)){
            foreach ($InstitutionProgrammes as $key => $Programmes) {
                $MealInstitutionProgrammes->delete($Programmes);
            }
        }
        //POCOR-7363 start
        $MealFoodTable = TableRegistry::get('Meal.MealFoodRecords');
        $MealFoodRecords =  $MealFoodTable
                ->find('all')->select(['id'])
                ->where([
                    $MealFoodTable->aliasField('meal_programmes_id') => $entity->id
                ])->toArray();
        if(!empty($MealFoodRecords)){
            foreach ($MealFoodRecords as $key => $mealFood) {
                $MealFoodTable->delete($mealFood);
            }
        }
    }
    public function onGetFoodTypeId(Event $event, Entity $entity)
    {
        $table=TableRegistry::get('Meal.FoodTypes');
        $obj = [];
        if ($entity->has('food_type_id')) {

            foreach ($entity->food_type_id as $role) {
               $res= $table->find('list')->where(['id'=>$role['id']])->first();
               $obj[] = $res;
            }
        }

        $values = !empty($obj) ? implode(', ', $obj) : __('No Excluded Security Roles ');
        return $values;

    }//POCOR-7363 end

}

