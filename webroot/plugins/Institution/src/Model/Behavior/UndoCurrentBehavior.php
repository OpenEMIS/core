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
		$model = $this->model;
		$studentIds = [];

        $institutionId = $entity->institution_id;

		if (isset($entity->students)) {
			foreach ($entity->students as $key => $obj) {
				$studentId = $obj['id'];
				if ($studentId != 0) {
					$studentIds[$studentId] = $studentId;

                    $enrolmentRecord = $model->find()
                        ->where([
                            $model->aliasField('institution_id') => $institutionId,
                            $model->aliasField('student_id') => $studentId,
                            $model->aliasField('student_status_id') => $this->statuses['CURRENT']
                        ])
                        ->first();

                    if ($enrolmentRecord) {
                        $model->delete($enrolmentRecord);
                    }
				}
			}
		}

		return $studentIds;
	}
}
