<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Institution\Model\Behavior\UndoBehavior;

class UndoGraduatedBehavior extends UndoBehavior {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Undo.'.'get'.$this->undoAction.'Students'] = 'onGet'.$this->undoAction.'Students';
		$events['Undo.'.'beforeSave'.$this->undoAction.'Students'] = 'beforeSave'.$this->undoAction.'Students';
		return $events;
	}

	public function onGetGraduatedStudents(Event $event, $data) {
		return $this->getStudents($data);
	}

	public function beforeSaveGraduatedStudents(Event $event, Entity $entity, ArrayObject $data) {
		$studentIds = [];

		$institutionId = $entity->institution_id;
		$selectedPeriod = $entity->academic_period_id;
		$selectedGrade = $entity->education_grade_id;
		$selectedStatus = $entity->student_status_id;

		if (isset($entity->students)) {
			foreach ($entity->students as $key => $obj) {
				$studentId = $obj['id'];
				if ($studentId != 0) {
					$studentIds[$studentId] = $studentId;

					$this->deleteEnrolledStudents($studentId);
					$where = [
						'institution_id' => $institutionId,
						'academic_period_id' => $selectedPeriod,
						'education_grade_id' => $selectedGrade,
						'student_status_id' => $selectedStatus,
						'student_id' => $studentId
					];
					$this->updateStudentStatus('CURRENT', $where);
				}
			}
		}

		return $studentIds;
	}
}
