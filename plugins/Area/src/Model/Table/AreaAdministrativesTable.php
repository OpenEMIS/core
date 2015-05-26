<?php
namespace Area\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class AreaAdministrativesTable extends AppTable {
	public function initialize(array $config) {

		$this->belongsTo('AreaAdministrativeLevels', ['className' => 'Area.AreaAdministrativeLevels']);

		$this->hasMany('Institutions', ['className' => 'Institution.Institutions']);

	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

	public function beforeAction() {
		
	}

}
