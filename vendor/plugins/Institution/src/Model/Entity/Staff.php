<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class Staff extends Entity
{
	protected $_virtual = ['name', 'staff_name'];

	protected function _getName() {
		$name = '';

		if ($this->has('user')) {
			$name = $this->user->name;
		} else if ($this->has('_matchingData')) {
			if (array_key_exists('Users', $this->_matchingData)) {
				$name = $this->_matchingData['Users']->name;
			}
		}
		return $name;
	}
	
	protected function _getStaffName() {
		$staffName = '';

		if ($this->has('user')) {
			$staffName = $this->user->name_with_id;
		} else if ($this->has('_matchingData')) {
			if (array_key_exists('Users', $this->_matchingData)) {
				$staffName = $this->_matchingData['Users']->name_with_id;
			}
		}
		return $staffName;
	}

	// protected function _getOpenemisNo() {
	// 	return ($this->has('user'))? $this->user->openemis_no: '';
	// }

	// protected function _getDefaultIdentityType() {
	// 	return ($this->has('user'))? $this->user->defaultIdentityType: '';
	// }
}
