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
use Directory\Model\Table\DirectoriesTable as UserTable;

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
		parent::viewAfterAction($event, $entity);
		$this->setupTabElements($entity);
	}

	public function beforeAction(Event $event) 
	{
		parent::beforeAction($event);
		parent::hideOtherInformationSection($this->controller->name, $this->action);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		parent::editAfterAction($event, $entity);
		$this->setupTabElements($entity);
	}

	public function addAfterAction(Event $event) {
		parent::addAfterAction($event);
		$options['type'] = 'student';
		$tabElements = $this->controller->getStudentGuardianTabElements($options);
		$this->controller->set('tabElements', $tabElements);
	}

	private function setupTabElements($entity) {
		$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];
		
		$tabElements = [
			'Guardians' => ['text' => __('Relation')],
			'GuardianUser' => ['text' => __('General')]
		];
		$action = 'Guardians';
		$actionUser = $this->alias();
		if ($this->controller->name == 'Directories') {
			$action = 'StudentGuardians';
			$actionUser = 'StudentGuardianUser';
		}
		$id = $this->request->query['id'];
		$tabElements['Guardians']['url'] = array_merge($url, ['action' => $action, 'view', $id]);
		$tabElements['GuardianUser']['url'] = array_merge($url, ['action' => $actionUser, 'view', $entity->id, 'id' => $id]);

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		
		$backUrl = $this->controller->getStudentGuardianTabElements();
		
		if ($action == 'view') {
			unset($toolbarButtons['back']);
			if ($toolbarButtons->offsetExists('export')) {
				unset($toolbarButtons['export']);
			}
		} else if ($action == 'add') {
			$toolbarButtons['back']['url']['action'] = $backUrl['Guardians']['url']['action'];
			$toolbarButtons['back']['url'][0] = 'add';
		}
	}
}
