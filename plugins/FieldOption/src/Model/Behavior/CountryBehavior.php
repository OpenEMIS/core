<?php
namespace FieldOption\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;

class CountriesBehavior extends DisplayBehavior {
	private $fieldOptionName;
	private $associations = ['identity_type_id' => ['plugin' => 'FieldOption', 'code' => 'IdentityTypes', 'contain' => 'IdentityTypes']];

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
		$events['ControllerAction.Model.addEdit.beforeAction'] = 'addEditBeforeAction';
		$events['ControllerAction.Model.view.beforeAction'] = 'viewBeforeAction';
		
		return $events;
	}

	public function initialize(array $config) {
		parent::initialize($config);	
		$this->fieldOptionName = $config['fieldOptionName'];
	}	


	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {		
		parent::indexBeforeAction($event, $query, $settings);

		$table = TableRegistry::get($this->fieldOptionName);
		$query = $table->find();
		
		$containsArray = [];
		foreach($this->associations as $contains){
			$containsArray[] = $contains['contain'];
		}
		$query->contain($containsArray);
		
		return $query;
	}	

}
