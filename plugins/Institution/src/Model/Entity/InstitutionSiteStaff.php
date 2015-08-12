<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
// use Cake\ORM\Query;

class InstitutionSiteStaff extends Entity {
	protected $_virtual = ['name', 'openemis_id', 'default_identity_type'];
	
	protected function _getStaffName() {
		return ($this->has('user'))? $this->user->name_with_id : '';
	}

	protected function _getOpenemisNo() {
		return ($this->has('user'))? $this->user->openemis_no: '';
	}

	protected function _getName() {
		return ($this->has('user'))? $this->user->name: '';
	}

	protected function _getDefaultIdentityType() {
		return ($this->has('user'))? $this->user->defaultIdentityType: '';
	}
}
