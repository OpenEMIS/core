<?php
namespace FieldOption\Model\Traits;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use FieldOption\Model\Traits\FieldOptionsTrait;

trait FieldOptionsTrait {
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction'];
		$events['ControllerAction.Model.index.beforeAction'] = ['callable' => 'indexBeforeAction'];
		return $events;
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		// only perform for v4
		if ($this->hasBehavior('ControllerAction')) {
			if ($entity->has('default') && $entity->default == 1) {
				$this->updateAll(['default' => 0], [$this->primaryKey().' != ' => $entity->{$this->primaryKey()}]);
			}
		}
	}

	public function onGetEditable(Event $event, Entity $entity) {
		return $entity->editable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}

	public function onGetDefault(Event $event, Entity $entity) {
		return $entity->default == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('default', ['options' => $this->getSelectOptions('general.yesno'), 'after' => 'visible']);
		$this->field('editable', ['options' => $this->getSelectOptions('general.yesno'), 'visible' => ['index' => true], 'after' => 'default']);
	}

	public function indexBeforeAction(Event $event) {
		$this->field('name', ['after' => 'editable']);
		$fields = ['visible', 'default', 'editable', 'name', 'international_code', 'national_code'];
		foreach ($fields as $field) {
			if (array_key_exists($field, $this->fields)) {
				$this->fields[$field]['visible']['index'] = true;
			}
		}
	}
}
