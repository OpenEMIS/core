<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class BankBranchesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('bank_branches');
		parent::initialize($config);
		$this->belongsTo('Banks', ['className' => 'FieldOption.Banks']);
		$this->hasMany('UserBankAccounts', ['className' => 'User.BankAccounts', 'foreignKey' => 'bank_branch_id']);
		$this->hasMany('InstitutionBankAccounts', ['className' => 'Institution.InstitutionSiteBankAccounts', 'foreignKey' => 'bank_branch_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->notEmpty('name', 'Please enter a name.')
			->notEmpty('code', 'Please enter a code.');;

		return $validator;
	}
	
}
