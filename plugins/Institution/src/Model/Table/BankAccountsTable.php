<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class BankAccountsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_bank_accounts');
		parent::initialize($config);
		
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
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
