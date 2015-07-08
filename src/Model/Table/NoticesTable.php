<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;

class NoticesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['created']['visible']['index'] = true;
		$this->ControllerAction->setFieldOrder([
			'created', 'message'
		]);
	}

	public function onGetCreated(Event $event, Entity $entity) {
		return $this->formatDateTime($entity->created);
	}

}
