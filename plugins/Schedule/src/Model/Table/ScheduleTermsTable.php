<?php
namespace Schedule\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class ScheduleTermsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_schedule_terms');
        parent::initialize($config);

        $this->belongsTo('Institutions', [
            'className' => 'Institution.Institutions'
        ]);

        $this->belongsTo('AcademicPeriods', [
            'className' => 'AcademicPeriod.AcademicPeriods'
        ]);

        $this->hasMany('Timetables', [
            'className' => 'Schedule.ScheduleTimetables', 
            'foreignKey' => 'institution_schedule_term_id', 
            'dependent' => true, 
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Schedule.Schedule');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->add('code', 'ruleUniqueCode', [
                'rule' => ['checkUniqueCode', null]
            ])
            ->add('start_date', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            ])
            ->add('end_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'start_date', true]
            ])
            ->add('end_date', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            ])
            ->add('end_date', 'overlapDates', [
                'rule' => function ($value, $context) {
                    $ScheduleTermsTable = $context['providers']['table'];
                    $institutionId = $context['data']['institution_id'];
                    $academicPeriodId = $context['data']['academic_period_id'];
                    $endDate = $context['data']['end_date'];
                    $startDate = $context['data']['start_date'];
                    $termIdCondition = '';
                    if(isset($context['data']['id']) && $context['data']['id'] > 0){
                        $termIdCondition = array('ScheduleTerms.id !=' => $context['data']['id']);
                    }
                    
                    $count = $ScheduleTermsTable 
                        ->find()
                        ->where([
                            $ScheduleTermsTable->aliasField('institution_id') => $institutionId,
                            $ScheduleTermsTable->aliasField('academic_period_id') => $academicPeriodId,                                                            
                            'OR' => [
                                [
                                    $ScheduleTermsTable->aliasField('start_date <= ') => $startDate,
                                    $ScheduleTermsTable->aliasField('end_date >= ') => $startDate,
                                ],
                                [
                                    $ScheduleTermsTable->aliasField('start_date <= ') => $endDate,
                                    $ScheduleTermsTable->aliasField('end_date >= ') => $endDate,
                                ],
                                [
                                    $ScheduleTermsTable->aliasField('start_date >= ') => $startDate,
                                    $ScheduleTermsTable->aliasField('end_date <= ') => $endDate,
                                ],
                                [
                                    $ScheduleTermsTable->aliasField('start_date <= ') => $startDate,
                                    $ScheduleTermsTable->aliasField('end_date >= ') => $endDate,
                                ],
                            ],
                            $termIdCondition
                        ])
                        ->count();

                    return $count == 0;
                }
            ]);

        return $validator;
    } 

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->order([$this->aliasField('start_date') => 'ASC']);

        if (array_key_exists('selectedAcademicPeriodOptions', $extra)) {
            $query->where([
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']  
            ]);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupField();

        // filter options
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $institutionId = $this->Session->read('Institution.Institutions.id');

        $requestQuery = $this->request->query;
        if (isset($requestQuery) && array_key_exists('period', $requestQuery)) {
            $selectedPeriodId = $requestQuery['period'];
        } else {
            $selectedPeriodId = $this->AcademicPeriods->getCurrent();
        }

        $extra['selectedAcademicPeriodOptions'] = $selectedPeriodId;
        $extra['elements']['control'] = [
            'name' => 'Schedule.Terms/controls',
            'data' => [
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriodOption'=> $extra['selectedAcademicPeriodOptions']
            ],
            'order' => 3
        ];
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupField($entity);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupField();
    }

    // OnUpdate Events
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $this->AcademicPeriods->getYearList();
            $attr['default'] = $this->AcademicPeriods->getCurrent();
            $attr['onChangeReload'] = true;
            return $attr;
        }
    }

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            return $this->updateDateRangeField('start_date', $attr, $request);
        }
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            return $this->updateDateRangeField('end_date', $attr, $request);
        }
    }

    // Misc
    private function updateDateRangeField($key, $attr, Request $request)
    {
        $requestData = $request->data;
        if (array_key_exists($this->alias(), $requestData) && array_key_exists('academic_period_id', $requestData[$this->alias()])) {
            $selectedPeriodId = $requestData[$this->alias()]['academic_period_id'];
        } else {
            $selectedPeriodId = $this->AcademicPeriods->getCurrent();
        }

        $selectedPeriod = $this->AcademicPeriods->get($selectedPeriodId);
        $attr['type'] = 'date';
        $attr['date_options']['startDate'] = $selectedPeriod->start_date->format('d-m-Y');
        $attr['date_options']['endDate'] = $selectedPeriod->end_date->format('d-m-Y');
        
        if (!array_key_exists($this->alias(), $requestData) || !array_key_exists($key, $requestData[$this->alias()])) {
            if ($selectedPeriodId != $this->AcademicPeriods->getCurrent()) {
                $attr['value'] = $selectedPeriod->start_date;
            } else {
                $attr['value'] = Time::now();
            }
        }

        return $attr;
    }

    private function setupField($entity = null)
    {
        $this->field('academic_period_id', [
            'entity' => $entity, 
            'visible' => ['index' => false, 'view' => true, 'add' => true, 'edit' => true]
        ]);
        $this->field('code');
        $this->field('name');
        //$this->field('start_date');
        //$this->field('end_date');
        $this->setFieldOrder(['academic_period_id', 'code', 'name', 'start_date', 'end_date']);
    }
}
