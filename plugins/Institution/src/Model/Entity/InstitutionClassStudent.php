<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;

class InstitutionClassStudent extends Entity {
	protected $_virtual = ['name', 'student_status_name'];
	
	protected function _getStudentName() {
		return $this->user->name_with_id;
	}

	protected function _getStudentStatusName() {
		$status = '';
		if ($this->has('student_status')) {
			$status = __($obj->student_status->name);
		}
		return $status;
	}
}
