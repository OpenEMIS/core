<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class InstitutionMealStudentsTable extends ControllerActionTable
{
    private $studentId;

    public function initialize(array $config)
    {
        $this->table('institution_meal_students');

        parent::initialize($config);
        $this->belongsTo('MealBenefit', ['className' => 'Meal.MealBenefits', 'foreignKey' =>'meal_benefit_id']); 
        $this->belongsTo('MealProgrammes', ['className' => 'Meal.MealProgrammes', 'foreignKey' =>'meal_programmes_id']);
       
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->toggle('view', false);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);
    }

    
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $request = $this->request;

        //academic period filter
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

        $extra['selectedPeriod'] = $selectedPeriod;
        $data['periodOptions'] = $periodOptions;
        $data['selectedPeriod'] = $selectedPeriod;


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
        $this->field('institution_class_id',['visible' => false]);   
        $this->field('institution_id');   

        $this->field('meal_received_id',['visible' => false]);
        $this->field('comment',['visible' => false]);
        $this->setFieldOrder(['date','meal_programmes_id','meal_benefit_id','institution_id','paid']);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
             case 'date':
                return __('Day');
            case 'meal_benefit_id':
                return __('Benefit Type');
            case 'meal_programmes_id':
                return __('Programme');
           case 'paid':
                return __('Total paid');
            default:
               return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    { 
        $hasSearchKey = $this->request->session()->read($this->registryAlias().'.search.key');

        $conditions = [];

        if (!$hasSearchKey) {
            //filter
            if (array_key_exists('selectedPeriod', $extra)) {
                if ($extra['selectedPeriod']) {
                    $conditions[] = $this->aliasField('academic_period_id = ') . $extra['selectedPeriod'];
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

                        $conditions[] = $this->aliasField('date >= ') . '"'. $data['start_day'] . '"';
                        $conditions[] = $this->aliasField('date <= ') . '"'. $data['end_day'] . '"';
                    }

                }
            }
           
           $query->where([$conditions]);
        }
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
