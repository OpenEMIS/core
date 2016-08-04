<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\I18n\Date;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class InstitutionRoomsTable extends AppTable {
	use OptionsTrait;
	const UPDATE_DETAILS = 1;	// In Use
	const END_OF_USAGE = 2;
	const CHANGE_IN_ROOM_TYPE = 3;

	private $Levels = null;
	private $levelOptions = [];
	private $roomLevel = null;

	private $canUpdateDetails = true;
	private $currentAcademicPeriod = null;

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('RoomStatuses', ['className' => 'Infrastructure.RoomStatuses']);
		$this->belongsTo('Parents', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'institution_infrastructure_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('RoomTypes', ['className' => 'Infrastructure.RoomTypes']);
		$this->belongsTo('InfrastructureConditions', ['className' => 'FieldOption.InfrastructureConditions']);
		$this->belongsTo('PreviousRoomUsages', ['className' => 'Institution.InstitutionRooms', 'foreignKey' => 'previous_room_usage_id']);

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
			        'rule' => ['validateUnique', ['scope' => ['start_date', 'institution_id']]],
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

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		if (!$entity->isNew() && $entity->has('change_type')) {
			$editType = $entity->change_type;
			$statuses = $this->RoomStatuses->find('list', ['keyField' => 'id', 'valueField' => 'code'])->toArray();
			$functionKey = Inflector::camelize(strtolower($statuses[$editType]));
			$functionName = "process$functionKey";

			if (method_exists($this, $functionName)) {
				$event->stopPropagation();
				$this->$functionName($entity);
			}
		}
	}

	public function onGetInfrastructureLevel(Event $event, Entity $entity) {
		return $this->levelOptions[$this->roomLevel];
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder(['code', 'name', 'room_type_id', 'room_status_id']);

		$this->ControllerAction->field('infrastructure_level', ['after' => 'name']);
		$this->ControllerAction->field('start_date', ['visible' => false]);
		$this->ControllerAction->field('start_year', ['visible' => false]);
		$this->ControllerAction->field('end_date', ['visible' => false]);
		$this->ControllerAction->field('end_year', ['visible' => false]);
		$this->ControllerAction->field('institution_infrastructure_id', ['visible' => false]);
		$this->ControllerAction->field('academic_period_id', ['visible' => false]);
		$this->ControllerAction->field('infrastructure_condition_id', ['visible' => false]);
		$this->ControllerAction->field('previous_room_usage_id', ['visible' => false]);

		$toolbarElements = [];
		$toolbarElements = $this->addBreadcrumbElement($toolbarElements);
		$toolbarElements = $this->addControlFilterElement($toolbarElements);
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

		// Academic Period
		list($periodOptions, $selectedPeriod) = array_values($this->getPeriodOptions());
		$query->where([$this->aliasField('academic_period_id') => $selectedPeriod]);
		$this->controller->set(compact('periodOptions', 'selectedPeriod'));
		// End

		// Room Types
		list($typeOptions, $selectedType) = array_values($this->getTypeOptions(['withAll' => true]));
		if ($selectedType != '-1') {
			$query->where([$this->aliasField('room_type_id') => $selectedType]);
		}
		$this->controller->set(compact('typeOptions', 'selectedType'));
		// End

		$inUseId = $this->RoomStatuses->getIdByCode('IN_USE');
		$query->where([$this->aliasField('room_status_id') => $inUseId]);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		unset($this->request->query['edit_type']);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['AcademicPeriods', 'RoomTypes', 'InfrastructureConditions']);
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

	public function editAfterAction(Event $event, Entity $entity) {
		$selectedEditType = $this->request->query('edit_type');
		if ($selectedEditType == self::END_OF_USAGE || $selectedEditType == self::CHANGE_IN_ROOM_TYPE) {
			foreach ($this->fields as $field => $attr) {
				if ($this->startsWith($field, 'custom_')) {
					$this->fields[$field]['visible'] = false;
				}
			}
		}
	}

	public function onUpdateFieldChangeType(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view' || $action == 'add') {
			$attr['visible'] = false;
		} else if ($action == 'edit') {
			$selectedEditType = $request->query('edit_type');
			if ($selectedEditType == self::END_OF_USAGE || $selectedEditType == self::CHANGE_IN_ROOM_TYPE) {
				$this->canUpdateDetails = false;
				if ($this->hasBehavior('Record')) {
					$this->removeBehavior('Record');
				}
			}

			$attr['type'] = 'select';
			$attr['options'] = $this->getSelectOptions($this->aliasField('change_types'));
			$attr['select'] = false;
			$attr['onChangeReload'] = 'changeEditType';
		}

		return $attr;
	}

	public function onUpdateFieldRoomStatusId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$inUseId = $this->RoomStatuses->getIdByCode('IN_USE');
			$attr['value'] = $inUseId;
		}

		return $attr;
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
				$attr['type'] = 'readonly';
				$attr['value'] = $parentId;
				$attr['attr']['value'] = $this->Parents->getParentPath($parentId);
			}
		}

		return $attr;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$currentAcademicPeriodId = $this->AcademicPeriods->getCurrent();
			$this->currentAcademicPeriod = $this->AcademicPeriods->get($currentAcademicPeriodId);

			$attr['type'] = 'readonly';
			$attr['value'] = $currentAcademicPeriodId;
			$attr['attr']['value'] = $this->currentAcademicPeriod->name;
		} else if ($action == 'edit') {
			$entity = $attr['entity'];
			$this->currentAcademicPeriod = $entity->academic_period;

			$attr['type'] = 'readonly';
			$attr['value'] = $entity->academic_period->id;
			$attr['attr']['value'] = $entity->academic_period->name;
		}

		return $attr;
	}

	public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$parentId = $request->query('parent');
			$autoGenerateCode = $this->getAutoGenerateCode($parentId);

			$attr['attr']['default'] = $autoGenerateCode;
		} else if ($action == 'edit') {
			$attr['type'] = 'readonly';
		}

		return $attr;
	}

	public function onUpdateFieldName(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			$selectedEditType = $request->query('edit_type');
			if (!$this->canUpdateDetails) {
				$attr['type'] = 'readonly';
			}
		}

		return $attr;
	}

	public function onUpdateFieldRoomTypeId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['onChangeReload'] = 'changeRoomType';
		} else if ($action == 'edit') {
			$selectedEditType = $request->query('edit_type');
			if ($selectedEditType == self::END_OF_USAGE) {
				$attr['type'] = 'hidden';
			} else {
				$entity = $attr['entity'];

				$attr['type'] = 'readonly';
				$attr['value'] = $entity->room_type->id;
				$attr['attr']['value'] = $entity->room_type->name;
			}
		}

		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$startDate = $this->currentAcademicPeriod->start_date->format('d-m-Y');
			$endDate = $this->currentAcademicPeriod->end_date->format('d-m-Y');

			$attr['date_options']['startDate'] = $startDate;
			$attr['date_options']['endDate'] = $endDate;
		} else if ($action == 'edit') {
			$entity = $attr['entity'];

			$attr['type'] = 'readonly';
			$attr['value'] = $entity->start_date->format('Y-m-d');
			$attr['attr']['value'] = $this->formatDate($entity->start_date);
		}

		return $attr;
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
			$attr['visible'] = false;
		} else if ($action == 'add') {
			$endDate = $this->currentAcademicPeriod->end_date->format('d-m-Y');

			$attr['type'] = 'hidden';
			$attr['value'] = $endDate;
		} else if ($action == 'edit') {
			$entity = $attr['entity'];

			$selectedEditType = $request->query('edit_type');
			if ($selectedEditType == self::END_OF_USAGE) {
				$startDate = $entity->start_date->format('d-m-Y');
				$endDate = $this->currentAcademicPeriod->end_date->format('d-m-Y');

				$attr['date_options']['startDate'] = $startDate;
				$attr['date_options']['endDate'] = $endDate;
			} else {
				$attr['type'] = 'hidden';
				$attr['value'] = $entity->end_date->format('Y-m-d');
			}
		}

		return $attr;
	}

	public function onUpdateFieldInfrastructureConditionId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			$selectedEditType = $request->query('edit_type');
			if (!$this->canUpdateDetails) {
				$attr['type'] = 'hidden';
			}
		}

		return $attr;
	}

	public function onUpdateFieldPreviousRoomUsageId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['value'] = 0;
		}

		return $attr;
	}

	public function onUpdateFieldNewRoomType(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			$entity = $attr['entity'];

			$selectedEditType = $request->query('edit_type');
			if ($selectedEditType == self::CHANGE_IN_ROOM_TYPE) {
				$roomTypeOptions = $this->RoomTypes
					->find('list')
					->find('visible')
					->where([
						$this->RoomTypes->aliasField('id <>') => $entity->room_type_id
					])
					->toArray();

				$attr['visible'] = true;
				$attr['options'] = $roomTypeOptions;
				$attr['select'] = false;
			}
		}

		return $attr;
	}

	public function onUpdateFieldNewStartDate(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			$entity = $attr['entity'];

			$selectedEditType = $request->query('edit_type');
			if ($selectedEditType == self::CHANGE_IN_ROOM_TYPE) {
				$startDateObj = $entity->start_date->copy();
				$startDateObj->addDay();

				$startDate = $startDateObj->format('d-m-Y');
				$endDate = $this->currentAcademicPeriod->end_date->format('d-m-Y');

				$attr['visible'] = true;
				$attr['date_options']['startDate'] = $startDate;
				$attr['date_options']['endDate'] = $endDate;
			}
		}

		return $attr;
	}

	public function addEditOnChangeEditType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['edit_type']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('change_type', $request->data[$this->alias()])) {
					$selectedEditType = $request->data[$this->alias()]['change_type'];
					$request->query['edit_type'] = $selectedEditType;
				}
			}
		}
	}

	public function addEditOnChangeRoomType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
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
			'institution_id', 'change_type', 'institution_infrastructure_id', 'academic_period_id', 'code', 'name', 'room_type_id', 'start_date', 'start_year', 'end_date', 'end_year', 'infrastructure_condition_id', 'previous_room_usage_id', 'new_room_type', 'new_start_date'
		]);

		$this->ControllerAction->field('change_type');
		$this->ControllerAction->field('room_status_id', ['type' => 'hidden']);
		$this->ControllerAction->field('institution_infrastructure_id', ['entity' => $entity]);
		$this->ControllerAction->field('academic_period_id', ['entity' => $entity]);
		$this->ControllerAction->field('code');
		$this->ControllerAction->field('name');
		$this->ControllerAction->field('room_type_id', ['type' => 'select', 'entity' => $entity]);
		$this->ControllerAction->field('start_date', ['entity' => $entity]);
		$this->ControllerAction->field('end_date', ['entity' => $entity]);
		$this->ControllerAction->field('infrastructure_condition_id', ['type' => 'select']);
		$this->ControllerAction->field('previous_room_usage_id', ['type' => 'hidden']);
		$this->ControllerAction->field('new_room_type', ['type' => 'select', 'visible' => false, 'entity' => $entity]);
		$this->ControllerAction->field('new_start_date', ['type' => 'date', 'visible' => false, 'entity' => $entity]);
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

	private function addControlFilterElement($toolbarElements=[]) {
		$toolbarElements[] = ['name' => 'Institution.Room/controls', 'data' => compact('typeOptions', 'selectedType'), 'options' => []];

		return $toolbarElements;
	}

	private function startsWith($haystack, $needle) {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    private function updateRoomStatus($code, $conditions) {
    	$roomStatuses = $this->RoomStatuses->findCodeList();
		$status = $roomStatuses[$code];

		$entity = $this->find()->where([$conditions])->first();
		$entity->room_status_id = $status;
		$this->save($entity);
	}

	private function processEndOfUsage($entity) {
		$where = ['id' => $entity->id];
		$this->updateRoomStatus('END_OF_USAGE', $where);

		$url = $this->ControllerAction->url('index');
		return $this->controller->redirect($url);
	}

	private function processChangeInRoomType($entity) {
		$newStartDateObj = new Date($entity->new_start_date);
		$endDateObj = $newStartDateObj->copy();
		$endDateObj->addDay(-1);
		$newRoomTypeId = $entity->new_room_type;

		$oldEntity = $this->find()->where(['id' => $entity->id])->first();
		$newRequestData = $oldEntity->toArray();

		// Update old entity
		$oldEntity->end_date = $endDateObj;

		$where = ['id' => $oldEntity->id];
		$this->updateRoomStatus('CHANGE_IN_ROOM_TYPE', $where);
		$this->save($oldEntity);
		// End

		// Update new entity
		$ignoreFields = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
		foreach ($ignoreFields as $key => $field) {
			unset($newRequestData[$field]);
		}
		$newRequestData['start_date'] = $newStartDateObj;
		$newRequestData['room_type_id'] = $newRoomTypeId;
		$newRequestData['previous_room_usage_id'] = $oldEntity->id;
		$newEntity = $this->newEntity($newRequestData, ['validate' => false]);
		$newEntity = $this->save($newEntity);
		// End

		$url = $this->ControllerAction->url('edit');
		unset($url['type']);
		unset($url['edit_type']);
		$url[1] = $newEntity->id;
		return $this->controller->redirect($url);
	}

	public function getPeriodOptions($params=[]) {
		$periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
		if (is_null($this->request->query('period_id'))) {
			$this->request->query['period_id'] = $this->AcademicPeriods->getCurrent();
		}
		$selectedPeriod = $this->queryString('period_id', $periodOptions);
		$this->advancedSelectOptions($periodOptions, $selectedPeriod);

		return compact('periodOptions', 'selectedPeriod');
	}

	public function getTypeOptions($params=[]) {
		$withAll = array_key_exists('withAll', $params) ? $params['withAll'] : false;

		$typeOptions = $this->RoomTypes
			->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->toArray();
		if($withAll && count($typeOptions) > 1) {
			$typeOptions = ['-1' => __('All Room Types')] + $typeOptions;
		}
		$selectedType = $this->queryString('type', $typeOptions);
		$this->advancedSelectOptions($typeOptions, $selectedType);

		return compact('typeOptions', 'selectedType');
	}
}
