<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SecurityUsersTable extends Table {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FileUpload');
	}

	public function beforeAction() {
		$this->fields['photo_content']['type'] = 'image';
	}

	public function validationDefault(Validator $validator) {
		$validator
			->notEmpty('username')
			->notEmpty('first_name');

		return $validator;
	}

}