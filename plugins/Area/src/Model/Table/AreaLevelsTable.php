<?php
namespace Area\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class AreaLevelsTable extends AppTable {
	public function initialize(array $config) {

		$this->hasMany('Areas', ['className' => 'Area.Areas']);
		
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

	public function beforeAction() {
		
	}

}
