<?php
namespace Import\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\EventInterface;

class ImportMappingTable extends AppTable {
	public function initialize(array $config): void {
		parent::initialize($config);
	}

	// public function indexBeforeAction(EventInterface $event) {
	// 	$this->fields['created']['visible']['index'] = true;
	// 	$this->ControllerAction->setFieldOrder([
	// 		'created', 'message'
	// 	]);
	// }
}
