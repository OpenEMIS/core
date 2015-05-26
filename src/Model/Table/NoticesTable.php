<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class NoticesTable extends AppTable {
	public function initialize(array $config) {
	}

	public function validationDefault(Validator $validator) {
		$validator
		->requirePresence('message')
		->notEmpty('message', 'Please enter a message.');

		return $validator;
	}
}
