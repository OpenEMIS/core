<?php
namespace ControllerAction\Model\Traits;

use Cake\Event\Event;

trait EventTrait {
	private function onEvent($subject, $eventKey, $method) {
		$eventMap = $subject->implementedEvents();
		if (!array_key_exists($eventKey, $eventMap) && !is_null($method)) {
			if (method_exists($subject, $method) || $subject->behaviors()->hasMethod($method)) {
				$subject->eventManager()->on($eventKey, [], [$subject, $method]);
			}
		}
	}

	private function dispatchEvent($subject, $eventKey, $method=null, $params=[], $autoOff=false) {
		$this->onEvent($subject, $eventKey, $method);
		$event = new Event($eventKey, $this, $params);
		$event = $subject->eventManager()->dispatch($event);
		if(!is_null($method) && $autoOff) {
			$this->offEvent($subject, $eventKey, $method);
		}
		return $event;
	}

	private function offEvent($subject, $eventKey, $method) {
		$subject->eventManager()->off($eventKey, [$subject, $method]);
	}
}
