<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionFeeTypesTable extends AppTable {
	public function initialize(array $config): void {
		parent::initialize($config);
		
		$this->belongsTo('InstitutionFees', ['className' => 'Institution.InstitutionFees']);
		$this->belongsTo('FeeTypes', ['className' => 'FieldOption.FeeTypes']);
	}

	public function validationDefault(Validator $validator): Validator {
		$validator = parent::validationDefault($validator);
		
		return $validator
			->add('amount', 'ruleMaxLength', [
				'rule' => ['maxLength', 15]
			])
			;
	}

}
