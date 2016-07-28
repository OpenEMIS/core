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
	private $Levels = null;
	private $levelOptions = [];
	private $roomLevel = null;

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

		$this->Levels = TableRegistry::get('Infrastructure.InfrastructureLevels');
		$this->levelOptions = $this->Levels->getOptions(['keyField' => 'id', 'valueField' => 'name']);
		$this->roomLevel = $this->Levels->getFieldByCode('ROOM', 'id');
	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra) {
    	$extra['excludedModels'] = [$this->CustomFieldValues->alias()];
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
		    ->add('start_date', [
				'ruleInAcademicPeriod' => [
					'rule' => ['inAcademicPeriod', 'academic_period_id']
				]
			])
			->add('end_date', 'ruleCompareDateReverse', [
				'rule' => ['compareDateReverse', 'start_date', true]
			])
		;
	}

	public function onGetInfrastructureLevel(Event $event, Entity $entity) {
		return $this->levelOptions[$this->roomLevel];
	}

	public function indexBeforeAction(Event $event) {		
		$this->ControllerAction->setFieldOrder(['code', 'name', 'room_type_id']);

		$this->ControllerAction->field('infrastructure_level', ['after' => 'name']);
		$this->ControllerAction->field('start_date', ['visible' => false]);
		$this->ControllerAction->field('start_year', ['visible' => false]);
		$this->ControllerAction->field('end_date', ['visible' => false]);
		$this->ControllerAction->field('end_year', ['visible' => false]);
		$this->ControllerAction->field('institution_infrastructure_id', ['visible' => false]);
		$this->ControllerAction->field('academic_period_id', ['visible' => false]);
		$this->ControllerAction->field('infrastructure_condition_id', ['visible' => false]);

		$toolbarElements = $this->addBreadcrumbElement();
		$this->controller->set('toolbarElements', $toolbarElements);

		// For breadcrumb to build the baseUrl
		$this->controller->set('breadcrumbPlugin', 'Institution');
		$this->controller->set('breadcrumbController', 'Institutions');
		$this->controller->set('breadcrumbAction', 'Infrastructures');
		// End
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$parentId = $this->request->query('parent');
		if (!is_null($parentId)) {
			$query->where([$this->aliasField('institution_infrastructure_id') => $parentId]);
		} else {
			$query->where([$this->aliasField('institution_infrastructure_id IS NULL')]);
		}
	}

	public function addEditBeforeAction(Event $event) {
		$toolbarElements = $this->addBreadcrumbElement();
		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function onUpdateFieldInstitutionInfrastructureId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
			$entity = $attr['entity'];

			$attr['type'] = 'hidden';
			$parentId = $entity->institution_infrastructure_id;
			if (!empty($parentId)) {
				$list = $this->Parents->findPath(['for' => $parentId, 'withLevels' => true]);
			} else {
				$list = [];
			}

			$field = 'institution_infrastructure_id';
			$after = $field;
			foreach ($list as $key => $infrastructure) {
				$this->ControllerAction->field($field.$key, [
					'type' => 'readonly',
					'attr' => ['label' => $infrastructure->_matchingData['Levels']->name],
					'value' => $infrastructure->code_name,
					'after' => $after
				]);
				$after = $field.$key;
			}
		} else if ($action == 'add' || $action == 'edit') {
			$parentId = $this->request->query('parent');

			if (is_null($parentId)) {
				$attr['type'] = 'hidden';
				$attr['value'] = null;
			} else {
				if ($action == 'add') {
					$attr['type'] = 'readonly';
					$attr['value'] = $parentId;
					$attr['attr']['value'] = $this->Parents->getParentPath($parentId);
				} else if ($action == 'edit') {
					$Parents = $this->Parents;

					$session = $request->session();
					$institutionId = $session->read('Institution.Institutions.id');

					$grandParentId = $Parents->get($parentId)->parent_id;
					$where = [$Parents->Parents->aliasField('institution_id') => $institutionId];
					if (is_null($grandParentId)) {
						$where[] = $Parents->Parents->aliasField('parent_id IS NULL');
					} else {
						$where[$Parents->Parents->aliasField('parent_id')] = $grandParentId;
						$crumbs = $Parents->findPath(['for' => $grandParentId]);
						$this->controller->set('crumbs', $crumbs);
					}
					$parents = $Parents->Parents->find()->where($where)->all();

					$parentOptions = [];
					foreach ($parents as $key => $parent) {
						$parentOptions[$parent->id] = $parent->code . " - " . $parent->name;
					}
					$this->advancedSelectOptions($parentOptions, $parentId);

					$attr['type'] = 'select';
					$attr['options'] = $parentOptions;
					$attr['select'] = false;
				}
			}
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

	public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$parentId = $request->query('parent');
			$autoGenerateCode = $this->getAutoGenerateCode($parentId);

			$attr['attr']['default'] = $autoGenerateCode;
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

	private function getAutoGenerateCode($parentId) {
		// getting suffix of code by counting
		$indexData = $this->find()
			->where([$this->aliasField('institution_infrastructure_id') => $parentId])
			->count();
		$indexData += 1; // starts counting from 1
		$indexData = strval($indexData);

		// if 1 character prepend '0'
		$indexData = (strlen($indexData) == 1)? '0'.$indexData: $indexData;

		// has Parent then get the ID of the parent then followed by counter
		$parentData = $this->Parents->find()
			->where([
				$this->Parents->aliasField($this->Parents->primaryKey()) => $parentId
			])
			->first();

		if (!empty($parentData)) {
			return $parentData->code . $indexData;
		} else {
			return $indexData;
		}
	}

	private function addBreadcrumbElement($toolbarElements=[]) {
		$parentId = $this->request->query('parent');
		$crumbs = $this->Parents->findPath(['for' => $parentId]);
		$levelOptions = $this->Levels->getOptions();
		$selectedLevel = $this->roomLevel;
		$toolbarElements[] = ['name' => 'Institution.Infrastructure/breadcrumb', 'data' => compact('crumbs', 'levelOptions', 'selectedLevel'), 'options' => []];

		return $toolbarElements;
	}
}
