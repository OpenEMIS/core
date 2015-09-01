<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class StaffBehavioursTable extends AppTable {
	use OptionsTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Staff', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('StaffBehaviourCategories', ['className' => 'FieldOption.StaffBehaviourCategories']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

		$this->addBehavior('AcademicPeriod.Period');
	}

	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->staff->openemis_no;
	}

	public function beforeAction() {
		$this->ControllerAction->field('openemis_no');
		$this->ControllerAction->field('staff_id');
		$this->ControllerAction->field('staff_behaviour_category_id', ['type' => 'select']);
		
		if ($this->action == 'view' || $this->action == 'edit') {
			$this->ControllerAction->setFieldOrder(['openemis_no', 'staff_id', 'date_of_behaviour', 'time_of_behaviour', 'title', 'staff_behaviour_category_id']);
		}
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->field('description', ['visible' => false]);
		$this->ControllerAction->field('action', ['visible' => false]);
		$this->ControllerAction->field('time_of_behaviour', ['visible' => false]);

		$this->ControllerAction->setFieldOrder(['openemis_no', 'staff_id', 'date_of_behaviour', 'title', 'staff_behaviour_category_id']);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$toolbarElements = [
			['name' => 'Institution.Behaviours/controls', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);

		// Setup period options
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = ['0' => __('All Periods')];
		$periodOptions = $periodOptions + $AcademicPeriod->getList();

		$selectedPeriod = $this->queryString('period_id', $periodOptions);
		$this->advancedSelectOptions($periodOptions, $selectedPeriod);

		if (!empty($selectedPeriod)) {
			$query->find('inPeriod', ['field' => 'date_of_behaviour', 'academic_period_id' => $selectedPeriod]);
		}
		
		$this->controller->set(compact('periodOptions'));

		// will need to check for search by name: AdvancedNameSearchBehavior
	}

	public function addAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('academic_period');
		$this->ControllerAction->setFieldOrder(['academic_period', 'staff_id', 'staff_behaviour_category_id', 'date_of_behaviour', 'time_of_behaviour']);
	}

	public function editBeforeQuery(Event $event, Query $query) {
		$query->contain(['Staff']);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->fields['staff_id']['attr']['value'] = $entity->staff->name_with_id;
	}

	public function onUpdateFieldOpenemisNo(Event $event, array $attr, $action, $request) {
		if ($action == 'edit' || $action == 'add') {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');

		if ($action == 'add') {
			$periodOptions = ['0' => $this->selectEmpty('period')];
			$periodOptions = $periodOptions + $AcademicPeriod->getList();
			$selectedPeriod = 0;
			if ($request->is(['post', 'put'])) {
				$selectedPeriod = $request->data($this->aliasField('academic_period'));
			}
			$this->advancedSelectOptions($periodOptions, $selectedPeriod);

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

	public function onUpdateFieldStaffId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$staffOptions = ['' => $this->selectEmpty('staff')];

			$selectedPeriod = 0;
			if ($request->is(['post', 'put'])) {
				$selectedPeriod = $request->data($this->aliasField('academic_period'));
			}

			if (!empty($selectedPeriod)) {
				$institutionId = $this->Session->read('Institution.Institutions.id');
				$Staff = TableRegistry::get('Institution.Staff');
				$staffOptions = $staffOptions + $Staff
				->find('list', ['keyField' => 'security_user_id', 'valueField' => 'name'])
				->matching('Users')
				->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
				->where([$Staff->aliasField('institution_site_id') => $institutionId])
				->toArray();
			}
			
			$attr['options'] = $staffOptions;
		} else if ($action == 'edit') {
			$attr['type'] = 'readonly';
		}
		return $attr;
	}
}
