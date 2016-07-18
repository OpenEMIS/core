<?php
namespace Infrastructure\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class RoomTypesTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Infrastructure.Types', ['code' => 'ROOM']);
		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}

	public function beforeAction(Event $event, ArrayObject $extra)
	{
		$extra['config']['selectedLink'] = ['controller' => 'Infrastructures', 'action' => 'Fields'];
	}
}
