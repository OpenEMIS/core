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

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		if (!$this->checkAllSubjectsEditPermission()) {
			if ($this->checkMySubjectsEditPermission()) {
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

	// used to be onUpdateActionButtons
	public function indexAfterAction(Event $event, ResultSet $data, ArrayObject $extra) {
		// $buttons = $this->_table->indexAfterAction($event, $data, $extra);
		// Remove the edit function if the user does not have the right to access that page
		// if (!$this->checkAllSubjectsEditPermission()) {
		// 	if ($this->checkMySubjectsEditPermission()) {
				// $userId = $this->_table->Auth->user('id');
				// if ($entity->has('teachers')) {
				// 	if (empty($entity->teachers)) {
				// 		if (isset($extra['indexButtons']['edit'])) {
				// 			unset($extra['indexButtons']['edit']);
				// 			// return $extra['indexButtons'];
				// 		}
				// 	} else {
				// 		$isFound = false;
				// 		foreach ($entity->teachers as $staff) {
				// 			if ($userId == $staff->id) {
				// 				$isFound = true;
				// 				break;
				// 			}
				// 		}
				// 		if (! $isFound) {
				// 			if (isset($extra['indexButtons']['edit'])) {
				// 				unset($extra['indexButtons']['edit']);
				// 				// return $extra['indexButtons'];
				// 			}
				// 		}
				// 	}
				// }
		// 	}
		// }
	}

	// Function to check MySubjects edit permission is set
	public function checkMySubjectsEditPermission() {
		$AccessControl = $this->_table->AccessControl;
		$mySubjectsEditPermission = $AccessControl->check(['Institutions', 'Subjects', 'edit']);
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

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$staff = [];
		if (!empty($entity->teachers)) {
			$staff = $entity->teachers;
		}
		$this->_table->request->data[$this->_table->alias()]['teachers'] = $staff;

		switch ($this->_table->action) {
			case 'view':
				if (!$this->checkAllSubjectsEditPermission()) {
					if ($this->checkMySubjectsEditPermission()) {
						$userId = $this->_table->Auth->user('id');
						$staffs = $this->_table->request->data[$this->_table->alias()]['teachers'];
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
			if (!$AccessControl->check(['Institutions', 'AllSubjects', 'index'])) {
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
