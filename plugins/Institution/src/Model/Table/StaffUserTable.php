<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Text;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Staff\Model\Table\StaffTable as UserTable;

class StaffUserTable extends UserTable {
	public function addAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
		$sessionKey = 'Institution.Staff.new';
		if ($this->Session->check($sessionKey)) {
			$positionData = $this->Session->read($sessionKey);
			$positionData['security_user_id'] = $entity->id;
			$role = $positionData['role'];
			$institutionId = $positionData['institution_site_id'];

			$Staff = TableRegistry::get('Institution.Staff');
			if ($Staff->save($Staff->newEntity($positionData))) {
				if ($role > 0) {
					$institutionEntity = TableRegistry::get('Institution.Institutions')->get($institutionId);
					$obj = [
						'id' => Text::uuid(),
						'security_group_id' => $institutionEntity->security_group_id, 
						'security_role_id' => $role, 
						'security_user_id' => $entity->id
					];
					$GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
					$GroupUsers->save($GroupUsers->newEntity($obj));
				}
			}
			$this->Session->delete($sessionKey);
		}
		$event->stopPropagation();
		$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Staff', 'index'];
		return $this->controller->redirect($action);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	private function setupTabElements($entity) {
		$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];
		
		$tabElements = [
			'Staff' => ['text' => __('Position')],
			'StaffUser' => ['text' => __('General')]
		];

		if ($this->action == 'add') {
			$tabElements['Staff']['url'] = array_merge($url, ['action' => 'Staff', 'add']);
			$tabElements['StaffUser']['url'] = array_merge($url, ['action' => $this->alias(), 'add']);
		} else {
			$id = $this->request->query['id'];
			$tabElements['Staff']['url'] = array_merge($url, ['action' => 'Staff', 'view', $id]);
			$tabElements['StaffUser']['url'] = array_merge($url, ['action' => $this->alias(), 'view', $entity->id, 'id' => $id]);
		}

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view' || $action == 'add') {
			unset($toolbarButtons['back']);
			if ($toolbarButtons->offsetExists('export')) {
				unset($toolbarButtons['export']);
			}
		}
	}
}
