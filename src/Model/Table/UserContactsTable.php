<?php
namespace App\Model\Table;

use Cake\Validation\Validator;
use Cake\Event\Event;
use User\Model\Table\ContactsTable as BaseTable;

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
