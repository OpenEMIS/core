<?php
namespace Institution\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;

class InstitutionAccessControlComponent extends Component {

	public function initialize(array $config): void {
		$this->Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
		$this->controller = $this->_registry->getController();
	}

	public function implementedEvents(): array {
		$events = parent::implementedEvents();
		$events['Controller.Navigation.onUpdateRoles'] = 'onNavigationUpdateRoles';
		$events['Controller.SecurityAuthorize.onUpdateRoles'] = 'onSecurityUpdateRoles';
		$events['Controller.Buttons.onUpdateRoles'] = 'onInitializeButtonUpdateRoles';
		return $events;
	}

	private function onUpdateRole() {
        // POCOR-8527 Check based on Institution_id;
        $queryString = $this->getController()->getQueryString();
        if(isset($queryString['institution_id'])){
            $institutionId = $queryString['institution_id'];
        }

		if (!$this->controller->AccessControl->isAdmin() && $institutionId){
			$userId = $this->controller->Auth->user('id');
            $institutionRoles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
            return $institutionRoles;
		}
	}

	public function onNavigationUpdateRoles(EventInterface $event) {

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

	public function onSecurityUpdateRoles(EventInterface $event) {
		return $this->onUpdateRole();
	}

	public function onInitializeButtonUpdateRoles(EventInterface $event) {
		return $this->onUpdateRole();
	}
}
