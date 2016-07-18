<?php
namespace Infrastructure\Model\Table;

use ArrayObject;

use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use FieldOption\Model\Traits\FieldOptionsTrait;

class RoomTypesTable extends ControllerActionTable {
	use FieldOptionsTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Infrastructure.Types', ['code' => 'ROOM']);
		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$extra['config']['selectedLink'] = ['controller' => 'Infrastructures', 'action' => 'Fields'];
	}
}
