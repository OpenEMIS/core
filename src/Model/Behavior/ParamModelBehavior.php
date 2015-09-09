<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;

class ParamModelBehavior extends Behavior {
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.beforeAction'] = 'beforeAction';
		return $events;
	}

	// public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
	// 	pr('beforeSave in behvaiour');
	// 	// $config = $this->config();
	// 	// foreach ($config as $date => $year) {
	// 	// 	if ($entity->has($date) && !empty($entity->$date)) {
	// 	// 		$entity->$year = date('Y', strtotime($entity->$date));
	// 	// 	}
	// 	// }
	// }

	public function beforeAction(Event $event) {
		pr('hi there ladies'); die;

		foreach($defaultFieldOrder as $each) {
				$this->ControllerAction->field($each, ['visible' => false]);
			}

			$table = TableRegistry::get($currentfieldOption->plugin.'.'.$currentfieldOption->code);
			$columns = $table->schema()->columns();
			$fieldOrder = 1000;
			$fieldOrderExcluded = 5000;
			foreach ($columns as $key => $attr) {
				$this->fields[$attr]['model'] = $table->alias();
				$defaultFieldOrder[] = $attr;
				if(!in_array($attr, $this->excludeFieldList)) {
					$this->ControllerAction->field($attr, ['visible' => true, 'order' => $fieldOrder]);
					$fieldOrder++;
				} else {
					$this->ControllerAction->field($attr, ['visible' => ['index' => false, 'edit' => false, 'add' => false, 'view' => true], 'order' => $fieldOrderExcluded]);
					$fieldOrderExcluded++;
				}
			}	

			$defaultFieldOrder = ['parent_field_option_id']; 
			
		// $config = $this->config();
		// foreach ($config as $date => $year) {
		// 	$this->_table->fields[$year]['visible'] = false;
		// }
	}
}
