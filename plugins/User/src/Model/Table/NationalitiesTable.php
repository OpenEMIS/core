<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class NationalitiesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_nationalities');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Countries', ['className' => 'FieldOption.Countries']);
	}

	public function beforeAction($event) {
		$this->fields['country_id']['type'] = 'select';
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function validationNonMandatory(Validator $validator) {
		$this->validationDefault($validator);
		return $validator->allowEmpty('country_id');
	}

}
