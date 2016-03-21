<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;

class InstitutionSubjectStudent extends Entity {
	protected $_virtual = ['name', 'student_status'];
	
	protected function _getStudentName() {
		return $this->user->name_with_id;
	}

	protected function _getStudentStatus() {
		$status = '';
		if ($this->has('class_student')) {
			if ($this->class_student->has('student_status')) {
				$status = __($obj->class_student->student_status->name);
			}
		}
		return $status;
	}
}
