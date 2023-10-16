<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;

class InstitutionClassStudent extends Entity {
	protected $_virtual = ['name', 'student_status_name', 'student_user_id', 'student_openemis_no', 'student_gender'];
	
	protected function _getStudentName() {
		$value = '';
		if ($this->has('user')) {
			$value = $this->user->name_with_id;
		}
		return $value;
	}

	protected function _getStudentUserId() {
		$value = '';
		if ($this->has('user')) {
			$value = $this->user->id;
		}
		return $value;
	}

	protected function _getStudentOpenemisNo() {
		$value = '';
		if ($this->has('user')) {
			$value = $this->user->openemis_no;
		}
		return $value;
	}

	protected function _getStudentGender() {
		$value = '';
		if ($this->has('user')) {
			if ($this->user->has('gender')) {
				$value = $this->user->gender->name;
			}
		}
		return $value;
	}

	protected function _getStudentStatusName() {
		$value = '';
		if ($this->has('student_status')) {
			$value = __($this->student_status->name);
		}
		return $value;
	}
}
