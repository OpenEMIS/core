<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteFeeTypesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('InstitutionSiteFees', ['className' => 'Institution.InstitutionSiteFees']);
		$this->belongsTo('FeeTypes', ['className' => 'FieldOption.FeeTypes']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {
		
	}
}
