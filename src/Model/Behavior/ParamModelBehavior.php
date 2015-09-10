<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;

class ParamModelBehavior extends Behavior {
	private $excludeFieldList = ['modified_user_id', 'modified', 'created_user_id', 'created'];
	private $parentFieldOptionInfo;
	private $parentFieldOptionList;
	private $parentFieldOptions;

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.beforeAction'] = 'beforeAction';
		$events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
		//$events['ControllerAction.Model.addEdit.beforeAction'] = 'addEditBeforeAction';
		return $events;
	}

	public function initialize(array $config) {
		$fieldOptionName = $config['fieldOptionName'];
		$this->parentFieldOptionList = $config['parentFieldOptionList'];

		$parentFieldOptionInfo = $this->parentFieldOptionList[$fieldOptionName];
		$this->parentFieldOptionInfo = $parentFieldOptionInfo;	

		if(!empty($parentFieldOptionInfo['parentModel']) && !empty($parentFieldOptionInfo['foreignKey'])) {
			$parentFieldOptionTable = TableRegistry::get($parentFieldOptionInfo['parentModel']);
			$parentFieldOptions = $parentFieldOptionTable->find('list')->where([$parentFieldOptionTable->aliasField('visible') => 1])->toArray();
			$this->parentFieldOptions = $parentFieldOptions;
		}

		// 		// $foreignKeyLabel = Inflector::humanize($foreignKey);
		// 		// if($this->endsWith($foreignKey, '_id')){
		// 		// 	$foreignKeyLabel = str_replace(' Id', '', $foreignKeyLabel);
		// 		// }

		// 		$this->ControllerAction->field($foreignKey, ['type' => 'readonly', 
		// 														  'visible' => ['index' => false, 'add' => true, 'edit' => true, 'view' => true], 
		// 														  'model' => $parentFieldOptionTable->alias(), 
		// 														  'value' => $parentFieldOptionValue,
		// 														  'className' => $parentFieldOptionInfo['parentModel'], 
		// 														  'attr' => ['value' => $parentFieldOptionValue]]);



			// $table = TableRegistry::get($fieldOptionName);
			// $columns = $table->schema()->columns();
			// $fieldOrder = 1000;
			// $fieldOrderExcluded = 5000;
			// foreach ($columns as $key => $attr) {
			// 	// pr($key);
			// 	// pr($attr);
			// 	$this->fields[$attr]['model'] = $table->alias();
			// 	$defaultFieldOrder[] = $attr;
			// 	if(!in_array($attr, $this->excludeFieldList)) {
			// 		$this->_table->ControllerAction->field($attr, ['visible' => true, 'order' => $fieldOrder]);
			// 		$fieldOrder++;
			// 	} else {
			// 		$this->_table->ControllerAction->field($attr, ['visible' => ['index' => false, 'edit' => false, 'add' => false, 'view' => true], 'order' => $fieldOrderExcluded]);
			// 		$fieldOrderExcluded++;
			// 	}
			// }	

		// pr('done'); die;
	}	

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {		
		$this->_table->controller->set('parentFieldOptions', $this->parentFieldOptions);
		$selectedParentFieldOption = $this->_table->queryString('parent_field_option_id', $this->parentFieldOptions);

		$this->_table->controller->set('selectedParentFieldOption', $selectedParentFieldOption);
		$this->_table->controller->set('foreignKey', $this->parentFieldOptionInfo['foreignKey']);
		
		$parentFieldOptionValue = $this->parentFieldOptions[$selectedParentFieldOption];
		$foreignKey =  $this->parentFieldOptionInfo['foreignKey'];

		$this->_table->ControllerAction->field('parent_field_option_id', [
			'type' => 'readonly', 
			'options' => $this->parentFieldOptions,
			'visible' => ['index' => false, 'view' => false, 'edit' => false]
		]);

		
	}	

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		pr('came');

		$selectedParentFieldOption = $this->_table->ControllerAction->getVar('selectedParentFieldOption');
		pr($selectedParentFieldOption);
		$foreignKey = $this->_table->ControllerAction->getVar('foreignKey');
		if (!empty($selectedParentFieldOption) && !empty($foreignKey)) {	
			$query->where([$foreignKey => $selectedParentFieldOption]);
		}	
		return $query;
	}	

	// public function addEditBeforeAction(Event $event) {
	// 	$this->_table->ControllerAction->field('testing', ['visible' => true, 'order' => 1]);
	// }	
}
