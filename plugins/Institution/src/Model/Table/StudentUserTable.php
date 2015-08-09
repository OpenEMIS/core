<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Student\Model\Table\StudentsTable as UserTable;

class StudentUserTable extends UserTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->removeBehavior('Record');
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	private function setupTabElements($entity) {
		$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];
		$id = $this->request->query['id'];

		$tabElements = [
			'Students' => [
				'url' => array_merge($url, ['action' => 'Students', 'view', $id]),
				'text' => __('Academic')
			],
			'StudentUser' => [
				'url' => array_merge($url, ['action' => $this->alias(), 'view', $entity->id, 'id' => $id]),
				'text' => __('General')
			]
		];

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view') {
			unset($toolbarButtons['back']);
			if ($toolbarButtons->offsetExists('export')) {
				unset($toolbarButtons['export']);
			}
		}
	}

}
