<?php
namespace Infrastructure\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class RoomTypesTable extends ControllerActionTable {
	private $levelOptions = [];
	private $roomLevel = null;

	public function initialize(array $config) {
		$this->addBehavior('FieldOption.FieldOption');
		$this->table('room_types');
		parent::initialize($config);

		$this->hasMany('InstitutionRooms', ['className' => 'Institution.InstitutionRooms', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->addBehavior('Infrastructure.Types', ['code' => 'ROOM']);
		$this->setDeleteStrategy('restrict');

		$InfrastructureLevels = TableRegistry::get('Infrastructure.InfrastructureLevels');
		$this->levelOptions = $InfrastructureLevels->getOptions(['keyField' => 'id', 'valueField' => 'name']);
		$this->roomLevel = $InfrastructureLevels->getFieldByCode('ROOM', 'id');
	}

	public function onGetInfrastructureLevel(Event $event, Entity $entity) {
		return $this->levelOptions[$this->roomLevel];
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$this->field('infrastructure_level', ['after' => 'national_code']);
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->setupFields($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->setupFields($entity);
	}

	public function onUpdateFieldInfrastructureLevel(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$attr['type'] = 'readonly';
			$attr['value'] = $this->roomLevel;
			$attr['attr']['value'] = $this->levelOptions[$this->roomLevel];
		}

		return $attr;
	}

	private function setupFields(Entity $entity) {
		$this->field('infrastructure_level', ['type' => 'select']);
	}
}