<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class BanksTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('banks');
		parent::initialize($config);
		$this->hasMany('BankBranches', ['className' => 'FieldOption.BankBranches', 'foreignKey' => 'bank_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->notEmpty('name', 'Please enter a name.')
			->notEmpty('code', 'Please enter a code.');

		return $validator;
	}

}
