<?php
namespace App\Model\Table;

use User\Model\Table\ContactsTable as BaseTable;
use Cake\Validation\Validator;

class UserContactsTable extends BaseTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->entityClass('User.Contact');
	}

	public function beforeAction(Event $event) {
		parent::beforeAction($event);
		$tabElements = $this->controller->getTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'contacts');
	}
}
