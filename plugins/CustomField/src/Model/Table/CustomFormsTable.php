<?php
namespace CustomField\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class CustomFormsTable extends AppTable {
	use OptionsTrait;

	private $_fieldOrder = ['custom_module_id', 'name', 'description', 'custom_fields'];
	private $_contain = ['CustomFilters', 'CustomFields'];
	private $filterClass = [
		'className' => 'FieldOption.FieldOptionValues',
		'joinTable' => 'custom_form_filters',
		'foreignKey' => 'custom_form_id',
		'targetForeignKey' => 'custom_filter_id',
		'through' => 'CustomField.CustomFormsFilters',
		'dependent' => true
	];

	public function initialize(array $config) {
		if (array_key_exists('custom_filter', $config)) {
			$this->filterClass = array_merge($this->filterClass, $config['custom_filter']);
		}
		
		parent::initialize($config);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->belongsToMany('CustomFilters', $this->filterClass);
		$this->belongsToMany('CustomFields', [
			'className' => 'CustomField.CustomFields',
			'joinTable' => 'custom_forms_fields',
			'foreignKey' => 'custom_form_id',
			'targetForeignKey' => 'custom_field_id',
			'through' => 'CustomField.CustomFormsFields',
			'dependent' => true
		]);
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if (isset($entity->apply_to_all) && $entity->apply_to_all == 1) {			
			$customFormIds = $this
				->find('list', ['keyField' => 'id', 'valueField' => 'id'])
				->where([
					$this->aliasField('custom_module_id') => $entity->custom_module_id
				])
				->toArray();

			$CustomFormsFilters = TableRegistry::get($this->filterClass['through']);
			$CustomFormsFilters->deleteAll([
				'OR' => [
					[
						$CustomFormsFilters->aliasField($this->filterClass['foreignKey'] . ' IN') => $customFormIds,
						$CustomFormsFilters->aliasField($this->filterClass['targetForeignKey']) => 0
					],
					$CustomFormsFilters->aliasField($this->filterClass['foreignKey']) => $entity->id
				]
			]);

			$filterData = [
				$this->filterClass['foreignKey'] => $entity->id,
				$this->filterClass['targetForeignKey'] => 0
			];
			$filterEntity = $CustomFormsFilters->newEntity($filterData);

			if ($CustomFormsFilters->save($filterEntity)) {
			} else {
				$CustomFormsFilters->log($filterEntity->errors(), 'debug');
			}
		}
	}

	public function beforeAction(Event $event) {
		if ($this->action == 'index') {
			$this->initFields();
		}
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'CustomField.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		list($moduleOptions, $selectedModule) = array_values($this->_getSelectOptions());
        $this->controller->set(compact('moduleOptions', 'selectedModule'));

		$options['conditions'][] = [
        	$this->aliasField('custom_module_id') => $selectedModule
        ];
        $options['contain'] = array_merge($options['contain'], $this->_contain);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain($this->_contain);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->initFields();
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->initFields();
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list($moduleOptions, $selectedModule, $applyToAllOptions, $selectedApplyToAll) = array_values($this->_getSelectOptions());

		$entity->custom_module_id = $selectedModule;
		$entity->apply_to_all = $selectedApplyToAll;
		$this->request->query['apply_to_all'] = $selectedApplyToAll;

		return $entity;
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		list(, , $applyToAllOptions, $selectedApplyToAll) = array_values($this->_getSelectOptions());

		$CustomFormsFilters = TableRegistry::get($this->filterClass['through']);
		$results = $CustomFormsFilters
			->find()
			->where([
				$CustomFormsFilters->aliasField($this->filterClass['foreignKey']) => $entity->id,
				$CustomFormsFilters->aliasField($this->filterClass['targetForeignKey']) => 0		
			])
			->all();

		if ($results->isEmpty()) {
			$selectedApplyToAll = 0;
		}
		$entity->apply_to_all = $selectedApplyToAll;
		$this->request->query['apply_to_all'] = $selectedApplyToAll;

		return $entity;
	}

	public function onGetApplyToAll(Event $event, Entity $entity) {
		if (sizeof($entity->custom_filters) > 0) {
			$value = __('No');
		} else {
			$CustomFormsFilters = TableRegistry::get($this->filterClass['through']);
			$results = $CustomFormsFilters
				->find()
				->where([
					$CustomFormsFilters->aliasField($this->filterClass['foreignKey']) => $entity->id,
					$CustomFormsFilters->aliasField($this->filterClass['targetForeignKey']) => 0
				])
				->all();

			if ($results->isEmpty()) {
				$value = __('No');
			} else {
				$value = __('Yes');
			}
		}

		return $value;
    }

	public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, $request) {
		list($moduleOptions, $selectedModule) = array_values($this->_getSelectOptions());

		$attr['options'] = $moduleOptions;
		$attr['onChangeReload'] = true;

		return $attr;
	}

	public function onUpdateFieldApplyToAll(Event $event, array $attr, $action, $request) {
		$attr['options'] = $this->getSelectOptions('general.yesno');
		$attr['onChangeReload'] = true;

		return $attr;
	}

	public function onUpdateFieldCustomFilters(Event $event, array $attr, $action, $request) {
		$moduleOptions = $this->fields['custom_module_id']['options'];
		$applyToAllOptions = $this->fields['apply_to_all']['options'];

		if ($action == 'index' || $action == 'view') {
			$selectedModule = $this->queryString('module', $moduleOptions);
			$selectedApplyToAll = 0;
		} else {
			$selectedModule = key($moduleOptions);
			$selectedModule = $this->queryString('module', $moduleOptions);
			$selectedApplyToAll = key($applyToAllOptions);

			if ($request->is(['get'])) {
				$selectedModule = $this->request->query('module');
				$selectedApplyToAll = $this->request->query('apply_to_all');
			} else if ($this->request->is(['post', 'put'])) {
				if (array_key_exists($this->alias(), $request->data)) {
					if (array_key_exists('custom_module_id', $request->data[$this->alias()])) {
						$selectedModule = $request->data[$this->alias()]['custom_module_id'];
					}
					if (array_key_exists('apply_to_all', $request->data[$this->alias()])) {
						$selectedApplyToAll = $request->data[$this->alias()]['apply_to_all'];
					}
				}
			}
		}

		if (!is_null($selectedModule)) {
			$customModule = $this->CustomModules->get($selectedModule);
			$filter = $customModule->filter;

			if (empty($filter)) {
				$this->fields['apply_to_all']['visible'] = false;
				$attr['visible'] = false;
			} else {
				$this->fields['apply_to_all']['visible'] = true;

				if ($selectedApplyToAll == 1) {
					$attr['visible'] = false;

					$this->_fieldOrder = ['custom_module_id', 'apply_to_all', 'name', 'description', 'custom_fields'];
				} else {
					$modelAlias = $this->ControllerAction->getModel($filter)['model'];
					$labelText = Inflector::underscore(Inflector::singularize($modelAlias));
					$filterOptions = TableRegistry::get($filter)->getList()->toArray();

					$attr['placeholder'] = __('Select ') . __(Inflector::humanize($labelText));
					$attr['options'] = $filterOptions;
					$attr['attr']['label'] = __(Inflector::humanize($labelText));
					$attr['visible'] = true;

					$this->_fieldOrder = ['custom_module_id', 'apply_to_all', 'custom_filters', 'name', 'description', 'custom_fields'];
				}
			}
		}

		return $attr;
	}

	public function onUpdateFieldCustomFields(Event $event, array $attr, $action, $request) {
		$customFieldOptions = $this->CustomFields
			->find('list')
			->toArray();
		$attr['options'] = $customFieldOptions;

		return $attr;
	}

	public function initFields() {
		$this->ControllerAction->field('custom_module_id');
		$this->ControllerAction->field('apply_to_all', [
			'type' => 'select',
			'visible' => ['index' => true, 'view' => true, 'edit' => false]
		]);
		$this->ControllerAction->field('custom_filters', [
			'type' => 'chosenSelect',
			'visible' => ['index' => true, 'view' => true, 'edit' => false]
		]);
		$this->ControllerAction->field('custom_fields', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Fields')
		]);
	}

	public function _getSelectOptions() {
		// Return all required options and their key
		$query = $this->request->query;
		// Exclude three main module
		$moduleOptions = $this->CustomModules
			->find('list')
			->find('visible')
			->where([
				$this->CustomModules->aliasField('parent_id !=') => 0
			])
			->toArray();
		$selectedModule = isset($query['module']) ? $query['module'] : key($moduleOptions);
		// $selectedModule = $this->queryString('module', $moduleOptions);

		$applyToAllOptions = $this->getSelectOptions('general.yesno');
		$selectedApplyToAll = !is_null($this->request->query('apply_to_all')) ? $this->request->query('apply_to_all') : key($applyToAllOptions);

		return compact('moduleOptions', 'selectedModule', 'applyToAllOptions', 'selectedApplyToAll');
	}
}
