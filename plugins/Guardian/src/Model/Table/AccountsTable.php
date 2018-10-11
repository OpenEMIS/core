<?php
namespace Guardian\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class AccountsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('User.Account', ['permission' => ['Guardian', 'Accounts', 'edit']]);
		parent::initialize($config);
	}
}
