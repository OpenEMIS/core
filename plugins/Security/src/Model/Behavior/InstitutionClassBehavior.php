<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\ResultSet;
use Cake\Event\Event;

class InstitutionClassBehavior extends Behavior {
	public function implementedEvents() {
		$events = parent::implementedEvents();
		// priority has to be set at 100 so that Institutions->indexBeforePaginate will be triggered first
		$events['ControllerAction.Model.index.beforeQuery'] = ['callable' => 'indexBeforeQuery', 'priority' => 100];
		// set the priority of the action button to be after the academic period behavior
		$events['ControllerAction.Model.index.afterAction'] = ['callable' => 'indexAfterAction', 'priority' => 101];
		$events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
		$events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';
		return $events;
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		if ($this->_table->Auth->user('super_admin') != 1) { // if user is not super admin, the list will be filtered
			$userId = $this->_table->Auth->user('id');
			$AccessControl = $this->_table->AccessControl;
			$query->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl]);
		}
	}

<<<<<<< HEAD
	public function editAfterAction(Event $event, Entity $entity) {
		$action = 'edit';
		if (!$this->checkAllClassesPermission($action)) {
			if ($this->checkMyClassesPermission($action)) {
=======
	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		if (!$this->checkAllClassesEditPermission()) {
			if ($this->checkMyClassesEditPermission()) {
>>>>>>> origin_ssh/POCOR-1694
				$userId = $this->_table->Auth->user('id');
				if ($userId != $entity->staff_id) {
					$urlParams = $this->_table->url('view');
					$event->stopPropagation();
					$this->_table->Alert->error('security.noAccess');
					return $this->_table->controller->redirect($urlParams);
				}
			}
		}
	}

	public function findByAccess(Query $query, array $options) {
		if (array_key_exists('accessControl', $options)) {
			$AccessControl = $options['accessControl'];
			$userId = $options['userId'];
			$controller = $this->_table->controller;
			$roles = [];
			$event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
			if ($event->result) {
				$roles = $event->result;	
			}
			if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles)) {
				$query->where([$this->_table->aliasField('staff_id') => $userId]);
			}		}
		return $query;
	}

<<<<<<< HEAD
	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);
		// Remove the edit function if the user does not have the right to access that page
		$action = 'edit';
		if (!$this->checkAllClassesPermission($action)) {
			if ($this->checkMyClassesPermission($action)) {
				$userId = $this->_table->Auth->user('id');
				if ($userId != $entity->staff_id) {
					if (isset($buttons['edit'])) {
						unset($buttons['edit']);
						return $buttons;
					}
				}
			}
		}
=======
	// public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
	public function indexAfterAction(Event $event, ResultSet $data, ArrayObject $extra) {
		// $buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);
		// // Remove the edit function if the user does not have the right to access that page
		// if (!$this->checkAllClassesEditPermission()) {
		// 	if ($this->checkMyClassesEditPermission()) {
		// 		$userId = $this->_table->Auth->user('id');
		// 		if ($userId != $entity->staff_id) {
		// 			if (isset($buttons['edit'])) {
		// 				unset($buttons['edit']);
		// 				return $buttons;
		// 			}
		// 		}
		// 	}
		// }
>>>>>>> origin_ssh/POCOR-1694
	}

	// Function to check MyClass permission is set
	private function checkMyClassesPermission($action) {
		$AccessControl = $this->_table->AccessControl;
<<<<<<< HEAD
		$controller = $this->_table->controller;
		$roles = [];
		$event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
    	if ($event->result) {
    		$roles = $event->result;	
    	}
		$myClassesPermission = $AccessControl->check(['Institutions', 'Sections', $action], $roles);
		if ($myClassesPermission) {
=======
		$myClassesEditPermission = $AccessControl->check(['Institutions', 'Classes', 'edit']);
		if ($myClassesEditPermission) {
>>>>>>> origin_ssh/POCOR-1694
			return true;
		} else {
			return false;
		}
	}

	// Function to check AllClass permission is set
	private function checkAllClassesPermission($action) {
		$AccessControl = $this->_table->AccessControl;
		$controller = $this->_table->controller;
		$roles = [];
		$event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
    	if ($event->result) {
    		$roles = $event->result;	
    	}
		$allClassesPermission = $AccessControl->check(['Institutions', 'AllClasses', $action], $roles);
		if ($allClassesPermission) {
			return true;
		} else {
			return false;
		}
	}

<<<<<<< HEAD
	public function viewAfterAction(Event $event, Entity $entity) {
		$action = 'view';
		if (!$this->checkAllClassesPermission($action)) {
			if ($this->checkMyClassesPermission($action)) {
				$userId = $this->_table->Auth->user('id');
				if ($userId != $entity->staff_id) {
					$urlParams = $this->_table->ControllerAction->url('index');
					$event->stopPropagation();
					$this->_table->Alert->error('security.noAccess');
					return $this->_table->controller->redirect($urlParams);
				}
			}
		}
=======
	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
>>>>>>> origin_ssh/POCOR-1694
		$this->_table->request->data[$this->_table->alias()]['staff_id'] = $entity->staff_id;
		switch ($this->_table->action) {
			case 'view':
				// If all classes can edit, then skip the removal of the button
				if (!$this->checkAllClassesPermission('edit')) {
					// If there is no permission to edit my classes
					if ($this->checkMyClassesPermission('edit')) {
						$userId = $this->_table->Auth->user('id');
						$entityUserId = $this->_table->request->data[$this->_table->alias()]['staff_id'];
						// Remove the edit button from those records who does not belong to the user
						if ($userId != $entityUserId) {
							if (isset($extra['toolbarButtons']) && isset($extra['toolbarButtons']['edit'])) {
								unset($extra['toolbarButtons']['edit']);
							}
							if (isset($extra['toolbarButtons']) && isset($extra['toolbarButtons']['remove'])) {
								unset($extra['toolbarButtons']['remove']);
							}
						}
					}
				}
				break;
		}
	}

}