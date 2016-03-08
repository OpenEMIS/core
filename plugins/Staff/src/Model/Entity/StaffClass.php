<?php
namespace Staff\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class StaffClass extends Entity
{
	protected $_virtual = ['male_students', 'female_students'];

	protected function _getMaleStudents() {
		if ($this->has('id')) {
			$count = TableRegistry::get('Institution.InstitutionClassStudents')->getMaleCountByClass($this->id);
		}
		return $count;
	}

	protected function _getFemaleStudents() {
		if ($this->has('id')) {
			$count = TableRegistry::get('Institution.InstitutionClassStudents')->getFemaleCountByClass($this->id);
		}
		return $count;
	}
}
