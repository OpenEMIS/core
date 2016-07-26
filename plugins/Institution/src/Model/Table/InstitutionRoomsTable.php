<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class InstitutionRoomsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Parents', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'institution_infrastructure_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('RoomTypes', ['className' => 'Infrastructure.RoomTypes']);
		$this->belongsTo('InfrastructureConditions', ['className' => 'FieldOption.InfrastructureConditions']);

		$this->addBehavior('AcademicPeriod.Period');
		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
		$this->addBehavior('CustomField.Record', [
			'fieldKey' => 'infrastructure_custom_field_id',
			'tableColumnKey' => null,
			'tableRowKey' => null,
			'fieldClass' => ['className' => 'Infrastructure.InfrastructureCustomFields'],
			'formKey' => 'infrastructure_custom_form_id',
			'filterKey' => 'infrastructure_custom_filter_id',
			'formFieldClass' => ['className' => 'Infrastructure.InfrastructureCustomFormsFields'],
			'formFilterClass' => ['className' => 'Infrastructure.RoomCustomFormsFilters'],
			'recordKey' => 'institution_room_id',
			'fieldValueClass' => ['className' => 'Infrastructure.RoomCustomFieldValues', 'foreignKey' => 'institution_room_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => null
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator
			->add('code', [
	    		'ruleUnique' => [
			        'rule' => ['validateUnique', ['scope' => 'institution_id']],
			        'provider' => 'table'
			    ]
		    ])
		;
	}

	public function indexBeforeAction(Event $event) {
		$parentId = $this->request->query('parent');
		
		$this->ControllerAction->setFieldOrder(['code', 'name', 'institution_infrastructure_id', 'room_type_id']);

		$this->ControllerAction->field('start_date', ['visible' => false]);
		$this->ControllerAction->field('start_year', ['visible' => false]);
		$this->ControllerAction->field('end_date', ['visible' => false]);
		$this->ControllerAction->field('end_year', ['visible' => false]);
		$this->ControllerAction->field('academic_period_id', ['visible' => false]);
		$this->ControllerAction->field('infrastructure_condition_id', ['visible' => false]);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$parentId = $this->request->query('parent');
		if (!is_null($parentId)) {
			$query->where([$this->aliasField('institution_infrastructure_id') => $parentId]);

			$crumbs = $this->Parents->findPath(['for' => $parentId]);
			$this->controller->set('crumbs', $crumbs);
		} else {
			$query->where([$this->aliasField('institution_infrastructure_id IS NULL')]);
		}

		$toolbarElements = [
			['name' => 'Institution.Infrastructure/breadcrumb', 'data' => [], 'options' => []]
		];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function onUpdateFieldInstitutionInfrastructureId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$parentId = $this->request->query('parent');

			$attr['type'] = 'hidden';
			$attr['value'] = $parentId;
		}

		return $attr;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$periodOptions = $this->AcademicPeriods->getYearList(['withLevels' => true, 'isEditable' => true]);

			$attr['options'] = $periodOptions;
			$attr['select'] = false;
			$attr['onChangeReload'] = 'changePeriod';
		}

		return $attr;
	}

	public function onUpdateFieldRoomTypeId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$attr['onChangeReload'] = 'changeType';
		}

		return $attr;
	}

	public function addEditOnChangeType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['type']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('room_type_id', $request->data[$this->alias()])) {
					$selectedType = $request->data[$this->alias()]['room_type_id'];
					$request->query['type'] = $selectedType;
				}

				if (array_key_exists('custom_field_values', $request->data[$this->alias()])) {
					unset($request->data[$this->alias()]['custom_field_values']);
				}
			}
		}
	}

	private function setupFields(Entity $entity) {
		$this->ControllerAction->setFieldOrder([
			'institution_id', 'institution_infrastructure_id', 'academic_period_id', 'code', 'name', 'room_type_id', 'start_date', 'start_year', 'end_date', 'end_year', 'infrastructure_condition_id'
		]);

		$this->ControllerAction->field('institution_infrastructure_id', ['entity' => $entity]);
		$this->ControllerAction->field('academic_period_id', ['type' => 'select']);
		$this->ControllerAction->field('code');
		$this->ControllerAction->field('room_type_id', ['type' => 'select']);
		$this->ControllerAction->field('infrastructure_condition_id', ['type' => 'select']);
	}
}
