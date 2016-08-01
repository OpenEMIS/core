<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\ORM\Entity;

class RestrictAssociatedDeleteBehavior extends Behavior {
	protected $_defaultConfig = [
		'message' => 'general.deleteTransfer.restrictDelete'
	];

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.onBeforeDelete'	=> ['callable' => 'onBeforeDelete', 'priority' => 20],
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra) {
		if ($this->_table->hasAssociatedRecords($this->_table, $entity, $extra)) {
			$event->stopPropagation();
			$extra['Alert']['message'] = $this->config('message');
			return false;
		}
	}
}
