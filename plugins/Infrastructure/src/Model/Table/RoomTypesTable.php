<?php
namespace Infrastructure\Model\Table;

use ArrayObject;

use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class RoomTypesTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->addBehavior('FieldOption.FieldOption');
		$this->table('room_types');
		parent::initialize($config);

		$this->addBehavior('Infrastructure.Types', ['code' => 'ROOM']);
		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
