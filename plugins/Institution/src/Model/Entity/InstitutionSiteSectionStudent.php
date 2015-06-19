<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class InstitutionSiteSectionStudent extends Entity {
	protected $_virtual = ['name'];
	
	protected function _getStudentName() {
		return $this->user->name_with_id;
	}
}
