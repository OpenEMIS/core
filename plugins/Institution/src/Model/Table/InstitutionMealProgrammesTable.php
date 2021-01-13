<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class InstitutionMealProgrammesTable extends ControllerActionTable
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
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('MealProgrammes', ['className' => 'Meal.MealProgrammes','foreignKey' => 'meal_programmes_id']);
        $this->belongsTo('MealStatus', ['className' => 'Meal.MealStatusTypes','foreignKey' => 'delivery_status_id']);

        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->MealProgrammes = TableRegistry::get('Meal.MealProgrammes');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {    

        $academicPeriodOptions = $this->AcademicPeriods->getYearList();


        $extra['selectedAcademicPeriodOptions'] = $this->getSelectedAcademicPeriod($this->request);

        $extra['elements']['control'] = [
            'name' => 'Institution.MealProgramme/controls',
            'data' => [
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriodOption'=> $extra['selectedAcademicPeriodOptions']
            ],
            'order' => 3
        ];
        
        
        


        $this->field('academic_period_id',['visible' => false]);   
        $this->field('meal_programmes_id');   
        $this->field('date_received');
        $this->field('quantity');
        $this->field('comment',['visible' => false]);
         $this->setFieldOrder(['meal_programmes_id','date_received','quantity','delivery_status']);
    }

     public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        /*$hasSearchKey = $this->request->session()->read($this->registryAlias().'.search.key');
         $conditions = [];

        if (!$hasSearchKey) {
            //filter
            if (array_key_exists('selectedPeriod', $extra)) {
                if ($extra['selectedPeriod']) {
                    $conditions[] = $this->aliasField('academic_period_id = ') . $extra['selectedPeriod'];
                }
            }

            $query->where([$conditions]);
        }*/
        if (array_key_exists('selectedAcademicPeriodOptions', $extra)) {
            $query->where([
                       
                        $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']
                    ], [], true); 
        }
    }

    public function BeforeAction(Event $event, ArrayObject $extra)
    {    
        $this->field('academic_period_id', ['select' => false]);
        $this->field('meal_programmes_id',['select' => false]);
        $this->field('delivery_status_id',['select' => false]);
        $this->field('date_received',['type' => 'date']);
        $this->field('comment');
         $this->setFieldOrder(['academic_period_id', 'meal_programmes_id','quantity','date_received','delivery_status', 'comment']);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
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

    public function onUpdateFieldMealProgrammesId(Event $event, array $attr, $action, $request)
    {
        list($levelOptions, $selectedLevel) = array_values($this->getNameOptions());
        $attr['options'] = $levelOptions;
        if ($action == 'add') {
            $attr['default'] = $selectedLevel;
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
            $attr['default'] = $selectedLevel;
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

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data){
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

    
}
