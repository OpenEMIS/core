<?php
namespace CustomField\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;

class CustomGroupsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsToMany('CustomModules', [
			'className' => 'CustomField.CustomModules',
			'joinTable' => 'custom_group_modules',
			'foreignKey' => 'custom_group_id',
			'targetForeignKey' => 'custom_module_id'
		]);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->addField('custom_modules', [
			'type' => 'chosen_select',
			'fieldNameKey' => 'custom_modules',
			'fieldName' => $this->alias() . '.custom_modules._ids',
			'placeholder' => __('Select Modules'),
			'order' => 2,
			'visible' => true
		]);
	}

	public function indexBeforePaginate(Event $event, Table $model, array $options) {
		$options['contain'][] = 'CustomModules';
		return $options;
	}

	public function viewBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain[] = 'CustomModules';
		return compact('query', 'contain');
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($moduleOptions) = array_values($this->getSelectOptions());

		$this->fields['custom_modules']['options'] = $moduleOptions;

		$this->ControllerAction->setFieldOrder('name', 1);
		$this->ControllerAction->setFieldOrder('custom_modules', 2);
	}

	public function addEditBeforePatch(Event $event, Entity $entity, array $data, array $options) {
		//Required by patchEntity for associated data
		$options['associated'] = ['CustomModules'];
		return compact('entity', 'data', 'options');
	}

	public function editBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain[] = 'CustomModules';
		return compact('query', 'contain');
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$moduleOptions = $this->CustomModules->find('list')->toArray();
		$selectedModule = key($moduleOptions);

		return compact('moduleOptions', 'selectedModule');
	}
}
