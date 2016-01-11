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
		$events['Undo.'.'beforeSave'.$this->undoAction.'Students'] = 'beforeSave'.$this->undoAction.'Students';
		return $events;
	}

	public function beforeSaveGraduatedStudents(Event $event, Entity $entity, ArrayObject $data) {
			$institutionId = $entity->institution_id;
			$selectedPeriod = $entity->academic_period_id;
			$selectedGrade = $entity->education_grade_id;
			$selectedStatus = $entity->student_status_id;

			$studentIds = [];
			$this->Students = $this->_table->getStudentModel();
			$this->statuses = $this->_table->getStudentStatuses();
			$currentStatus = $this->statuses['CURRENT'];

			if (isset($entity->students)) {
				foreach ($entity->students as $key => $obj) {
					$studentId = $obj['id'];
					if ($studentId != 0) {
						$studentIds[$studentId] = $studentId;
						$this->Students->updateAll(
							['student_status_id' => $this->statuses['CURRENT']],
							[
								'institution_id' => $institutionId,
								'academic_period_id' => $selectedPeriod,
								'education_grade_id' => $selectedGrade,
								'student_status_id' => $selectedStatus,
								'student_id' => $studentId
							]
						);
					}
				}
			}

			if (empty($studentIds)) {
				$this->_table->Alert->warning('general.notSelected', ['reset' => true]);
			} else {
				$this->_table->Alert->success('UndoStudentStatus.success.graduated', ['reset' => true]);
			}
	}
}
