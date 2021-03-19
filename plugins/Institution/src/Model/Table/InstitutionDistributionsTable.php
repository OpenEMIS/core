<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Chronos\Date;
use Cake\I18n\Time;
use App\Model\Table\ControllerActionTable;

class InstitutionDistributionsTable extends ControllerActionTable
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    { 
        $this->table('institution_meal_programmes');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('MealProgrammes', ['className' => 'Meal.MealProgrammes','foreignKey' => 'meal_programmes_id']);
        $this->belongsTo('MealStatus', ['className' => 'Meal.MealStatusTypes','foreignKey' => 'delivery_status_id']);

        $this->addBehavior('AcademicPeriod.AcademicPeriod');

        $this->MealProgrammes = TableRegistry::get('Meal.MealProgrammes');
        
    }

    // public function validationDefault(Validator $validator)
    // {
    //    $validator = parent::validationDefault($validator);

    // }


    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {    
        
        $request = $this->request;

        //academic period filter
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

        $extra['selectedPeriod'] = $selectedPeriod;
        $data['periodOptions'] = $periodOptions;
        $data['selectedPeriod'] = $selectedPeriod;

        // meal programmes filter
        $levelOptions = $this->MealProgrammes->getMealProgrammesOptions();
    
        if ($levelOptions) {
            $levelOptions = array(-1 => __('-- Select Programmes Meal --')) + $levelOptions;
        }

        if ($request->query('level')) {
            $selectedLevel = $request->query('level');
        } else {
            $selectedLevel = -1;
        }

        $extra['selectedLevel'] = $selectedLevel;
        $data['levelOptions'] = $levelOptions;
        $data['selectedLevel'] = $selectedLevel;

        //week

        if ($selectedPeriod) {
            $programmeOptions = $this->getMealWeekOptions($selectedPeriod);


            $programmeOptions = array(-1 => __('-- Please Select week --')) + $programmeOptions;

            if ($request->query('programme')) {
                $selectedProgramme = $request->query('programme');
            } else {
                $selectedProgramme = -1;
            }


            $extra['selectedProgramme'] = $selectedProgramme;
            $extra['programmeOptions'] = $programmeOptions;
            $data['programmeOptions'] = $programmeOptions;
            $data['selectedProgramme'] = $selectedProgramme;
        }



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
        $this->setFieldOrder(['meal_programmes_id','date_received','quantity_received','delivery_status']);
    }

     public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    { 
        $hasSearchKey = $this->request->session()->read($this->registryAlias().'.search.key');
        $institutions = $this->request->session()->read('Institution.Institutions.id');

        $conditions = [];

        if (!$hasSearchKey) {
            //filter
            if (array_key_exists('selectedPeriod', $extra)) {
                if ($extra['selectedPeriod']) {
                    $conditions[] = $this->aliasField('academic_period_id = ') . $extra['selectedPeriod'];
                    $conditions[] = $this->aliasField('institution_id = ') . $institutions;
                }
            }

            if (array_key_exists('selectedLevel', $extra)) {
                if ($extra['selectedLevel']) {
                    $query->innerJoinWith('MealProgrammes');
                    $conditions[] = 'MealProgrammes.id = ' . $extra['selectedLevel'];
                }
            }

            if (array_key_exists('selectedProgramme', $extra)) {

  
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

    public function beforeAction(Event $event, ArrayObject $extra)
    { 
        $this->field('academic_period_id', ['select' => false]);
        $this->field('meal_programmes_id',['select' => false]);
        $this->field('delivery_status_id',['select' => false]);
        $this->field('date_received',['type' => 'date']);

        $this->field('comment',['type' => 'text']);
        $this->field('quantity_received');
         $this->setFieldOrder(['academic_period_id', 'meal_programmes_id','quantity_received','delivery_status_id','date_received', 'comment']);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

            $attr['options'] = $periodOptions;

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


    public function onUpdateFieldMealProgrammesId(Event $event, array $attr, $action, $request)
    {
        list($levelOptions, $selectedLevel) = array_values($this->getNameOptions());
        $attr['options'] = $levelOptions;
        if ($action == 'add') {
            $attr['default'] = $selectedLevel;
        }

        return $attr;
    }

    public function onUpdateFieldDateReceived(Event $event, array $attr, $action, $request){

        $institutionId = $this->Session->read('Institution.Institutions.id');
        $data = $request->data[$this->alias()];
        if($data['delivery_status_id'] == 4){
             $attr['type'] = 'hidden';          
             $attr['value'] = Null;          
        }


        return $attr;

    }

    public function getNameOptions()
    {
        $MealProgramme = TableRegistry::get('Meal.MealProgrammes');
        $levelOptions = $MealProgramme
        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
        ->toArray();

        $selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

        return compact('levelOptions', 'selectedLevel');
    }

    public function onUpdateFieldDeliveryStatusId(Event $event, array $attr, $action, $request)
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
        $MealStatus = TableRegistry::get('Meal.MealStatusTypes');
        $levelOptions = $MealStatus
        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
        ->toArray();

        $selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

        return compact('levelOptions', 'selectedLevel');
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $data){
        if($entity->delivery_status_id == 4){
             $this->updateAll(['date_received' => NULL],['id' => $entity->id]);
                 return;
        }
        $entity->institution_id = $this->request->session()->read('Institution.Institutions.id');
        $entity->date_received = date("Y-m-d H:i:s");
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

    
}
