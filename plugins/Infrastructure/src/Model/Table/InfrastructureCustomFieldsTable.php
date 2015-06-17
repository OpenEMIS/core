<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldsTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;

class InfrastructureCustomFieldsTable extends CustomFieldsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Levels', ['className' => 'Infrastructure.InfrastructureLevels', 'foreignKey' => 'infrastructure_level_id']);
		$this->hasMany('CustomFieldOptions', ['className' => 'Infrastructure.InfrastructureCustomFieldOptions', 'foreignKey' => 'infrastructure_custom_field_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'Infrastructure.InfrastructureCustomTableColumns', 'foreignKey' => 'infrastructure_custom_field_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'Infrastructure.InfrastructureCustomTableRows', 'foreignKey' => 'infrastructure_custom_field_id', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Infrastructure.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function addEditBeforeAction(Event $event) {
		parent::addEditBeforeAction($event);
		//Setup fields
		$levelOptions = $this->Levels->find('list')->toArray();

		$this->fields['infrastructure_level_id']['type'] = 'select';
		$this->fields['infrastructure_level_id']['options'] = $levelOptions;

		$this->setFieldOrder();
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		parent::addOnInitialize($event, $entity);
		$query = $this->request->query;
		$levelOptions = $this->Levels->find('list')->toArray();
		$selectedLevel = isset($query['level']) ? $query['level'] : key($levelOptions);
		
		$entity->infrastructure_level_id = $selectedLevel;

		return $entity;
	}

	public function setFieldOrder() {
		$order = 1;
		$this->ControllerAction->setFieldOrder('infrastructure_level_id', $order++);
		$this->ControllerAction->setFieldOrder('field_type', $order++);
		$this->ControllerAction->setFieldOrder('name', $order++);
		$this->ControllerAction->setFieldOrder('is_mandatory', $order++);
		$this->ControllerAction->setFieldOrder('is_unique', $order++);
	}
}
