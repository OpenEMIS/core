<?php
namespace Institution\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use App\Model\Traits\MessagesTrait;
use Cake\I18n\Date;

class InstitutionDistributionsTable extends ControllerActionTable
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('institution_meal_programmes');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('MealProgrammes', ['className' => 'Meal.MealProgrammes','foreignKey' => 'meal_programmes_id']);
        $this->belongsTo('MealStatus', ['className' => 'Meal.MealStatusTypes','foreignKey' => 'delivery_status_id']);
        // $this->belongsTo('MealRatings', ['className' => 'Meal.MealRatings', 'foreignKey' => 'meal_rating_id']);//POCOR-7363 // Commented for POCOR-7484
        $this->addBehavior('AcademicPeriod.AcademicPeriod');

        $this->MealProgrammes = TableRegistry::getTableLocator()->get('Meal.MealProgrammes');

            // POCOR-6153 start
            $this->addBehavior('Excel', [
            'excludes' => ['academic_period_id', 'institution_id', 'comment'],
            'pages' => ['index'],
            'autoFields' => false
            ]);
        // POCOR-6153 end

        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Distributions' =>['id']
            ]
        ]);

    }
    //START:POCOR-6681
    // public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
	// {
	// 	$this->setupFields($entity);
	// }

    // public function setupFields(Entity $entity)
	// {
	// 	$this->field('date_received', [
	// 		'type' => 'date',
	// 		'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true],
	// 		'attr' => ['required' => true], // to add red asterisk
	// 		'entity' => $entity,
	// 		'before' => 'comment'
	// 	]);
	// }
    //END:POCOR-6681

    public function validationDefault(Validator $validator): Validator
    {
        //START: POCOR-6681
        $validator
        ->requirePresence('academic_period_id')
        ->requirePresence('meal_programmes_id')
        ->requirePresence('quantity_received')
        ->requirePresence('delivery_status_id')
        ->requirePresence('date_received', 'create')->notEmpty('date_received');
        return $validator;
        //END: POCOR-6681
    }

    /*
    * To check validation entity before save
    * @auther Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return boolean
    * ticket POCOR-6681
    */
    // public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $data) {
    //     if ($entity->isNew()) {
    //         $MealProgrammesData = TableRegistry::getTableLocator()->get('Meal.MealProgrammes');
    //         $MealProgrammesResult = $MealProgrammesData
    //         ->find()
    //         ->select(['amount'])
    //         ->where(['id' => $entity->meal_programmes_id])
    //         ->first();
    //         if($entity->quantity_received > $MealProgrammesResult->amount){
    //         $this->Alert->error('Institution.InstitutionDistributions.quantity_received.genralerror', ['reset' => true]);
    //             return false;
    //         }
    //         $query = $this->find();
    //         $entityRecord = $query->where([
    //                 $this->aliasField('meal_programmes_id') => $entity->meal_programmes_id
    //             ])
    //         ->select([
    //             'quantity_received_sum' => $query->func()->sum($this->aliasField('quantity_received'))
    //         ])
    //         ->first()
    //         ;
    //         $total_sum = $entityRecord->quantity_received_sum + $entity->quantity_received;
    //         // echo "<pre>";print_r($total_sum);die;
    //         if($total_sum > $MealProgrammesResult->amount){
    //             $this->Alert->error('Institution.InstitutionDistributions.quantity_received_sum.genralerror', ['reset' => true]);
    //             return false;
    //         }
    //     }else{
    //         $MealProgrammesData = TableRegistry::getTableLocator()->get('Meal.MealProgrammes');
    //         $MealProgrammesResult = $MealProgrammesData
    //         ->find()
    //         ->select(['amount'])
    //         ->where(['id' => $entity->meal_programmes_id])
    //         ->first();
    //         if($entity->quantity_received > $MealProgrammesResult->amount){
    //             $this->Alert->error('Institution.InstitutionDistributions.quantity_received.genralerror', ['reset' => true]);
    //             return false;
    //         }
    //     }
    // }

    /*
    * To change default field name to the required field name
    * @auther Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return boolean
    * ticket POCOR-6681
    */
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'date_received':
                return __('Date');
            case 'academic_period_id':
                return __('Academic Period');
            case 'meal_rating_id':
                return __('Rating');
            case 'comment':
                return __('Comment');
            case 'meal_programmes_id':
                return __('Meal Programme');
            case 'quantity_received':
                return __('Quantity Received');
            case 'delivery_status_id':
                return __('Delivery Status');
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }



    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $request = $this->request;
        //academic period filter
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->getQuery('period')));

        $extra['selectedPeriod'] = $selectedPeriod;
        $data['periodOptions'] = $periodOptions;
        $data['selectedPeriod'] = $selectedPeriod;
        $session = $this->request->getSession();
        $institutionId = $this->getInstitutionID();
        $options['academid_period_id'] = $selectedPeriod;
        $options['institution_id'] = $institutionId;
        // meal programmes filter
        //START: POCOR-6609
        // $levelOptions = $this->MealProgrammes->getMealProgrammesOptions();
        $levelOptions = $this->MealProgrammes->getMealInstitutionProgrammes($options);
        //END: POCOR-6609

        if (!empty($levelOptions)) {
            $levelOptions = array(-1 => __('-- Select Programmes Meal --')) + $levelOptions;
        }else{
            $levelOptions = array(-1 => __('-- Select Programmes Meal --'));
        }

        if ($request->getQuery('level')) {
            $selectedLevel = $request->getQuery('level');
        } else {
            $selectedLevel = -1;
        }

        $extra['selectedLevel'] = $selectedLevel;
        $data['levelOptions'] = $levelOptions;
        $data['selectedLevel'] = $selectedLevel;

        //week
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        if ($selectedPeriod) {
            $programmeOptions = $this->getMealWeekOptions($selectedPeriod);


            $programmeOptions = array(-1 => __('-- Please Select week --')) + $programmeOptions;

            if ($request->getQuery('programme')) {
                $selectedProgramme = $request->getQuery('programme');
            } else {
                $selectedProgramme = -1;
            }


            $extra['selectedProgramme'] = $selectedProgramme;
            $extra['programmeOptions'] = $programmeOptions;
            $data['programmeOptions'] = $programmeOptions;
            $data['selectedProgramme'] = $selectedProgramme;
        }

        $data['encodedQueryString'] = $encodedQueryString;
        //build up the control filter
        $extra['elements']['control'] = [
            'name' => 'Institution.InstitutionsMealProgramme/controls',
            'data' => $data,
            'order' => 3
        ];


        $this->field('academic_period_id',['visible' => false]);
        $this->field('meal_programmes_id');
        $this->field('date_received');
        $this->field('quantity_received');
        $this->field('comment',['visible' => false]);
        $this->field('meal_rating_id',['visible' => false]);//POCOR-7363
        $this->setFieldOrder(['meal_programmes_id','date_received','quantity_received','delivery_status']);


        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Meals Distribution','Meals');
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

     public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $hasSearchKey = $this->request->getSession()->read($this->getRegistryAlias().'.search.key');
        $institutions = $this->getInstitutionID();

        $conditions = [];

        if (!$hasSearchKey) {
            //filter
            if (isset($extra['selectedPeriod'])) {
                if ($extra['selectedPeriod']) {
                    $conditions[] = $this->aliasField('academic_period_id = ') . $extra['selectedPeriod'];
                    $conditions[] = $this->aliasField('institution_id = ') . $institutions;
                }
            }

            if (isset($extra['selectedLevel'])) {
                if ($extra['selectedLevel']) {
                    $query->innerJoinWith('MealProgrammes');
                    $conditions[] = 'MealProgrammes.id = ' . $extra['selectedLevel'];
                }
            }

            if (isset($extra['selectedProgramme'])) {


                if ($extra['selectedProgramme'] > 0) {
                    $list = $this->AcademicPeriods->getMealWeeksForPeriod($extra['selectedPeriod']);
                    if (!empty($list)) {
                        $data = $list[$extra['selectedProgramme'] - 1];

                        $conditions[] = $this->aliasField('institution_id = ') . $institutions;
                        $conditions[] = $this->aliasField('date_received >= ') . '"'. $data['start_day'] . '"';
                        $conditions[] = $this->aliasField('date_received <= ') . '"'. $data['end_day'] . '"';
                    }

                }
            }

           $query->where([$conditions]);
     }
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['select' => false]);
        $this->field('meal_programmes_id',['select' => false]);
        $this->field('delivery_status_id',['select' => false]);
        $this->field('date_received',['type' => 'date']);

        $this->field('comment',['type' => 'text']);
        $this->field('quantity_received');
         $this->setFieldOrder(['academic_period_id', 'meal_programmes_id','quantity_received','delivery_status_id','date_received', 'comment']);
    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->getQuery('period')));
            $attr['options'] = $periodOptions;
            //START:POCOR:6609
            $attr['default'] = $selectedPeriod;
            //END:POCOR:6609

            $attr['onChangeReload'] = $selectedPeriod;
        } else if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->academic_period_id;
            $attr['attr']['value'] = $entity->academic_period->name;
            $attr['onChangeReload'] = 'changeShiftOption';
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

    public function getMealProgrammeOptions($querystringMeal)
    {
        $mealOptions = $this->MealProgrammes->getMealOptions($querystringMeal);

        if (!empty($querystringPeriod)) {
            $selectedMeal = $querystringPeriod;
        } else {
            $selectedMeal = $this->AcademicPeriods->getCurrent();
        }


        return compact('mealOptions', 'selectedMeal');
    }


    public function onUpdateFieldMealProgrammesId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $session = $this->request->getSession();
        $institutionId = $this->getInstitutionID();
        //POCOR-6434[START]
        // $institutionId = $request->data['InstitutionDistributions'];
        //POCOR-6434[END]
        if(!empty($institutionId)){
            $options['period'] = $request->getData()['InstitutionDistributions']['academic_period_id'];
            $options['level'] = $request->getQuery('level');
            $options['institution_id'] = $institutionId;
        }else{
            $options['period'] = $request->getQuery('period');
            $options['level'] = $request->getQuery('level');
            $options['institution_id'] = $institutionId;
        }
        // list($levelOptions, $selectedLevel) = array_values($this->getNameOptions($institutionId));
        list($levelOptions, $selectedLevel) = array_values($this->getNameOptions($options));
        $attr['options'] = $levelOptions;
        if ($action == 'add') {
            $attr['default'] = $selectedLevel;
        }
        return $attr;
    }

    public function onUpdateFieldDateReceived(EventInterface $event, array $attr, $action, ServerRequest $request){

        $institutionId = $this->getInstitutionID();
        $data = $request->getData[$this->getAlias()];
        //START:POCOR-6681 // Requirment change to show date received in all condition
        // if($data['delivery_status_id'] == 4){
        //      $attr['type'] = 'hidden';
        //      $attr['value'] = Null;
        // }
        //END:POCOR-6681

        return $attr;

    }

    public function getNameOptions($options)
    {

        $institutionId = $options['institution_id'];
        //START: POCOR-6609
        if(!isset($options['period'])){
            $academic_period_id = $this->AcademicPeriods->getCurrent();
        }else{
            $academic_period_id = $options['period'];
        }
        //END: POCOR-6609

        $MealInstitutionProgrammes = TableRegistry::getTableLocator()->get('Meal.MealInstitutionProgrammes');

        $MealProgramme = TableRegistry::getTableLocator()->get('Meal.MealProgrammes');
        $levelOptions = $MealProgramme
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->innerJoin(
                [$MealInstitutionProgrammes->getAlias() => $MealInstitutionProgrammes->getTable()], [
                    $MealProgramme->aliasField('id = ') . $MealInstitutionProgrammes->aliasField('meal_programme_id'),
                    $MealProgramme->aliasField('academic_period_id = ') . $academic_period_id
                ]
            )
            ->where([
                'OR' => [
                    [$MealInstitutionProgrammes->aliasField('institution_id') => $institutionId],
                    [$MealInstitutionProgrammes->aliasField('institution_id') => $options['institution_id']],
                    [$MealInstitutionProgrammes->aliasField('institution_id') => 0]
                ]
            ])
            ->toArray();

        $selectedLevel = !is_null($this->request->getQuery('level')) ? $this->request->getQuery('level') : key($levelOptions);

        return compact('levelOptions', 'selectedLevel');
    }

    public function onUpdateFieldDeliveryStatusId(EventInterface $event, array $attr, $action, $request)
    {

        list($levelOptions, $selectedLevel) = array_values($this->getDeliveryStatusOptions());
        $attr['options'] = $levelOptions;

        if ($action == 'add') {
            $attr['onChangeReload'] = $selectedLevel;
        }

        return $attr;
    }

    public function getDeliveryStatusOptions()
    {
        $MealStatus = TableRegistry::getTableLocator()->get('Meal.MealStatusTypes');
        $levelOptions = $MealStatus
        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
        ->toArray();

        $selectedLevel = !is_null($this->request->getQuery('level')) ? $this->request->getQuery('level') : key($levelOptions);

        return compact('levelOptions', 'selectedLevel');
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $data){
        if($entity->delivery_status_id == 4){
             $this->updateAll(['date_received' => date("Y-m-d H:i:s")],['id' => $entity->id]);
                 return;
        }
        $entity->institution_id = $this->getInstitutionID();
        $entity->date_received = date("Y-m-d H:i:s");
    }

    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';

        if ($this->action == 'index' || $this->action == 'view' || $this->action == 'edit') {
            if (!is_null($request->getQuery()) && array_key_exists('period', $request->getQuery())) {
                $selectedAcademicPeriod = $request->getQuery('period');
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }

        return $selectedAcademicPeriod;
    }

    public function getMealWeekOptions($selectedPeriod)
    {
        $list = $this->AcademicPeriods->getMealWeeksForPeriod($selectedPeriod);
         if (!empty($list)) {
                        foreach($list as $data){
                            $result[$data['id']] = $data['name'];
                        }
                    }
        return $result;
    }

    /*
    * To generate excel report
    * @auther Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return file
    * ticket POCOR-6681
    */
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query){
        $AcademicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $academicPeriodId =  ($this->request->getQuery('period')) ? $this->request->getQuery('period') : $AcademicPeriod->getCurrent();
        $session = $this->request->getSession();
        $institutionId  = $this->getInstitutionID();
        $MealInstitutionProgrammes = TableRegistry::getTableLocator()->get('Meal.MealInstitutionProgrammes');
        $query
        ->innerJoin(
            [$MealInstitutionProgrammes->getAlias() => $MealInstitutionProgrammes->getTable()], [
                $this->aliasField('meal_programmes_id = ') . $MealInstitutionProgrammes->aliasField('id'),
            ]
        )
        ->where([
            $this->aliasField('academic_period_id') => $academicPeriodId,
            $this->aliasField('institution_id') => $institutionId
        ]);
    }
    //POCOR-7363 start
    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
		$this->field('meal_rating_id',["type"=>"select"]);
        $this->setFieldOrder(['academic_period_id', 'meal_programmes_id','quantity_received','delivery_status_id','date_received', 'meal_rating_id','comment']);

    }
    //POCOR-7363 end
}
