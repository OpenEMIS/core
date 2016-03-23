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
use Cake\Network\Session;
use Staff\Model\Table\StaffTable as UserTable;

class StaffUserTable extends UserTable {
	public function beforeAction(Event $event) {
		$this->ControllerAction->field('username', ['visible' => false]);
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
		$sessionKey = 'Institution.Staff.new';
		if ($this->Session->check($sessionKey)) {
			$positionData = $this->Session->read($sessionKey);
			$positionData['staff_id'] = $entity->id;
			$institutionId = $positionData['institution_id'];

			$Staff = TableRegistry::get('Institution.Staff');
			$staffEntity = $Staff->newEntity($positionData, ['validate' => 'AllowEmptyName']);
			if (!$Staff->save($staffEntity)) {
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
		if (!$this->AccessControl->isAdmin()) {
			$institutionIds = $this->AccessControl->getInstitutionsByUser();
			$this->Session->write('AccessControl.Institutions.ids', $institutionIds);
		}
		$this->Session->write('Staff.Staff.id', $entity->id);
		$this->Session->write('Staff.Staff.name', $entity->name);
		$this->setupTabElements($entity);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->Session->write('Staff.Staff.id', $entity->id);
		$this->Session->write('Staff.Staff.name', $entity->name);
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

    private function addTerminationButton(ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, Session $session) {
    	$InstitutionStudentsTable = TableRegistry::get('Institution.Staff');
    	// $InstitutionsTable = TableRegistry::get('Institution.Institutions');
    	// $institutionId = $session->read('Institution.Institutions.id');
    	// $userId = $
    	// $roles = $InstitutionsTable->getInstitutionRoles(,$institutionId);
    	if ($this->AccessControl->check([$this->controller->name, 'StaffTerminationRequest', 'add'])) {
    		$id = $session->read('Institution.Staff.id');
    		$StaffTerminations = TableRegistry::get('Institution.StaffTerminations');
    		$session->write($StaffTerminations->registryAlias().'.id', $id);
    		$staff = TableRegistry::get('Institution.Staff')->get($id);

    		$terminationRecords = $StaffTerminations->find()
    			->where([
    				$StaffTerminations->aliasField('staff_id') => $staff->staff_id, 
    				$StaffTerminations->aliasField('institution_position_id') => $staff->institution_position_id
    			])
    			->first();

    		$terminationButton = $buttons['view'];
			$terminationButton['type'] = 'button';
			$terminationButton['label'] = '<i class="fa kd-dropout"></i>';
			$terminationButton['attr'] = $attr;
			$terminationButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
			$terminationButton['attr']['title'] = __('Staff Termination');

			if (!count($terminationRecords)) {
				$terminationButton['url'] = [
					'plugin' => $buttons['back']['url']['plugin'],
					'controller' => $buttons['back']['url']['controller'],
					'action' => 'StaffTerminationRequests',
					'add'
				];
			} else {
				$terminationButton['url'] = [
					'plugin' => $buttons['back']['url']['plugin'],
					'controller' => $buttons['back']['url']['controller'],
					'action' => 'StaffTerminationRequests',
					'view',
					$terminationRecords->id
				];
			}

			$toolbarButtons['termination'] = $terminationButton;
    	}
    	
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view') {
			$id = $this->request->query('id');
			$this->Session->write('Institution.Staff.id', $id);
			$session = $this->request->session();
			$this->addTerminationButton($buttons, $toolbarButtons, $attr, $session);

			// $toolbarButtons['termination'] = $transferButton;
			if ($toolbarButtons->offsetExists('back')) {
				unset($toolbarButtons['back']);
			}
		} else if ($action == 'add') {
			$backAction = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Staff', 'add'];
			$toolbarButtons['back']['url'] = $backAction;

			if ($toolbarButtons->offsetExists('export')) {
				unset($toolbarButtons['export']);
			}
		}
	}
}
