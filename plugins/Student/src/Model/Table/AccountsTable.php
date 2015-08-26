<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;

class AccountsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);
		
		$this->addBehavior('User.Account', ['userRole' => 'Students']);
	}

	public function validationDefault(Validator $validator) {
		$validator = $this->getAccountValidation($validator);
		return $validator;
	}
}