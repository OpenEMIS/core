<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use App\Model\Traits\OptionsTrait;

class VisibleBehavior extends Behavior {
	use OptionsTrait;

	public function implementedEvents(): array {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.afterAction'] = 'afterAction';
		$events['ControllerAction.Model.add.beforeAction'] = 'addBeforeAction';
		$events['ControllerAction.Model.edit.beforeAction'] = 'editBeforeAction';
		return $events;
	}

	public function afterAction(EventInterface $event) {
		if ($this->_table->action == 'index') {
			$this->_table->fields['visible']['order'] = 0;
		}
	}

	public function addBeforeAction(EventInterface $event) {
		$this->_table->fields['visible']['type'] = 'hidden';
		$this->_table->fields['visible']['value'] = 1;
	}

	public function editBeforeAction(EventInterface $event) {
		$this->_table->fields['visible']['options'] = $this->getSelectOptions('general.yesno');
	}

	public function onGetVisible(EventInterface $event, Entity $entity) {
		return $entity->visible == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}
}
