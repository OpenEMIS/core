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
	private $_fieldOrder = [
		'institution_id', 'parent_id', 'infrastructure_level_id', 'code', 'name', 'infrastructure_type_id', 'size', 'infrastructure_ownership_id', 'year_acquired', 'year_disposed', 'infrastructure_condition_id', 'comment'
	];

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Parents', ['className' => 'Institution.InstitutionInfrastructures']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('Levels', ['className' => 'Infrastructure.InfrastructureLevels', 'foreignKey' => 'infrastructure_level_id']);
		$this->belongsTo('Types', ['className' => 'Infrastructure.InfrastructureTypes', 'foreignKey' => 'infrastructure_type_id']);
		$this->belongsTo('InfrastructureOwnerships', ['className' => 'FieldOption.InfrastructureOwnerships']);
		$this->belongsTo('InfrastructureConditions', ['className' => 'FieldOption.InfrastructureConditions']);
		$this->hasMany('ChildInfrastructures', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'parent_id']);

		$this->addBehavior('CustomField.Record', [
			'fieldKey' => 'infrastructure_custom_field_id',
			'tableColumnKey' => 'infrastructure_custom_table_column_id',
			'tableRowKey' => 'infrastructure_custom_table_row_id',
			'fieldClass' => ['className' => 'Infrastructure.InfrastructureCustomFields'],
			'formKey' => 'infrastructure_custom_form_id',
			'filterKey' => 'infrastructure_custom_filter_id',
			'formFieldClass' => ['className' => 'Infrastructure.InfrastructureCustomFormsFields'],
			'formFilterClass' => ['className' => 'Infrastructure.InfrastructureCustomFormsFilters'],
			'recordKey' => 'institution_infrastructure_id',
			'fieldValueClass' => ['className' => 'Infrastructure.InfrastructureCustomFieldValues', 'foreignKey' => 'institution_infrastructure_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'Infrastructure.InfrastructureCustomTableCells', 'foreignKey' => 'institution_infrastructure_id', 'dependent' => true, 'cascadeCallbacks' => true]
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
		return $event->subject()->Html->link($entity->code, [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => $this->alias,
			'index',
			'parent' => $entity->id
		]);
	}

	public function beforeAction(Event $event) {
		// Add breadcrumb
		$action = $this->ControllerAction->action();
		if ($action == 'index' || $action == 'edit') {
			$toolbarElements = [
	            ['name' => 'Institution.Infrastructure/breadcrumb', 'data' => [], 'options' => []]
	        ];
			$this->controller->set('toolbarElements', $toolbarElements);
		}

		$this->ControllerAction->field('parent_id');
		$this->ControllerAction->field('year_acquired');
		$this->ControllerAction->field('year_disposed');

		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event) {
		$parentId = $this->request->query('parent');
		if (!is_null($parentId)) {
			$crumbs = $this->findPath(['for' => $parentId]);
			$this->controller->set('crumbs', $crumbs);
		}

		$this->fields['parent_id']['visible'] = false;
		$this->fields['infrastructure_level_id']['visible'] = false;
		$this->fields['size']['visible'] = false;
		$this->fields['infrastructure_ownership_id']['visible'] = false;
		$this->fields['year_acquired']['visible'] = false;
		$this->fields['year_disposed']['visible'] = false;
		$this->fields['infrastructure_condition_id']['visible'] = false;
		$this->fields['comment']['visible'] = false;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$parentId = $this->request->query('parent');
		if (!is_null($parentId)) {
			$query->where([$this->aliasField('parent_id') => $parentId]);
		} else {
			$query->where([$this->aliasField('parent_id IS NULL')]);
		}
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		foreach ($this->fields as $field => $attr) {
			if ($field == 'parent_id' && $attr['type'] != 'hidden') {
				$this->fields[$field]['type'] = 'hidden';
				$parentId = $entity->parent_id;
				if (!empty($parentId)) {
					$list = $this->findPath(['for' => $parentId, 'withLevels' => true]);
				} else {
					$list = [];
				}

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
			}
		}
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('infrastructure_level_id');
		$this->ControllerAction->field('infrastructure_type_id');
		$this->ControllerAction->field('infrastructure_ownership_id', ['type' => 'select']);
		$this->ControllerAction->field('infrastructure_condition_id', ['type' => 'select']);

		$this->fields['infrastructure_level_id']['select'] = false;

		$session = $this->request->session();
		$institutionId = $session->read('Institution.Institutions.id');
		$infrastructureLevelId = $this->request->query('level');
		$parentId = $this->request->query('parent');
		$this->fields['code']['attr']['default'] = $this->getAutogenerateCode($institutionId, $infrastructureLevelId, $parentId);
	}

	private function getAutogenerateCode($institutionId, $infrastructureLevelId, $parentId) {
		// getting suffix of code by counting 
		$indexData = $this->find()
			->where([
				$this->aliasField('institution_id') => $institutionId,
				$this->aliasField('infrastructure_level_id') => $infrastructureLevelId,
			])
			->count();
		$indexData += 1; // starts counting from 1
		$indexData = strval($indexData);

		// if 1 character prepend '0'
		$indexData = (strlen($indexData) == 1)? '0'.$indexData: $indexData;
		if (empty($parentId)) {
			// hasParent
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
			// hasParent
			$parentData = $this->find()
				->where([
					$this->aliasField($this->primaryKey()) => $parentId
				])
				->first()
				;

			if (!empty($parentData)) {
				return $parentData->code . $indexData;
			} else {
				// no parent data just return the 
				return $indexData;
			}
		}
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		list(, $selectedLevel) = array_values($this->getLevelOptions());
		$entity->infrastructure_level_id = $selectedLevel;
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['level'] = $entity->infrastructure_level_id;
	}

	public function onGetConvertOptions(Event $event, Entity $entity, Query $query) {
		$query->where([
			$this->aliasField('institution_id') => $entity->institution_id,
			$this->aliasField('parent_id') => $entity->parent_id
		]);	
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$entity = $this->get($id);
		$transferTo = $this->request->data['transfer_to'];
		$transferFrom = $id;

		if (empty($transferTo) && $this->ControllerAction->hasAssociatedRecords($this, $entity)) {
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
			}
		}

		return $attr;
	}

	public function onUpdateFieldInfrastructureLevelId(Event $event, array $attr, $action, Request $request) {
		list($levelOptions, $selectedLevel) = array_values($this->getLevelOptions());

		$attr['options'] = $levelOptions;
		$attr['onChangeReload'] = 'changeLevel';

		$submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
		if ($submit != 'save') {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('custom_field_values', $request->data[$this->alias()])) {
					unset($request->data[$this->alias()]['custom_field_values']);
				}
				if (array_key_exists('custom_table_cells', $request->data[$this->alias()])) {
					unset($request->data[$this->alias()]['custom_table_cells']);
				}
			}
		}

		return $attr;
	}

	public function onUpdateFieldInfrastructureTypeId(Event $event, array $attr, $action, Request $request) {
		$selectedLevel = $request->query('level');
		$typeOptions = $this->Types
			->find('list')
			->find('visible')
			->find('order')
			->where([$this->Types->aliasField('infrastructure_level_id') => $selectedLevel])
			->toArray();

		$attr['options'] = $typeOptions;
		return $attr;
	}

	public function onUpdateFieldYearAcquired(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->getYearOptionsByConfig();
		return $attr;
	}

	public function onUpdateFieldYearDisposed(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->getYearOptionsByConfig();
		return $attr;
	}

	public function addEditOnChangeLevel(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['level']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('infrastructure_level_id', $request->data[$this->alias()])) {
					$selectedLevel = $request->data[$this->alias()]['infrastructure_level_id'];
					$request->query['level'] = $selectedLevel;
					$entity->infrastructure_level_id = $selectedLevel;
				}
			}
		}
	}

	public function runRecover() {
		$script = 'tree';

		$consoleDir = ROOT . DS . 'bin' . DS;
		$cmd = sprintf("%scake %s %s", $consoleDir, $script, $this->registryAlias());
		$nohup = '%s >> %slogs/'.$script.'.log & echo $!';
		$shellCmd = sprintf($nohup, $cmd, ROOT.DS);
		Log::write('debug', $shellCmd);
		exec($shellCmd);
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

	public function getLevelOptions() {
		$parentId = $this->request->query('parent');
		$levelQuery = $this->Levels->find('list');
		if (is_null($parentId)) {
			$levelQuery->where([$this->Levels->aliasField('parent_id') => 0]);
		} else {
			$levelId = $this->get($parentId)->infrastructure_level_id;
			$levelQuery->where([$this->Levels->aliasField('parent_id') => $levelId]);
		}
		$levelOptions = $levelQuery->toArray();
		$selectedLevel = $this->queryString('level', $levelOptions);
		$this->advancedSelectOptions($levelOptions, $selectedLevel);

		return compact('levelOptions', 'selectedLevel');
	}

	public function getYearOptionsByConfig() {
		$ConfigItems = TableRegistry::get('ConfigItems');
		$lowestYear = $ConfigItems->value('lowest_year');
		$currentYear = date("Y");
		
		for($i=$currentYear; $i >= $lowestYear; $i--){
			$yearOptions[$i] = $i;
		}

		return $yearOptions;
	}
}
