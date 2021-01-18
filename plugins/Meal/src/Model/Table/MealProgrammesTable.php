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
        $this->belongsTo('MealTargetTypes', ['className' => 'Meal.MealTargetTypes','foreignKey' => 'trageting']);
        $this->belongsTo('MealImplementers', ['className' => 'Meal.MealImplementers','foreignKey' => 'implementer']);
        $this->belongsTo('MealNutritions', ['className' => 'Meal.MealNutritions','foreignKey' => 'nutritional_content']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentMeals' => ['index', 'view']
        ]);

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

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        

        $this->field('academic_period_id',['visible' => false]);
        $this->field('code');
        $this->field('name');
        $this->field('type');
        $this->field('trageting');
        $this->field('start_date');
        $this->field('end_date');
        $this->field('amount');
        $this->field('nutritional_content',['visible' => false]);
        $this->field('implementer',['visible' => false]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {    
        $this->field('academic_period_id',['select' => false]);
        $this->field('code');
        $this->field('name');
        $this->field('type',['select' => false]);
        $this->field('trageting');
        $this->field('start_date');
        $this->field('end_date');
        $this->field('amount');
        $this->field('nutritional_content',['chosenSelect' => false]);
        $this->field('implementer');
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

    public function onUpdateFieldTrageting(Event $event, array $attr, $action, Request $request)
    {
        list($levelOptions, $selectedLevel) = array_values($this->getTragetingOptions());
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

    public function getTragetingOptions()
    {
        $MealTrageting = TableRegistry::get('Meal.MealTargetTypes');
        $levelOptions = $MealTrageting
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->toArray();
           
         $selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

         return compact('levelOptions', 'selectedLevel');
    }

    public function onUpdateFieldNutritionalContent(Event $event, array $attr, $action, Request $request)
    {
        list($levelOptions, $selectedLevel) = array_values($this->getNutritionalOptions());
        $attr['options'] = $levelOptions;
        if ($action == 'add') {
            $attr['default'] = $selectedLevel;
        }

        return $attr;
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
    
}
