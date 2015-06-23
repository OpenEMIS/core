<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class NationalitiesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Countries', ['className' => 'FieldOption.Countries']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

}
