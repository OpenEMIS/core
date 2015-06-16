<?php
namespace CustomField\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;

class CustomFormsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomGroups', ['className' => 'CustomField.CustomGroups']);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->belongsToMany('CustomFields', [
			'className' => 'CustomField.CustomFields',
			'joinTable' => 'custom_form_fields',
			'foreignKey' => 'custom_form_id',
			'targetForeignKey' => 'custom_field_id'
		]);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->addField('custom_fields', [
			'type' => 'chosen_select',
			'fieldNameKey' => 'custom_fields',
			'fieldName' => $this->alias() . '.custom_fields._ids',
			'placeholder' => __('Select Fields'),
			'order' => 2,
			'visible' => true
		]);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'CustomField.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function indexBeforePaginate(Event $event, Table $model, array $options) {
		list($groups, $selectedGroup, $modules, $selectedModule) = array_values($this->getSelectOptions());

		$groupOptions = [];
        foreach ($groups as $key => $group) {
            $groupOptions['group=' . $key] = $group;
        }
		$moduleOptions = [];
        foreach ($modules as $key => $module) {
            $moduleOptions['module=' . $key] = $module;
        }
        $this->controller->set(compact('groupOptions', 'selectedGroup', 'moduleOptions', 'selectedModule'));

		$options['conditions'][] = [
        	$model->aliasField('custom_group_id') => $selectedGroup,
        	$model->aliasField('custom_module_id') => $selectedModule
        ];
		$options['contain'][] = 'CustomFields';

		return $options;
	}

	public function viewBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain[] = 'CustomFields';
		return compact('query', 'contain');
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($groupOptions, , $moduleOptions) = array_values($this->getSelectOptions());

		$this->fields['custom_group_id']['type'] = 'select';
		$this->fields['custom_group_id']['options'] = $groupOptions;
		$this->fields['custom_module_id']['type'] = 'select';
		$this->fields['custom_module_id']['options'] = $moduleOptions;
		$fieldOptions = $this->CustomFields->find('list')->toArray();
		$this->fields['custom_fields']['options'] = $fieldOptions;

		$this->ControllerAction->setFieldOrder('name', 1);
		$this->ControllerAction->setFieldOrder('custom_fields', 2);
		$this->ControllerAction->setFieldOrder('custom_group_id', 3);
		$this->ControllerAction->setFieldOrder('custom_module_id', 4);
	}

	public function addEditBeforePatch(Event $event, Entity $entity, array $data, array $options) {
		//Required by patchEntity for associated data
		$options['associated'] = ['CustomFields'];
		return compact('entity', 'data', 'options');
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, $selectedGroup, , $selectedModule) = array_values($this->getSelectOptions());

		$entity->custom_group_id = $selectedGroup;
		$entity->custom_module_id = $selectedModule;

		return $entity;
	}

	public function editBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain[] = 'CustomFields';
		return compact('query', 'contain');
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$query = $this->request->query;

		$groupOptions = $this->CustomGroups->find('list')->toArray();
		$selectedGroup = isset($query['group']) ? $query['group'] : key($groupOptions);
		
		$customModules = $this->CustomGroups->find('all', ['keyField' => 'id', 'valueField' => 'name'])->where([$this->CustomGroups->aliasField('id') => $selectedGroup])->contain('CustomModules')->first()->custom_modules;
		$moduleOptions = [];
		foreach ($customModules as $key => $customModule) {
			$moduleOptions[$customModule->id] = $customModule->name;
		}
		$selectedModule = isset($query['module']) ? $query['module'] : key($moduleOptions);

		return compact('groupOptions', 'selectedGroup', 'moduleOptions', 'selectedModule');
	}
}
