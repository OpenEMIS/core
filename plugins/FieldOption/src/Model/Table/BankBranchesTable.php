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
	}
}
