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
		$events['Undo.'.'processSave'.$this->undoAction.'Students'] = 'processSave'.$this->undoAction.'Students';
		return $events;
	}

	public function processSaveCurrentStudents(Event $event, Entity $entity, ArrayObject $data) 
	{
		$studentIds = [];

        $institutionId = $entity->institution_id;

		if (isset($entity->students)) {
			foreach ($entity->students as $key => $obj) {
				$studentId = $obj['id'];
				if ($studentId != 0) {
					$studentIds[$studentId] = $studentId;

					$this->deleteEnrolledStudents($studentId, $this->statuses['CURRENT']);

					//remove pending admission/transfer/dropout that occured after the process that is undone.
                    $this->removePendingAdmission($this->statuses['CURRENT'], $studentId, $institutionId);
                    $this->removePendingDropout($studentId, $institutionId);
				}
			}
		}

		return $studentIds;
	}
}
