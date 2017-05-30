<?php
namespace FieldOption\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class DisplayBehavior extends Behavior {
	private $excludeFieldList = ['modified_user_id', 'modified', 'created_user_id', 'created'];
	private $fieldOptionName;
	protected $defaultFieldOrder;

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
		$events['ControllerAction.Model.addEdit.beforeAction'] = 'addEditBeforeAction';
		$events['ControllerAction.Model.view.beforeAction'] = 'viewBeforeAction';
		return $events;
	}

	public function initialize(array $config) {
		$this->fieldOptionName = $config['fieldOptionName'];
		$this->defaultFieldOrder = $this->_table->defaultFieldOrder;
	}

	public function indexBeforeAction(Event $event, ArrayObject $settings) {
        $query = $settings['query'];
		$table = TableRegistry::get($this->fieldOptionName);
		$query = $table->find();
		$this->displayFields($table);
		return $query;
	}

	public function viewBeforeAction(Event $event) {
		$table = TableRegistry::get($this->fieldOptionName);
		$this->displayFields($table);
		return $table;
	}

	public function addEditBeforeAction(Event $event) {
		$table = TableRegistry::get($this->fieldOptionName);
		$this->displayFields($table);
		return $table;
	}

	public function displayFields($table) {
		$table = TableRegistry::get($this->fieldOptionName);
		/**
		 * ugly hack
		 */
		$table->ControllerAction = $this->_table->ControllerAction;
		/**
		 * ugly hack ends
		 */

		$columns = $table->schema()->columns();
		$fieldOrder = 1000;
		$fieldOrderExcluded = 9999;
		foreach ($columns as $key => $attr) {
			$this->_table->ControllerAction->field($attr, ['model' => $table->alias()]);
			$this->defaultFieldOrder[] = $attr;

			if(!in_array($attr, $this->excludeFieldList)) {
				$this->_table->ControllerAction->field($attr, ['visible' => true,
					'order' => $fieldOrder,
					'model' => $table->alias(), 'className' => $this->fieldOptionName]);
				$fieldOrder++;
			} else {
				$this->_table->ControllerAction->field($attr, ['visible' => ['index' => false, 'edit' => false, 'add' => false, 'view' => true], 'order' => $fieldOrderExcluded, 'model' => $table->alias(), 'className' => $this->fieldOptionName]);
				$fieldOrderExcluded++;
			}
		}
		$this->_table->ControllerAction->setFieldOrder($this->defaultFieldOrder);
	}

}
