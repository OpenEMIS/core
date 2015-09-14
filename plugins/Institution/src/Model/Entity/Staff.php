<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class Staff extends Entity
{
	protected $_virtual = ['name'];
	
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
}
