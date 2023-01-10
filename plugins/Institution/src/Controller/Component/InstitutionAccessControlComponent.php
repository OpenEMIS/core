<?php
namespace Institution\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class InstitutionAccessControlComponent extends Component {

	public function initialize(array $config) {
		$this->Institutions = TableRegistry::get('Institution.Institutions');
		$this->controller = $this->_registry->getController();
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Controller.Navigation.onUpdateRoles'] = 'onNavigationUpdateRoles';
		$events['Controller.SecurityAuthorize.onUpdateRoles'] = 'onSecurityUpdateRoles';
		$events['Controller.Buttons.onUpdateRoles'] = 'onInitializeButtonUpdateRoles';
		return $events;
	}

	private function onUpdateRole() {
		$session = $this->request->session();
		if (!$this->controller->AccessControl->isAdmin() && $session->check('Institution.Institutions.id')){
			$userId = $this->controller->Auth->user('id');
			$institutionId = $session->read('Institution.Institutions.id');
			return $this->Institutions->getInstitutionRoles($userId, $institutionId);
		}
	}

	public function onNavigationUpdateRoles(Event $event) {
		$roles = $this->onUpdateRole();
		$restrictedTo = [
			['controller' => 'Institutions'],
			['controller' => 'Students'],
			['controller' => 'Staff'],
			['controller' => 'InstitutionContactPersons'],
			['controller' => 'InstitutionCalendars']
		];
		return ['roles' => $roles, 'restrictedTo' => $restrictedTo];
	}

	public function onSecurityUpdateRoles(Event $event) {
		return $this->onUpdateRole();
	}

	public function onInitializeButtonUpdateRoles(Event $event) {
		return $this->onUpdateRole();
	}
}
