<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Query;
use User\Model\Table\ContactsTable as BaseTable;

class UserContactsTable extends BaseTable
{
	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->entityClass('User.Contact');
	}

	public function beforeAction(Event $event, ArrayObject $extra)
	{
		parent::beforeAction($event, $extra);
		$tabElements = $this->controller->getUserTabElements();

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Contacts');
	}

	public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$userId = $this->Auth->user('id');
		$query->where([$this->aliasField('security_user_id') => $userId]);
	}
}
