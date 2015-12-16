<?php
namespace Staff\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class StaffSection extends Entity
{
	protected $_virtual = ['male_students', 'female_students'];

	protected function _getMaleStudents() {
		if ($this->has('id')) {
			$count = TableRegistry::get('Institution.InstitutionSectionStudents')->getMaleCountBySection($this->id);
		}
		return $count;
	}

	protected function _getFemaleStudents() {
		if ($this->has('id')) {
			$count = TableRegistry::get('Institution.InstitutionSectionStudents')->getFemaleCountBySection($this->id);
		}
		return $count;
	}
}
