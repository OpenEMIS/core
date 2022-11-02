<?php
namespace User\Model\Entity;

use Cake\ORM\Entity;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;

class Account extends Entity {
	protected function _setPassword($password) {
		return (new DefaultPasswordHasher)->hash($password);
	}
}
