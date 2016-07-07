<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\ResultSet;
use Cake\Event\Event;

class InstitutionSubjectBehavior extends Behavior {
	public function implementedEvents() {
		$events = parent::implementedEvents();
		// priority has to be set at 100 so that Institutions->indexBeforePaginate will be triggered first
		$events['ControllerAction.Model.index.beforeQuery'] = ['callable' => 'indexBeforeQuery', 'priority' => 100];
		// set the priority of the action button to be after the academic period behavior
		$events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
		$events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';
		return $events;
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		if ($this->_table->Auth->user('super_admin') != 1) { // if user is not super admin, the list will be filtered
			$userId = $this->_table->Auth->user('id');
			$AccessControl = $this->_table->AccessControl;
			$query->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl, 'controller' => $this->_table->controller]);
		}
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$action = 'edit';
		if (!$this->checkAllSubjectsPermission($action)) {
			if ($this->checkMySubjectsPermission($action)) {
				$userId = $this->_table->Auth->user('id');
				if (empty($entity->teachers)) {
					$urlParams = $this->_table->url('view');
					$event->stopPropagation();
					$this->_table->Alert->error('security.noAccess');
					return $this->_table->controller->redirect($urlParams);
				} else {
					$isFound = false;
					foreach ($entity->teachers as $staff) {
						if ($userId == $staff->id) {
							$isFound = true;
							break;
						}
					}
					if (! $isFound) {
						$urlParams = $this->_table->url('view');
						$event->stopPropagation();
						$this->_table->Alert->error('security.noAccess');
						return $this->_table->controller->redirect($urlParams);
					}
				}
			}
		}
	}

	// Function to check MySubjects permission is set
	private function checkMySubjectsPermission($action) {
		$AccessControl = $this->_table->AccessControl;
		$controller = $this->_table->controller;
		$roles = [];
		$event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
    	if ($event->result) {
    		$roles = $event->result;	
    	}
		$mySubjectsEditPermission = $AccessControl->check(['Institutions', 'Subjects', $action], $roles);
		if ($mySubjectsEditPermission) {
			return true;
		} else {
			return false;
		}
	}

	// Function to check AllSubjects permission is set
	private function checkAllSubjectsPermission($action) {
		$AccessControl = $this->_table->AccessControl;
		$controller = $this->_table->controller;
		$roles = [];
		$event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
    	if ($event->result) {
    		$roles = $event->result;	
    	}
		$allSubjectsEditPermission = $AccessControl->check(['Institutions', 'AllSubjects', $action], $roles);
		if ($allSubjectsEditPermission) {
			return true;
		} else {
			return false;
		}
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$action = 'view';

		// Check if the staff has access to the subject
		if (!$this->checkAllSubjectsPermission($action)) {
			if ($this->checkMySubjectsPermission($action)) {
				$isFound = false;
				$userId = $this->_table->Auth->user('id');
				
				// Homeroom teacher of the class will be able to view the subject
				if ($entity->has('classes')) {
					foreach ($entity->classes as $class) {
						if ($class->staff_id == $userId) {
							$isFound = true;
							break;
						}
					}
				}

				// Teachers who are owner of the classes will be able to access the subjects
				if (!empty($entity->teachers)) {
					foreach ($entity->teachers as $staff) {
						if ($userId == $staff->id) {
							$isFound = true;
							break;
						}
					}
				}
			}
			if (!$isFound) {
				$urlParams = $this->_table->ControllerAction->url('index');
				$event->stopPropagation();
				$this->_table->Alert->error('security.noAccess');
				return $this->_table->controller->redirect($urlParams);
			}
		}

		switch ($this->_table->action) {
			case 'view':
				if (!$this->checkAllSubjectsPermission('edit')) {
					if ($this->checkMySubjectsPermission('edit')) {
						$userId = $this->_table->Auth->user('id');
						$staffs = [];
						if (!empty($entity->teachers)) {
							$staffs = $entity->teachers;
						}
						$isFound = false;
						foreach ($staffs as $staff) {
							if ($userId == $staff->id) {
								$isFound = true;
								break;
							}
						}
						if (! $isFound) {
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

	public function findByAccess(Query $query, array $options) {
		if (array_key_exists('accessControl', $options)) {
			$AccessControl = $options['accessControl'];
			$userId = $options['userId'];
			$roles = [];
            if (array_key_exists('controller', $options)) {
                $controller = $options['controller'];
                $event = $controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
                if (is_array($event->result)) {
                    $roles = $event->result;    
                }
            }
			
			if (!$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles)) {
				$query->where([
					'OR' => [
						// first condition if the current user is a teacher for this subject
						'EXISTS (
							SELECT 1 
							FROM institution_subject_staff
							WHERE institution_subject_staff.institution_subject_id = ' . $this->_table->aliasField('id') . '
							AND institution_subject_staff.staff_id = ' . $userId . 
						')',

						// second condition if the current user is the homeroom teacher of the subject class
						'EXISTS (
							SELECT 1
							FROM institution_class_subjects
							JOIN institution_classes
							ON institution_classes.id = institution_class_subjects.institution_class_id
							AND institution_classes.staff_id = ' . $userId . '
							WHERE institution_class_subjects.institution_subject_id = ' . $this->_table->aliasField('id') .
						')'
					]
				]);
			}
		}
		return $query;
	}
}
