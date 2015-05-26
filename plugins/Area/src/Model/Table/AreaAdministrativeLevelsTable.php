<?php
namespace Area\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class AreaAdministrativeLevelsTable extends AppTable {
	public function initialize(array $config) {

		$this->hasMany('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);

	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

	public function beforeAction() {
		
	}

}
