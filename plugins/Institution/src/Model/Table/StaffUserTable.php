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
	public function beforeAction(Event $event) {
		$this->ControllerAction->field('username', ['visible' => false]);
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
		$sessionKey = 'Institution.Staff.new';
		if ($this->Session->check($sessionKey)) {
			$positionData = $this->Session->read($sessionKey);
			$positionData['security_user_id'] = $entity->id;
			$role = $positionData['role'];
			$institutionId = $positionData['institution_id'];

			$Staff = TableRegistry::get('Institution.Staff');
			$staffEntity = $Staff->newEntity($positionData, ['validate' => 'AllowEmptyName']);
			if ($Staff->save($staffEntity)) {
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
			} else {
				$errors = $staffEntity->errors();
				if (isset($errors['institution_position_id']['ruleCheckFTE'])) {
					$this->Alert->error('Institution.InstitutionStaff.noFTE', ['reset' => true]);
				} else {
					$this->Alert->error('Institution.InstitutionStaff.error', ['reset' => true]);
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
		$id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;

		$options = [
			'userRole' => 'Staff',
			'action' => $this->action,
			'id' => $id,
			'userId' => $entity->id
		];

		$tabElements = $this->controller->getUserTabElements($options);

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
		} else if ($action == 'add') {
			$toolbarButtons['back']['url'] = $this->request->referer(true);
			if ($toolbarButtons->offsetExists('export')) {
				unset($toolbarButtons['export']);
			}
		}
	}
}
