<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Guardian\Model\Table\GuardiansTable as UserTable;

class GuardianUserTable extends UserTable {
	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
		$sessionKey = 'Student.Guardians.new';
		if ($this->Session->check($sessionKey)) {
			$guardianData = $this->Session->read($sessionKey);
			$guardianData['guardian_id'] = $entity->id;

			$Guardians = TableRegistry::get('Student.Guardians');
			$Guardians->save($Guardians->newEntity($guardianData));
			$this->Session->delete($sessionKey);
		}
		$event->stopPropagation();
		$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Guardians', 'index'];
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
			'Guardians' => ['text' => __('Relation')],
			'GuardianUser' => ['text' => __('General')]
		];

		$id = $this->request->query['id'];
		$tabElements['Guardians']['url'] = array_merge($url, ['action' => 'Guardians', 'view', $id]);
		$tabElements['GuardianUser']['url'] = array_merge($url, ['action' => $this->alias(), 'view', $entity->id, 'id' => $id]);

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
			$toolbarButtons['back']['url']['action'] = 'Guardians';
			$toolbarButtons['back']['url'][0] = 'add';
		}
	}
}
