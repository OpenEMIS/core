<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
// use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Institution\Model\Behavior\UndoBehavior;

class UndoGraduatedBehavior extends UndoBehavior {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Undo.'.'get'.$this->undoAction.'Students'] = 'onGet'.$this->undoAction.'Students';
		return $events;
	}

	public function onGetGraduatedStudents(Event $event, ArrayObject $settings, ArrayObject $students) {
	}
}
