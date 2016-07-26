<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\Validation\Validator;

class InstitutionInfrastructuresTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Parents', ['className' => 'Institution.InstitutionInfrastructures']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('Levels', ['className' => 'Infrastructure.InfrastructureLevels', 'foreignKey' => 'infrastructure_level_id']);
		$this->belongsTo('Types', ['className' => 'Infrastructure.InfrastructureTypes', 'foreignKey' => 'infrastructure_type_id']);
		$this->belongsTo('InfrastructureOwnerships', ['className' => 'FieldOption.InfrastructureOwnerships']);
		$this->belongsTo('InfrastructureConditions', ['className' => 'FieldOption.InfrastructureConditions']);
		$this->hasMany('ChildInfrastructures', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'parent_id']);

		$this->addBehavior('CustomField.Record', [
			'fieldKey' => 'infrastructure_custom_field_id',
			'tableColumnKey' => null,
			'tableRowKey' => null,
			'fieldClass' => ['className' => 'Infrastructure.InfrastructureCustomFields'],
			'formKey' => 'infrastructure_custom_form_id',
			'filterKey' => 'infrastructure_custom_filter_id',
			'formFieldClass' => ['className' => 'Infrastructure.InfrastructureCustomFormsFields'],
			'formFilterClass' => ['className' => 'Infrastructure.InfrastructureCustomFormsFilters'],
			'recordKey' => 'institution_infrastructure_id',
			'fieldValueClass' => ['className' => 'Infrastructure.InfrastructureCustomFieldValues', 'foreignKey' => 'institution_infrastructure_id', 'dependent' => true, 'cascadeCallbacks' => true],
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

	public function onGetCode(Event $event, Entity $entity) {
		return $event->subject()->HtmlField->link($entity->code, [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => $this->alias,
			'index',
			'parent' => $entity->id,
			'parent_level' => $entity->infrastructure_level_id
		]);
	}

	public function indexBeforeAction(Event $event) {
		$parentId = $this->request->query('parent');
		if (!is_null($parentId)) {
			$parentLevelId = $this->request->query('parent_level');
			if (!is_null($parentLevelId)) {
				$floorLevelId = $this->Levels->getFieldByCode('FLOOR', 'id');

				if ($parentLevelId == $floorLevelId) {
					$url = [
						'plugin' => 'Institution',
						'controller' => 'Institutions',
						'action' => 'Rooms',
						'parent' => $parentId,
						'parent_level' => $parentLevelId
					];

					$event->stopPropagation();
					return $this->controller->redirect($url);
				}
			}

			$crumbs = $this->findPath(['for' => $parentId]);
			$this->controller->set('crumbs', $crumbs);
		}

		$this->ControllerAction->setFieldOrder(['code', 'name', 'infrastructure_level_id', 'infrastructure_type_id']);

		$this->ControllerAction->field('parent_id', ['visible' => false]);
		$this->ControllerAction->field('size', ['visible' => false]);
		$this->ControllerAction->field('infrastructure_ownership_id', ['visible' => false]);
		$this->ControllerAction->field('year_acquired', ['visible' => false]);
		$this->ControllerAction->field('year_disposed', ['visible' => false]);
		$this->ControllerAction->field('infrastructure_condition_id', ['visible' => false]);
		$this->ControllerAction->field('comment', ['visible' => false]);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		// Filter by parent
		$parentId = $this->request->query('parent');
		if (!is_null($parentId)) {
			$query->where([$this->aliasField('parent_id') => $parentId]);
		} else {
			$query->where([$this->aliasField('parent_id IS NULL')]);
		}
		// End

		$toolbarElements = [
			['name' => 'Institution.Infrastructure/breadcrumb', 'data' => [], 'options' => []]
		];

		// No need to show controls filter if only has one level options
		list($levelOptions, $selectedLevel) = array_values($this->getLevelOptions(['withAll' => true]));
		if (count($levelOptions) > 1) {
			$toolbarElements[] = ['name' => 'Institution.Infrastructure/controls', 'data' => [], 'options' => []];
			$this->controller->set(compact('levelOptions', 'selectedLevel'));
		}
		$this->controller->set('toolbarElements', $toolbarElements);

		if ($selectedLevel != '-1') {
			$query->where([$this->aliasField('infrastructure_level_id') => $selectedLevel]);
		}
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		list(, $selectedLevel) = array_values($this->getLevelOptions());
		$entity->infrastructure_level_id = $selectedLevel;
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['level'] = $entity->infrastructure_level_id;
		$this->request->query['type'] = $entity->infrastructure_type_id;
	}

	public function editBeforeAction(Event $event) {
		// Add breadcrumb
		$toolbarElements = [
            ['name' => 'Institution.Infrastructure/breadcrumb', 'data' => [], 'options' => []]
        ];
		$this->controller->set('toolbarElements', $toolbarElements);

		list($levelOptions, $selectedLevel) = array_values($this->getLevelOptions(['withAll' => true]));
		$this->controller->set(compact('levelOptions', 'selectedLevel'));
		// End
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	private function getAutoGenerateCode($institutionId, $infrastructureLevelId, $parentId) {

		if (!empty($parentId)) { //if have parent, then count number of child of parent
			$conditions[] = $this->aliasField('parent_id') . " = " . $parentId;
		} else { // no parent, means 1st level (parent = null), then count number of infra of the institution.
			$conditions[] = $this->aliasField('parent_id') . ' IS NULL';
			$conditions[] = $this->aliasField('institution_id') . " = " . $institutionId;
		}

		// getting suffix of code by counting
		$indexData = $this->find()
			->where($conditions)
			->count();
		$indexData += 1; // starts counting from 1
		$indexData = strval($indexData);

		// if 1 character prepend '0'
		$indexData = (strlen($indexData) == 1)? '0'.$indexData: $indexData;
		if (empty($parentId)) {
			// no Parent then get institutionID followed by counter
			$institutionData = $this->Institutions->find()
				->where([
					$this->Institutions->aliasField($this->Institutions->primaryKey()) => $institutionId
				])
				->select([$this->Institutions->aliasField('code')])
				->first();
			if (!empty($institutionData)) {
				return $institutionData->code . $indexData;
			} else {
				return $indexData;
			}
		} else {
			// has Parent then get the ID of the parent then followed by counter
			$parentData = $this->find()
				->where([
					$this->aliasField($this->primaryKey()) => $parentId
				])
				->first()
				;

			if (!empty($parentData)) {
				return $parentData->code . $indexData;
			} else {
				return $indexData;
			}
		}
	}

	public function onGetConvertOptions(Event $event, Entity $entity, Query $query) {
		$query->where([
			$this->aliasField('institution_id') => $entity->institution_id,
			$this->aliasField('parent_id') => $entity->parent_id
		]);
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id, ArrayObject $extra) {
		$entity = $this->get($id);
		$transferTo = $this->request->data['transfer_to'];
		$transferFrom = $id;

		if (empty($transferTo) && $this->ControllerAction->hasAssociatedRecords($this, $entity, $extra)) {
			$event->stopPropagation();
			$this->Alert->error('general.deleteTransfer.restrictDelete');
			$url = $this->ControllerAction->url('remove');
			return $this->controller->redirect($url);
		} else {
			// Require to update the parent id
			$this->updateAll(
				['parent_id' => $transferTo],
				['parent_id' => $transferFrom]
			);

			$process = function($model, $id, $options) {
				$entity = $model->get($id);
				return $model->delete($entity, $options->getArrayCopy());
			};

			return $process;
		}
	}

	public function onUpdateFieldParentId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
			$entity = $attr['entity'];

			$attr['type'] = 'hidden';
			$parentId = $entity->parent_id;
			if (!empty($parentId)) {
				$list = $this->findPath(['for' => $parentId, 'withLevels' => true]);
			} else {
				$list = [];
			}

			$field = 'parent_id';
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
					$attr['attr']['value'] = $this->getParentPath($parentId);
				} else if ($action == 'edit') {
					$session = $request->session();
					$institutionId = $session->read('Institution.Institutions.id');

					$grandParentId = $this->get($parentId)->parent_id;
					$where = [$this->Parents->aliasField('institution_id') => $institutionId];
					if (is_null($grandParentId)) {
						$where[] = $this->Parents->aliasField('parent_id IS NULL');
					} else {
						$where[$this->Parents->aliasField('parent_id')] = $grandParentId;
						$crumbs = $this->findPath(['for' => $grandParentId]);
						$this->controller->set('crumbs', $crumbs);
					}
					$parents = $this->Parents->find()->where($where)->all();

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

	public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$session = $request->session();
			$institutionId = $session->read('Institution.Institutions.id');
			$selectedLevel = $request->query('level');
			$parentId = $request->query('parent');
			$autoGenerateCode = $this->getAutoGenerateCode($institutionId, $selectedLevel, $parentId);

			$attr['attr']['default'] = $autoGenerateCode;
		}

		return $attr;
	}

	public function onUpdateFieldInfrastructureLevelId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			list($levelOptions, $selectedLevel) = array_values($this->getLevelOptions());

			$attr['options'] = $levelOptions;
			$attr['select'] = false;
			$attr['onChangeReload'] = 'changeLevel';
		}

		return $attr;
	}

	public function onUpdateFieldInfrastructureTypeId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$selectedLevel = $request->query('level');
			$typeOptions = $this->Types
				->find('list')
				->find('visible')
				->find('order')
				->where([$this->Types->aliasField('infrastructure_level_id') => $selectedLevel])
				->toArray();

			$attr['options'] = $typeOptions;
			$attr['onChangeReload'] = 'changeType';
		}

		return $attr;
	}

	public function onUpdateFieldYearAcquired(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$yearOptions = $this->getYearOptionsByConfig();
			$attr['options'] = $yearOptions;
		}

		return $attr;
	}

	public function onUpdateFieldYearDisposed(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$yearOptions = $this->getYearOptionsByConfig();
			$attr['options'] = $yearOptions;
		}

		return $attr;
	}

	public function addEditOnChangeLevel(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['level']);
		unset($request->query['type']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('infrastructure_level_id', $request->data[$this->alias()])) {
					$selectedLevel = $request->data[$this->alias()]['infrastructure_level_id'];
					$request->query['level'] = $selectedLevel;
				}

				if (array_key_exists('custom_field_values', $request->data[$this->alias()])) {
					unset($request->data[$this->alias()]['custom_field_values']);
				}
			}
		}
	}

	public function addEditOnChangeType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['type']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('infrastructure_type_id', $request->data[$this->alias()])) {
					$selectedType = $request->data[$this->alias()]['infrastructure_type_id'];
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
			'institution_id', 'parent_id', 'code', 'name', 'infrastructure_level_id', 'infrastructure_type_id', 'size', 'infrastructure_ownership_id', 'year_acquired', 'year_disposed', 'infrastructure_condition_id', 'comment'
		]);

		$this->ControllerAction->field('parent_id', ['entity' => $entity]);
		$this->ControllerAction->field('code');
		$this->ControllerAction->field('infrastructure_level_id', ['type' => 'select']);
		$this->ControllerAction->field('infrastructure_type_id', ['type' => 'select']);
		$this->ControllerAction->field('infrastructure_ownership_id', ['type' => 'select']);
		$this->ControllerAction->field('year_acquired');
		$this->ControllerAction->field('year_disposed');
		$this->ControllerAction->field('infrastructure_condition_id', ['type' => 'select']);
	}

	public function findPath($params=[]) {
		$parentId = array_key_exists('for', $params) ? $params['for'] : null;
		$withLevels = array_key_exists('withLevels', $params) ? $params['withLevels'] : false;

		$paths = [];
		while (!is_null($parentId)) {
			$query = $this->find()->where([$this->aliasField('id') => $parentId]);
			if ($withLevels) { $query->matching('Levels'); }
			$results = $query->first();

			array_unshift($paths, $results);
			$parentId = $results->parent_id;
		}

		return $paths;
	}

	public function getParentPath($parentId=null) {
		$crumbs = $this->findPath(['for' => $parentId]);

		$parentPath = __('All') . ' > ';
		foreach ($crumbs as $crumb) {
			$parentPath .= $crumb->name;
			$parentPath .= $crumb === end($crumbs) ? '' : ' > ';
		}

		return $parentPath;
	}

	public function getLevelOptions($params=[]) {
		$withAll = array_key_exists('withAll', $params) ? $params['withAll'] : false;

		$parentId = $this->request->query('parent');
		$levelQuery = $this->Levels->find('list', ['keyField' => 'id', 'valueField' => 'code_name']);
		if (is_null($parentId)) {
			$levelQuery->where([$this->Levels->aliasField('parent_id IS NULL')]);
		} else {
			$levelId = $this->get($parentId)->infrastructure_level_id;
			$levelQuery->where([$this->Levels->aliasField('parent_id') => $levelId]);
		}
		$levelOptions = $levelQuery->toArray();
		if($withAll && count($levelOptions) > 1) {
			$levelOptions = ['-1' => __('All Levels')] + $levelOptions;
		}
		$selectedLevel = $this->queryString('level', $levelOptions);
		$this->advancedSelectOptions($levelOptions, $selectedLevel);

		return compact('levelOptions', 'selectedLevel');
	}

	public function getYearOptionsByConfig() {
		$ConfigItems = TableRegistry::get('ConfigItems');
		$lowestYear = $ConfigItems->value('lowest_year');
		$currentYear = date("Y");

		for ($i=$currentYear; $i >= $lowestYear; $i--) {
			$yearOptions[$i] = $i;
		}

		return $yearOptions;
	}
}
