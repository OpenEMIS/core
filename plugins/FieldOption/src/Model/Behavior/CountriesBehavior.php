<?php
namespace FieldOption\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use ControllerAction\Model\Traits\UtilityTrait;

class CountriesBehavior extends DisplayBehavior {
	use UtilityTrait;

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
		
		$query->contain(['IdentityTypes']);
	
		//$this->displayAssociatedFields($table);

		//return $query;
	}	

	public function addEditBeforeAction(Event $event) {
		parent::indexBeforeAction($event);

		$table = TableRegistry::get($this->fieldOptionName);
		$this->displayAssociatedFields($table);
		return $table;
	}

	public function displayAssociatedFields($table){
		$columns = $table->schema()->columns();
		foreach ($columns as $key => $attr) {
			//check whether column has another set of values to be retrieved
			if(array_key_exists($attr, $this->associations)){
				
				$attrFieldOptionPluginName = $this->associations[$attr]['plugin']; 
				$attrFieldOptionCodeName = $this->associations[$attr]['code']; 
				

				$attrFieldOptionId = TableRegistry::get('FieldOption.FieldOptions')->find()
										->where(['plugin' => $attrFieldOptionPluginName])
										->andWhere(['code' => $attrFieldOptionCodeName])
										->first();
								
				$options = TableRegistry::get('FieldOption.FieldOptionValues')->find('list')->where(['field_option_id' => $attrFieldOptionId->id])->toArray();
				
				// if(array_key_exists($attr, $this->_table->fields)){
				// 	$fieldsArray = $this->_table->fields;
	   //          	unset($fieldsArray[$attr]);
	   //          	$this->_table->fields = $fieldsArray;	
				// }


				// $foreignKeyLabel = Inflector::humanize($attr);
				// if ($this->endsWith($attr, '_id') && $this->endsWith($foreignKeyLabel, ' Id')) {
				// 	$foreignKeyLabel = str_replace(' Id', '', $foreignKeyLabel);
				// }

				$this->_table->ControllerAction->field($attr, ['type' => 'select', 
															   'options' => [0 => '-- Select Option --']+$options, 
															   'visible' => true, 
															   'order' => 6000,
															   'model' => $table->alias(),
															   'className' => $this->fieldOptionName
															   ]);
			
				$this->_table->ControllerAction->setFieldOrder($attr); 
			}
			
		}	
	}

	public function onGetIdentityTypeId(Event $event, Entity $entity) {
		return $entity->identity_type->name;
	}

}
