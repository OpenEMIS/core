<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class UserCommentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('SecurityUsers', ['className' => 'SecurityUsers']);
		$this->belongsTo('ModifiedUser', ['className' => 'SecurityUsers', 'foreignKey' => 'modified_user_id']);
		$this->belongsTo('CreatedUser', ['className' => 'SecurityUsers', 'foreignKey' => 'created_user_id']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

}
