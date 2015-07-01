<?php
namespace Security\Model\Table;

use User\Model\Table\UsersTable as BaseTable;
use Cake\Validation\Validator;

class UsersTable extends BaseTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->entityClass('User.User');
		$this->addBehavior('Security.User');
	}
}