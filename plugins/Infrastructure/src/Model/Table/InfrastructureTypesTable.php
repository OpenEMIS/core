<?php
namespace Infrastructure\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\ORM\Entity;
//use Cake\ORM\Query;
use Cake\Event\Event;

class InfrastructureTypesTable extends AppTable {
	private $visible = array(
		1 => array('id' => 1, 'name' => 'Yes'),
		2 => array('id' => 0, 'name' => 'No')
	);

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('InfrastructureLevels', ['className' => 'Infrastructure.InfrastructureLevels']);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Infrastructure.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($levelOptions, , $visibleOptions) = array_values($this->getSelectOptions());

		$this->fields['infrastructure_level_id']['type'] = 'select';
		$this->fields['infrastructure_level_id']['options'] = $levelOptions;

		$this->fields['visible']['type'] = 'select';
		$this->fields['visible']['options'] = $visibleOptions;

		$this->setFieldOrder();
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, $selectedLevel, , $selectedVisible) = array_values($this->getSelectOptions());

		$entity->infrastructure_level_id = $selectedLevel;
		$entity->visible = $selectedVisible;

		return $entity;
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$query = $this->request->query;

		$levelOptions = $this->InfrastructureLevels->find('list')->toArray();
		$selectedLevel = isset($query['level']) ? $query['level'] : key($levelOptions);

		$visibleOptions = [];
		foreach ($this->visible as $key => $visible) {
			$visibleOptions[$visible['id']] = __($visible['name']);
		}
		$selectedVisible = key($visibleOptions);

		return compact('levelOptions', 'selectedLevel', 'visibleOptions', 'selectedVisible');
	}

	public function setFieldOrder() {
		$order = 1;
		$this->ControllerAction->setFieldOrder('infrastructure_level_id', $order++);
		$this->ControllerAction->setFieldOrder('name', $order++);
		$this->ControllerAction->setFieldOrder('visible', $order++);
	}
}
