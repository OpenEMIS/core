<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
// use Cake\ORM\TableRegistry;
// use Cake\ORM\Query;

class InstitutionSiteStaff extends Entity {
	protected $_virtual = ['name'];
	
	protected function _getStaffName() {
		return ($this->has('user'))? $this->user->name_with_id : '';
	}
}
