<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

class StaffBehavioursTable extends ControllerActionTable {
	use OptionsTrait;

	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->belongsTo('Staff', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('StaffBehaviourCategories', ['className' => 'Staff.StaffBehaviourCategories']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('BehaviourClassifications', ['className' => 'Student.BehaviourClassifications', 'foreignKey' => 'behaviour_classification_id']);

		$this->addBehavior('AcademicPeriod.Period');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
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

	public function afterAction(Event $event, ArrayObject $extra)
	{
		$this->field('openemis_no');
		$this->field('staff_id');
		$this->field('staff_behaviour_category_id', ['type' => 'select', 'onChangeReload' => 'changeStaffBehaviourCategoryId']);
		$this->field('behaviour_classification_id', ['type' => 'select']);

		if ($this->action == 'view' || $this->action == 'edit') {
			$this->setFieldOrder(['openemis_no', 'staff_id', 'date_of_behaviour', 'time_of_behaviour', 'staff_behaviour_category_id', 'behaviour_classification_id']);

		} else if ($this->action == 'add') {
			$this->setFieldOrder(['academic_period_id', 'staff_id', 'staff_behaviour_category_id', 'behaviour_classification_id', 'date_of_behaviour', 'time_of_behaviour']);

		} else if ($this->action == 'index') {
			$this->setFieldOrder(['openemis_no', 'staff_id', 'date_of_behaviour', 'staff_behaviour_category_id', 'behaviour_classification_id']);
		}
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('description', ['visible' => false]);
		$this->field('action', ['visible' => false]);
		$this->field('time_of_behaviour', ['visible' => false]);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$toolbarElements = [
			['name' => 'Institution.Behaviours/controls', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);

		// Setup period options
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		// $periodOptions = ['0' => __('All Periods')];
		$periodOptions = $AcademicPeriod->getList();
		if (empty($this->request->query['academic_period_id'])) {
			$this->request->query['academic_period_id'] = $AcademicPeriod->getCurrent();
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
			$AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$academicPeriod = $AcademicPeriodTable->get($academicPeriodId);
			$this->request->data[$this->alias()]['academic_start_date'] = $academicPeriod->start_date;
			$this->request->data[$this->alias()]['academic_end_date'] = $academicPeriod->end_date;
		}
		$this->field('date_of_behaviour');
		$this->field('academic_period_id');
	}

	public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$query->contain(['Staff']);
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
	{
		$this->fields['staff_id']['attr']['value'] = $entity->staff->name_with_id;
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
		if(!empty($request->data[$this->alias()]['academic_period_id'])) {
			$startDate = $request->data[$this->alias()]['academic_start_date'];
			$endDate = $request->data[$this->alias()]['academic_end_date'];
			$attr['value'] = $startDate->format('d-m-Y');
			$attr['default_date'] = false;
			$attr['date_options'] = ['startDate' => $startDate->format('d-m-Y'), 'endDate' => $endDate->format('d-m-Y')];
			return $attr;
		}
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
	{
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');

		if ($action == 'add') {
			$periodOptions = $AcademicPeriod->getList(['isEditable'=>true]);
			$selectedPeriod = 0;
			if ($request->is(['post', 'put'])) {
				$selectedPeriod = $request->data($this->aliasField('academic_period_id'));
			}

			$attr['options'] = $periodOptions;
			$attr['onChangeReload'] = 'changePeriod';

			//set start and end dates for date of behaviour based on chosen academic period
			if (!empty($selectedPeriod)) {
				$periodEntity = $AcademicPeriod->get($selectedPeriod);
				$dateOptions = [
					'startDate' => $periodEntity->start_date->format('d-m-Y'),
					'endDate' => $periodEntity->end_date->format('d-m-Y')
				];
				$this->fields['date_of_behaviour']['date_options'] = $dateOptions;
			}
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

	public function addEditOnChangeStaffBehaviourCategoryId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
	{
		$request = $this->request;
		unset($data[$this->alias()]['behaviour_classification_id']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('staff_behaviour_category_id', $request->data[$this->alias()])) {
					$selectedCategory = $this->StaffBehaviourCategories->get($request->data[$this->alias()]['staff_behaviour_category_id']);

					if (!empty($selectedCategory)) {
						$data[$this->alias()]['behaviour_classification_id'] = $selectedCategory->behaviour_classification_id;
					}
				}
			}
		}
	}

	public function onUpdateFieldBehaviourClassificationId(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add') {
            $defaultCategory = $this->StaffBehaviourCategories
				->find()
				->where([$this->StaffBehaviourCategories->aliasField('default') => 1])
				->first();

			if (!empty($defaultCategory)) {
				// set default classification if there is a default category
				$attr['default'] = $defaultCategory->behaviour_classification_id;
			}
        }

        return $attr;
	}
}
