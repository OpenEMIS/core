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
	private $parentFieldOptions;
	private $fieldOptionName;
	private $parentFieldOptionList;

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
		$events['ControllerAction.Model.addEdit.beforeAction'] = 'addEditBeforeAction';
		$events['ControllerAction.Model.view.beforeAction'] = 'viewBeforeAction';
		$events['ControllerAction.Model.onBeforeDelete'] = 'onBeforeDelete';
		$events['ControllerAction.Model.delete.beforeAction'] = 'deleteBeforeAction';
		$events['ControllerAction.Model.delete.onInitialize'] = 'deleteOnInitialize';
		return $events;
	}

	public function initialize(array $config) {
		$this->fieldOptionName = $config['fieldOptionName'];
		$this->parentFieldOptionList = $config['parentFieldOptionList'];

		$parentFieldOptionInfo = $this->parentFieldOptionList[$this->fieldOptionName];
		$this->parentFieldOptionInfo = $parentFieldOptionInfo;	

		if(!empty($parentFieldOptionInfo['parentModel']) && !empty($parentFieldOptionInfo['foreignKey'])) {
			$parentFieldOptionTable = TableRegistry::get($parentFieldOptionInfo['parentModel']);
			$parentFieldOptions = $parentFieldOptionTable->find('list')->where([$parentFieldOptionTable->aliasField('visible') => 1])->toArray();
			$this->parentFieldOptions = $parentFieldOptions;
		}
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

		$table = TableRegistry::get($this->fieldOptionName);
		$query = $table->find();

		//check for additional contains
		$count = count($this->parentFieldOptionList[$this->fieldOptionName]['associations']);
		if($count > 0) {
			$containsArray = [];
			foreach($this->parentFieldOptionList[$this->fieldOptionName]['associations'] as $contains){
				$containsArray[] = $contains['contain'];
			}
			$query->contain($containsArray);
		}

		$selectedParentFieldOption = $this->_table->ControllerAction->getVar('selectedParentFieldOption');
		$foreignKey = $this->_table->ControllerAction->getVar('foreignKey');
		if (!empty($selectedParentFieldOption) && !empty($foreignKey)) {	
			$query->where([$foreignKey => $selectedParentFieldOption]);
		}	

		$this->displayFields($table, 'index');

		return $query;
	}	

	public function viewBeforeAction(Event $event) {
		$table = TableRegistry::get($this->fieldOptionName);
		//$this->displayFields();
		return $table;
	}

	public function addEditBeforeAction(Event $event) {
		$table = TableRegistry::get($this->fieldOptionName);
		$this->displayFields();
		return $table;
	}

	public function displayFields(){
		$table = TableRegistry::get($this->fieldOptionName);
		$selectedParentFieldOption = $this->_table->queryString('parent_field_option_id', $this->parentFieldOptions);
		$selectedParentFieldOptionValue = $this->parentFieldOptions[$selectedParentFieldOption];

		$defaultFieldOrder = [];
		$fieldOrder = 1000;
		$fieldOrderExcluded = 5000;
		$selectedOption = $this->_table->ControllerAction->getVar('selectedOption');
		
		if(!empty($selectedOption)){
			$this->_table->ControllerAction->field('field_option_id', 
														['type' => 'readonly', 
														 'visible' => ['index' => false, 'add' => true, 'edit' => true, 'view' => true], 
														 'model' => $table->alias(), 
														 'value' => $selectedOption,
														 'className' => $this->parentFieldOptionInfo['parentModel'], 
														 'attr' => ['value' => $this->fieldOptionName],
														 'order' => $fieldOrder]);
			$defaultFieldOrder[] = 'field_option_id';
			$fieldOrder++;
		}

		if(!empty($this->parentFieldOptionInfo['foreignKey'])){
			$this->_table->ControllerAction->field($this->parentFieldOptionInfo['foreignKey'], 
														[
														'type' => 'readonly', 
														'visible' => ['index' => false, 'add' => true, 'edit' => true, 'view' => true], 
														'model' => $table->alias(), 
														'value' => $selectedParentFieldOption,
														 'className' => $this->fieldOptionName, 
														 'attr' => ['value' => $selectedParentFieldOptionValue],
														'order' => $fieldOrder

														 ]);
			$defaultFieldOrder[] = $this->parentFieldOptionInfo['foreignKey'];
			$fieldOrder++;
		}

		

		$columns = $table->schema()->columns();
		foreach ($columns as $key => $attr) {
			$this->_table->ControllerAction->field($attr, ['model' => $table->alias()]); 
			$defaultFieldOrder[] = $attr;
			
			//check whether column has another set of values to be retrieved
			if(array_key_exists($attr, $this->parentFieldOptionList[$this->fieldOptionName]['associations'])){
				$attrFieldOptionPluginName = $this->parentFieldOptionList[$this->fieldOptionName]['associations'][$attr]['plugin']; 
				$attrFieldOptionCodeName = $this->parentFieldOptionList[$this->fieldOptionName]['associations'][$attr]['code']; 

				$attrFieldOptionId = TableRegistry::get('FieldOption.FieldOptions')->find()
										->where(['plugin' => $attrFieldOptionPluginName])
										->andWhere(['code' => $attrFieldOptionCodeName])
										->first();
									
								
				$options = TableRegistry::get('FieldOption.FieldOptionValues')->find('list')->where(['field_option_id' => $attrFieldOptionId->id])->toArray();
				$this->_table->ControllerAction->field($attr, ['type' => 'select', 'options' => [0 => '-- Select Option --']+$options, 'visible' => true, 'order' => $fieldOrder]);
				$fieldOrder++;
			}
			
			
			if(!in_array($attr, $this->excludeFieldList) && ($attr != $this->parentFieldOptionInfo['foreignKey'])) {
				$this->_table->ControllerAction->field($attr, ['visible' => true, 'order' => $fieldOrder]);
				$fieldOrder++;
			} else if($attr != $this->parentFieldOptionInfo['foreignKey']){
				$this->_table->ControllerAction->field($attr, ['visible' => ['index' => false, 'edit' => false, 'add' => false, 'view' => true], 'order' => $fieldOrderExcluded]);
				$fieldOrderExcluded++;
			}
		}	
		
		$this->_table->ControllerAction->setFieldOrder($defaultFieldOrder); 
	}

	public function onGetIdentityTypeId(Event $event, Entity $entity) {
		return $entity->identity_type->name;
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$table = TableRegistry::get($this->fieldOptionName);
		$entity = $table->get($id);
		return $table->delete($entity);
	}

	public function deleteBeforeAction(Event $event, ArrayObject $settings) {
		$settings['deleteStrategy'] = 'transfer';
		$settings['model'] = $this->fieldOptionName;
	}
	
	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $options) {
		$table = TableRegistry::get($this->fieldOptionName);
		$foreignKey = $this->parentFieldOptionList[$this->fieldOptionName]['foreignKey'];
		
		if(!empty($foreignKey)) {
			$selectedParentFieldOption = $this->_table->queryString('parent_field_option_id', $this->parentFieldOptions);
			$availFieldOptions = $table->find()->where([$foreignKey => $selectedParentFieldOption])->count();

			$query->where([$foreignKey => $selectedParentFieldOption]);

		} else {
			$availFieldOptions = $table->find()->count();
		}

		if($availFieldOptions == 1) {
			$this->_table->Alert->warning('general.notTransferrable');
			$event->_table->stopPropagation();
			return $this->_table->controller->redirect($this->ControllerAction->url('index'));
		}

		return $query;
	}
}
