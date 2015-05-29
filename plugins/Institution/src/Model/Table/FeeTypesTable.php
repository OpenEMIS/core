<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class FeeTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_fee_types');
		parent::initialize($config);
		
		$this->belongsTo('Fees', ['className' => 'Institution.Fees', 'foreignKey' => 'institution_site_fee_id']);
		$this->belongsTo('FeeTypes', ['className' => 'FeeTypes']);
	}

	public function validationDefault(Validator $validator) {
		$validator->add('name', 'notBlank', [
			'rule' => 'notBlank'
		]);
		return $validator;
	}

	public function beforeAction() {
		
	}
}
