<?php
namespace Import\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;

class ImportMappingTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	// public function indexBeforeAction(Event $event) {
	// 	$this->fields['created']['visible']['index'] = true;
	// 	$this->ControllerAction->setFieldOrder([
	// 		'created', 'message'
	// 	]);
	// }
}
