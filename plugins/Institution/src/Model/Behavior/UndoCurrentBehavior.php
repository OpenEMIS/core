<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Institution\Model\Behavior\UndoBehavior;

class UndoCurrentBehavior extends UndoBehavior {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Undo.'.'beforeSave'.$this->undoAction.'Students'] = 'beforeSave'.$this->undoAction.'Students';
		return $events;
	}

	public function beforeSaveCurrentStudents(Event $event, Entity $entity, ArrayObject $data) {
		$studentIds = [];

		if (isset($entity->students)) {
			foreach ($entity->students as $key => $obj) {
				$studentId = $obj['id'];
				if ($studentId != 0) {
					$studentIds[$studentId] = $studentId;

					$this->deleteEnrolledStudents($studentId);
				}
			}
		}

		return $studentIds;
	}
}
