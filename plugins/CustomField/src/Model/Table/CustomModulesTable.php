<?php
namespace CustomField\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Event\Event;

class CustomModulesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Parents', ['className' => 'CustomField.CustomModules', 'foreignKey' => 'parent_id']);
	}

	public function viewBeforeAction(Event $event) {
		$this->setFieldOrder();
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		if ($entity->id) {
			$this->fields['parent_id']['visible'] = $entity->parent_id == 0 ? false : true;
		}

		return $entity;
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($moduleOptions) = array_values($this->getSelectOptions());

		$this->fields['parent_id']['type'] = 'select';
		$this->fields['parent_id']['options'] = $moduleOptions;

		$this->setFieldOrder();
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		//edit
		if ($entity->id) {
			$this->fields['parent_id']['visible'] = $entity->parent_id == 0 ? false : true;
		}

		return $entity;
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$query = $this->request->query;

		$moduleOptions = $this->Parents->find('list')->toArray();
		$selectedModule = isset($query['module']) ? $query['module'] : key($moduleOptions);

		return compact('moduleOptions', 'selectedModule');
	}

	public function setFieldOrder() {
		$order = 1;
		$this->ControllerAction->setFieldOrder('parent_id', $order++);
		$this->ControllerAction->setFieldOrder('name', $order++);
	}
}
