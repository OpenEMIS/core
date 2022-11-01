<?php
namespace App\Auth;

use Cake\Auth\AbstractPasswordHasher;
use Cake\Utility\Security;

class LegacyPasswordHasher extends AbstractPasswordHasher {
	public function hash($password) {
		$salt = 'thisismysalt';
		$hashedPassword = Security::hash($password, null, $salt);
		return $hashedPassword;
	}

	public function check($password, $hashedPassword) {
		return $this->hash($password) === $hashedPassword;
	}
}
