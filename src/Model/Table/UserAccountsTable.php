<?php
namespace App\Model\Table;

use ArrayObject;

use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;

class UserAccountsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('User.Account', ['userRole' => 'Preferences', 'targetField' => 'new_password']);
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction(Event $event) {
		$tabElements = $this->controller->getUserTabElements();
		
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Account');
	}
}
