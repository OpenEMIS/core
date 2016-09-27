<?php
namespace App\Model\Behavior;

use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class ActivityBehavior extends Behavior {
	private $_dateTypes = [
		'date'=>'Y-m-d',
		'time'=>'H:i:s',
		'datetime'=>'Y-m-d H:i:s'
	];

	public function initialize(array $config) {
		$this->_table->dateTypes = $this->_dateTypes;
	}
	

/******************************************************************************************************************
**
** Link/Map ControllerActionComponent events
**
******************************************************************************************************************/
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.beforeAction' => 'beforeAction'
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}


/******************************************************************************************************************
**
** CakePhp events
**
******************************************************************************************************************/
	public function beforeAction(Event $event) {
		$model = $this->_table;

		$model->fields['operation']['visible'] = false;
		$model->fields['model_reference']['visible'] = false;
		$model->fields['field_type']['visible'] = false;

		$model->fields['created_user_id']['visible'] = true;
		$model->fields['created']['visible'] = true;
	}


/******************************************************************************************************************
**
** specific field functions
**
******************************************************************************************************************/
	public function onGetField(Event $event, Entity $entity) {
		$value = str_replace('_id', '', $entity->field);
		return Inflector::humanize($value);
	}

	public function onGetOldValue(Event $event, Entity $entity) {
		if (array_key_exists($entity->field_type, $this->_dateTypes)) {
			return $this->formatToSystemConfig($entity->old_value, $entity->field_type);
		}
	}

	public function onGetNewValue(Event $event, Entity $entity) {
		if (array_key_exists($entity->field_type, $this->_dateTypes)) {
			return $this->formatToSystemConfig($entity->new_value, $entity->field_type);
		}
	}


/******************************************************************************************************************
**
** essential functions
**
******************************************************************************************************************/
	public function formatToSystemConfig($value, $type) {
		$ConfigItem = TableRegistry::get('Configuration.ConfigItems');
		if ($type=='datetime') {
			$format = $ConfigItem->value('date_format') . ' - ' . $ConfigItem->value('time_format');
		} else {
			$format = $ConfigItem->value($type.'_format');
		}

		$value = new Time($value);
		$value = $value->format($format);

		return $value;
	}

}
