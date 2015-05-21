<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table {
	public function initialize(array $config) {
		$this->table('security_users');
	}

	public function validationDefault(Validator $validator) {
		$validator
			->notEmpty('username')
			->notEmpty('first_name');

		return $validator;
	}
}
