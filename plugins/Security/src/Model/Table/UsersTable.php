<?php
namespace Security\Model\Table;

use User\Model\Table\UsersTable as BaseTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class UsersTable extends BaseTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->entityClass('User.User');
		$this->addBehavior('Security.User');
		$this->addBehavior('Area.Areapicker');
	}

	public function editBeforeAction(Event $event) {
		$this->ControllerAction->field('address_area_id', ['type' => 'areapicker', 'source_model' => 'Area.AreaAdministratives']);
		$this->ControllerAction->field('birthplace_area_id', ['type' => 'areapicker', 'source_model' => 'Area.AreaAdministratives']);
	}
}
