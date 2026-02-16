<?php
namespace Schedule\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenTime; // POCOR-8985

class ScheduleTermsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_schedule_terms');
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
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['ScheduleTerms' =>['id']
            ]
        ]);
    }

    // POCOR-8985 start
     public function validationDefault(Validator $validator): Validator
     {
         $validator = parent::validationDefault($validator);
         $validator->setProvider('custom', $this);
         $validator
             ->notEmptyString('name')
             ->notEmptyString('code')
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
    // POCOR-8985 end

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->order([$this->aliasField('start_date') => 'ASC']);

        if (isset($extra['selectedAcademicPeriodOptions'])) {
            $query->where([
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodOptions']
            ]);
        }
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setupField();

        // filter options
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $institutionId = $this->getInstitutionID();

        $requestQuery = $this->request->getQuery();
        if (isset($requestQuery) && isset($requestQuery['period'])) {
            $selectedPeriodId = $requestQuery['period'];
        } else {
            $selectedPeriodId = $this->AcademicPeriods->getCurrent();
        }
        $encodeQueryString = $this->request->getParam('pass')[1];
        $extra['selectedAcademicPeriodOptions'] = $selectedPeriodId;
        $extra['elements']['control'] = [
            'name' => 'Schedule.Terms/controls',
            'data' => [
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriodOption'=> $extra['selectedAcademicPeriodOptions'],
                'encodeQueryString' => $encodeQueryString
            ],
            'order' => 3
        ];

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Terms','Schedules');
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

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupField($entity);
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setupField();
    }

    // OnUpdate Events
    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
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

    public function onUpdateFieldStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            return $this->updateDateRangeField('start_date', $attr, $request);
        }
    }

    public function onUpdateFieldEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        if ($action == 'add' || $action == 'edit') {
            return $this->updateDateRangeField('end_date', $attr, $request);
        }
    }

    // Misc
    private function updateDateRangeField($key, $attr, ServerRequest $request)
    {
        $requestData = $request->getData();
        // POCOR-8985 start
        $alias = $this->getAlias();
        $currentAcademicPeriodId = $this->AcademicPeriods->getCurrent();
        $selectedPeriodId = $requestData[$alias]['academic_period_id'] ?? $currentAcademicPeriodId;
        // POCOR-8985 end
        $selectedPeriod = $this->AcademicPeriods->get($selectedPeriodId);
        $attr['type'] = 'date';
        $attr['date_options']['startDate'] = $selectedPeriod->start_date->format('d-m-Y');
        $attr['date_options']['endDate'] = $selectedPeriod->end_date->format('d-m-Y');
        // POCOR-8985 start
        $attr['date_options']['todayBtn'] = false;
        if (!isset($requestData[$alias]) || !isset($requestData[$alias][$key])) {
            if ($selectedPeriodId != $this->AcademicPeriods->getCurrent()) {
                $attr['value'] = $selectedPeriod->start_date;
            } else {
                $attr['value'] = FrozenTime::now();
            }
        }
        // POCOR-8985 end

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
        // POCOR-8985 start
        $this->field('start_date');
        $this->field('end_date');
        // POCOR-8985 end
        $this->setFieldOrder(['academic_period_id', 'code', 'name', 'start_date', 'end_date']);
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'code':
                return __('Code');
            case 'name':
                return __('Name');
            case 'start_date':
                return __('Start Date');
            case 'academic_period_id':
                return __('Academic Period');
            case 'end_date':
                return __('End Date');
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
}
