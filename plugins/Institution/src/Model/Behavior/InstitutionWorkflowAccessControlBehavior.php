<?php
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

class InstitutionWorkflowAccessControlBehavior extends Behavior {

    public function initialize(array $config) {
    	$this->Institutions = TableRegistry::get('Institution.Institutions');
    }

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Workflow.onUpdateRoles'] = 'onWorkflowUpdateRoles';
		return $events;
	}

	public function onWorkflowUpdateRoles(Event $event) {
		$session = $this->_table->Session;
		$controller = $this->_table->controller;
		$restrictedController = ['Institutions', 'Students', 'Staff'];
		if (!$controller->AccessControl->isAdmin() && $session->check('Institution.Institutions.id') && in_array($controller->name, $restrictedController)) {
			$userId = $controller->Auth->user('id');
			$institutionId = $session->read('Institution.Institutions.id');
			return $this->Institutions->getInstitutionRoles($userId, $institutionId);
		}
	}
}
