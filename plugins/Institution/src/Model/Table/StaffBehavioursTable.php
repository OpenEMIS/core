<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\I18n\Date;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

class StaffBehavioursTable extends ControllerActionTable
{
	use OptionsTrait;

	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
		$this->belongsTo('Staff', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('StaffBehaviourCategories', ['className' => 'Staff.StaffBehaviourCategories']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('BehaviourClassifications', ['className' => 'Student.BehaviourClassifications', 'foreignKey' => 'behaviour_classification_id']);

		$this->addBehavior('AcademicPeriod.Period');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
		$this->addBehavior('Institution.Case');

		$this->setDeleteStrategy('restrict');
	}

	public function implementedEvents()
    {
        $events = parent::implementedEvents();
		$events['InstitutionCase.onSetCustomCaseTitle'] = 'onSetCustomCaseTitle';
		$events['InstitutionCase.onSetCustomCaseSummary'] = 'onSetCustomCaseSummary';
        return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
        	->add('date_of_behaviour', [
				'ruleInAcademicPeriod' => [
					'rule' => ['inAcademicPeriod', 'academic_period_id', []],
					'provider' => 'table',
					'on' => function ($context) {
						if (array_key_exists('academic_period_id', $context['data'])) {
							return !empty($context['data']['academic_period_id']);
						}
				    }
				]
			])
        ;
    }

	public function onGetOpenemisNo(Event $event, Entity $entity)
	{
		if ($this->action == 'view') {
			return $event->subject()->Html->link($entity->staff->openemis_no , [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'StaffUser',
				'view',
				$this->paramsEncode(['id' => $entity->staff->id])
			]);
		} else {
			return $entity->staff->openemis_no;
		}
	}

	public function beforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('openemis_no');
		$this->field('staff_id');

		if ($this->action == 'view') {
			$this->setFieldOrder(['openemis_no', 'staff_id', 'date_of_behaviour', 'time_of_behaviour', 'staff_behaviour_category_id', 'behaviour_classification_id']);
		}
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('academic_period_id', ['visible' => false]);
		$this->field('description', ['visible' => false]);
		$this->field('action', ['visible' => false]);
		$this->field('time_of_behaviour', ['visible' => false]);

		$this->setFieldOrder(['openemis_no', 'staff_id', 'date_of_behaviour', 'staff_behaviour_category_id', 'behaviour_classification_id']);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$extra['elements']['controls'] = ['name' => 'Institution.Behaviours/controls', 'data' => [], 'options' => [], 'order' => 1];

		// Setup period options
		// $periodOptions = ['0' => __('All Periods')];
		$periodOptions = $this->AcademicPeriods->getList();
		if (empty($this->request->query['academic_period_id'])) {
			$this->request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
		}

		$Staff = TableRegistry::get('Institution.Staff');
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$selectedPeriod = $this->queryString('academic_period_id', $periodOptions);
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage('general.noStaff'),
			'callable' => function($id) use ($Staff, $institutionId) {
				return $Staff
					->findByInstitutionId($institutionId)
					->find('academicPeriod', ['academic_period_id' => $id])
					->count();
			}
		]);

		if (!empty($selectedPeriod)) {
			$query->find('inPeriod', ['field' => 'date_of_behaviour', 'academic_period_id' => $selectedPeriod]);
		}

		$this->controller->set(compact('periodOptions'));

		// will need to check for search by name: AdvancedNameSearchBehavior
	}

	public function addBeforeAction(Event $event, ArrayObject $extra)
	{
		if(!empty($this->request->data[$this->alias()]['academic_period_id'])) {
			$academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
			$academicPeriod = $this->AcademicPeriods->get($academicPeriodId);
			$this->request->data[$this->alias()]['academic_start_date'] = $academicPeriod->start_date;
			$this->request->data[$this->alias()]['academic_end_date'] = $academicPeriod->end_date;
		}

		$this->field('date_of_behaviour');
		$this->field('academic_period_id');
		$this->field('staff_behaviour_category_id', ['type' => 'select']);
		$this->field('behaviour_classification_id', ['type' => 'select']);
		$this->setFieldOrder(['academic_period_id', 'staff_id', 'staff_behaviour_category_id', 'behaviour_classification_id', 'date_of_behaviour', 'time_of_behaviour']);
	}

	public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$query->contain(['Staff', 'StaffBehaviourCategories', 'BehaviourClassifications']);
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
	{
		$this->fields['staff_id']['attr']['value'] = $entity->staff->name_with_id;
		$this->field('date_of_behaviour');
		$this->field('academic_period_id');
		$this->field('staff_behaviour_category_id', ['entity' => $entity]);
		$this->field('behaviour_classification_id', ['entity' => $entity]);

		$this->setFieldOrder(['academic_period_id', 'openemis_no', 'staff_id', 'date_of_behaviour', 'time_of_behaviour', 'staff_behaviour_category_id', 'behaviour_classification_id']);
	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
	{
		$entity->showDeletedValueAs = $entity->description;
	}

	public function onUpdateFieldOpenemisNo(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'edit' || $action == 'add') {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldDateOfBehaviour(Event $event, array $attr, $action, Request $request)
	{
		$startDate = '';
		$endDate = '';
		if($action == 'add' && !empty($request->data[$this->alias()]['academic_period_id'])) {
			$todayDate = new Date();
			$inputDate = Date::createfromformat('d-m-Y',$request->data[$this->alias()]['date_of_behaviour']); //string to date object

			$startDate = $request->data[$this->alias()]['academic_start_date'];
			$endDate = $request->data[$this->alias()]['academic_end_date'];

			// if today date is not within selected academic period, default date will be start of the year
			if ($inputDate <= $startDate || $inputDate >= $endDate) {
				$attr['value'] = $startDate->format('d-m-Y');

				// if today date is within selected academic period, default date will be current date
				if ($startDate <= $todayDate && $todayDate <= $endDate) {
					$attr['value'] = $todayDate->format('d-m-Y');
				}
			}

			$attr['default_date'] = false;
			$attr['date_options'] = ['startDate' => $startDate->format('d-m-Y'), 'endDate' => $endDate->format('d-m-Y')];
		} else if ($action == 'edit' && !empty($this->paramsPass(0))) {
			// restrict the date options only on the selected academic period
			$entityId = $this->paramsDecode($this->paramsPass(0))['id'];
			$academicPeriodId = $this->get($entityId)->academic_period_id;

			$startDate = $this->AcademicPeriods->get($academicPeriodId)->start_date;
			$endDate = $this->AcademicPeriods->get($academicPeriodId)->end_date;

			$attr['date_options'] = ['startDate' => $startDate->format('d-m-Y'), 'endDate' => $endDate->format('d-m-Y')];
		}

		return $attr;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
	{
		$institutionId = $this->Session->read('Institution.Institutions.id');

		if ($action == 'add') {
			$periodOptions = $this->AcademicPeriods->getList(['isEditable'=>true]);
			$selectedPeriod = 0;
			if ($request->is(['post', 'put'])) {
				$selectedPeriod = $request->data($this->aliasField('academic_period_id'));
			}

			$attr['options'] = $periodOptions;
			$attr['onChangeReload'] = 'changePeriod';

			//set start and end dates for date of behaviour based on chosen academic period
			if (!empty($selectedPeriod)) {
				$periodEntity = $this->AcademicPeriods->get($selectedPeriod);
				$dateOptions = [
					'startDate' => $periodEntity->start_date->format('d-m-Y'),
					'endDate' => $periodEntity->end_date->format('d-m-Y')
				];
				$this->fields['date_of_behaviour']['date_options'] = $dateOptions;
			}
		} else if ($action == 'edit' && !empty($this->paramsPass(0))) {
			$entityId = $this->paramsDecode($this->paramsPass(0))['id'];
			$academicPeriodId = $this->get($entityId)->academic_period_id;

			$attr['type'] = 'readonly';
			$attr['value'] = $academicPeriodId;
			$attr['attr']['value'] = $this->AcademicPeriods->get($academicPeriodId)->name;
		}
		return $attr;
	}

	public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add') {
			$staffOptions = [];

			$selectedPeriod = 0;
			if ($request->is(['post', 'put'])) {
				$selectedPeriod = $request->data($this->aliasField('academic_period_id'));
			}

			if (!empty($selectedPeriod)) {
				$institutionId = $this->Session->read('Institution.Institutions.id');
				$Staff = TableRegistry::get('Institution.Staff');
				$staffOptions = $Staff
				->find('list', ['keyField' => 'staff_id', 'valueField' => 'name'])
				->matching('Users')
				->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
				->where([$Staff->aliasField('institution_id') => $institutionId])
				->toArray();
			}

			$attr['options'] = $staffOptions;
		} else if ($action == 'edit') {
			$attr['type'] = 'readonly';
		}
		return $attr;
	}

	public function onUpdateFieldStaffBehaviourCategoryId(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'edit') {
			$entity = $attr['entity'];

			$attr['type'] = 'readonly';
			$attr['value'] = $entity->staff_behaviour_category_id;
			$attr['attr']['value'] = $entity->staff_behaviour_category->name;
		}

		return $attr;
	}

	public function onUpdateFieldBehaviourClassificationId(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'edit') {
			$entity = $attr['entity'];

			$attr['type'] = 'readonly';
			$attr['value'] = $entity->behaviour_classification_id;
			$attr['attr']['value'] = $entity->behaviour_classification->name;
		}

		return $attr;
	}

	public function onSetCustomCaseTitle(Event $event, Entity $entity)
    {
    	$recordEntity = $this->get($entity->id, [
    		'contain' => ['Staff', 'StaffBehaviourCategories', 'Institutions', 'BehaviourClassifications']
    	]);
    	$title = '';
    	$title .= $recordEntity->staff->name.' '.__('from').' '.$recordEntity->institution->code_name.' '.__('with').' '.$recordEntity->staff_behaviour_category->name;

		return $title;
    }

	public function onSetCustomCaseSummary(Event $event, $id=null)
    {
    	$recordEntity = $this->get($id, [
    		'contain' => ['Staff', 'StaffBehaviourCategories', 'Institutions', 'BehaviourClassifications']
    	]);
    	$summary = '';
    	$summary .= $recordEntity->staff->name.' '.__('from').' '.$recordEntity->institution->code_name.' '.__('with').' '.$recordEntity->staff_behaviour_category->name;

    	return $summary;
    }
}
