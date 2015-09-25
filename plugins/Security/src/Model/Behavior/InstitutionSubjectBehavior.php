<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;

class InstitutionSubjectBehavior extends Behavior {
	public function implementedEvents() {
		$events = parent::implementedEvents();
		// priority has to be set at 100 so that Institutions->indexBeforePaginate will be triggered first
		$events['ControllerAction.Model.index.beforePaginate'] = ['callable' => 'indexBeforePaginate', 'priority' => 100];
		// set the priority of the action button to be after the academic period behavior
		$events['Model.custom.onUpdateActionButtons'] = ['callable' => 'onUpdateActionButtons', 'priority' => 101];
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		$events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
		$events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';
		return $events;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		if ($this->_table->Auth->user('super_admin') != 1) { // if user is not super admin, the list will be filtered
			$userId = $this->_table->Auth->user('id');
			$AccessControl = $this->_table->AccessControl;
			$query->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl]);
		}
	}

	public function editAfterAction(Event $event, Entity $entity) {
		if (!$this->checkAllSubjectsEditPermission()) {
			if ($this->checkMySubjectsEditPermission()) {
				$userId = $this->_table->Auth->user('id');
				if (empty($entity->teachers)) {
					$urlParams = $this->_table->ControllerAction->url('view');
					$event->stopPropagation();
					$this->_table->Alert->error('security.noAccess');
					return $this->_table->controller->redirect($urlParams);
				} elseif ($userId != $entity->teachers[0]->id) {
					$urlParams = $this->_table->ControllerAction->url('view');
					$event->stopPropagation();
					$this->_table->Alert->error('security.noAccess');
					return $this->_table->controller->redirect($urlParams);
				}
			}
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);
		// Remove the edit function if the user does not have the right to access that page
		if (!$this->checkAllSubjectsEditPermission()) {
			if ($this->checkMySubjectsEditPermission()) {
				$userId = $this->_table->Auth->user('id');
				if ($entity->has('teachers')) {
					if (empty($entity->teachers)) {
						if (isset($buttons['edit'])) {
							unset($buttons['edit']);
							return $buttons;
						}
					} elseif ($userId != $entity->teachers[0]->id) {
						if (isset($buttons['edit'])) {
							unset($buttons['edit']);
							return $buttons;
						}
					}
				}
			}
		}
	}

	// Function to check MySubjects edit permission is set
	public function checkMySubjectsEditPermission() {
		$AccessControl = $this->_table->AccessControl;
		$mySubjectsEditPermission = $AccessControl->check(['Institutions', 'Classes', 'edit']);
		if ($mySubjectsEditPermission) {
			return true;
		} else {
			return false;
		}
	}

	// Function to check AllSubjects edit permission is set
	public function checkAllSubjectsEditPermission() {
		$AccessControl = $this->_table->AccessControl;
		$allSubjectsEditPermission = $AccessControl->check(['Institutions', 'AllSubjects', 'edit']);
		if ($allSubjectsEditPermission) {
			return true;
		} else {
			return false;
		}
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$staffId = '';
		if (!empty($entity->teachers)) {
			$staffId = $entity->teachers[0]->id;
		}
		$this->_table->request->data[$this->_table->alias()]['teacher_id'] = $staffId;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		switch ($action) {
			case 'view':
				if (!$this->checkAllSubjectsEditPermission()) {
					if ($this->checkMySubjectsEditPermission()) {
						$userId = $this->_table->Auth->user('id');
						$staffId = $this->_table->request->data[$this->_table->alias()]['teacher_id'];
						if ($userId != $staffId) {
							if (isset($toolbarButtons['edit'])) {
								unset($toolbarButtons['edit']);
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
			if (!$AccessControl->check(['Institutions', 'AllSubjects', 'index'])) {
				$query->where([
					'OR' => [
						// first condition if the current user is a teacher for this subject
						'EXISTS (
							SELECT 1 
							FROM institution_site_class_staff
							WHERE institution_site_class_staff.institution_site_class_id = ' . $this->_table->aliasField('id') . '
							AND institution_site_class_staff.security_user_id = ' . $userId . 
						')',

						// second condition if the current user is the homeroom teacher of the subject class
						'EXISTS (
							SELECT 1
							FROM institution_site_section_classes
							JOIN institution_site_sections
							ON institution_site_sections.id = institution_site_section_classes.institution_site_section_id
							AND institution_site_sections.security_user_id = ' . $userId . '
							WHERE institution_site_section_classes.institution_site_class_id = ' . $this->_table->aliasField('id') .
						')'
					]
				]);
			}
		}
		return $query;
	}
}
