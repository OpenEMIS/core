<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;

class InstitutionSubjectStudent extends Entity {
	protected $_virtual = ['name', 'student_user_id', 'student_openemis_no', 'student_gender'];

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
			if ($this->user instanceof Entity) {
				$value = $this->user->id;
			} else if (is_array($this->user)) {
				$value = $this->user['id'];
			}
		}
		return $value;
	}

	protected function _getStudentOpenemisNo() {
		$value = '';
		if ($this->has('user')) {
			if ($this->user instanceof Entity) {
				$value = $this->user->openemis_no;
			} else if (is_array($this->user)) {
				$value = $this->user['openemis_no'];
			}
		}
		return $value;
	}

	protected function _getStudentGender() {
		$value = '';
		if ($this->has('user')) {
			if ($this->user instanceof Entity) {
				if ($this->user->has('gender')) {
					$value = __($this->user->gender->name);
				}
			} else if (is_array($this->user)) {
				if (array_key_exists('gender', $this->user)) {
					$value = __($this->user['gender']['name']);
				}
			}
		}
		return $value;
	}
}
