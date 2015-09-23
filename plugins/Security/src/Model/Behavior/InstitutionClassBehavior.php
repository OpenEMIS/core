<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;

class InstitutionClassBehavior extends Behavior {
	public function implementedEvents() {
		$events = parent::implementedEvents();

		// priority has to be set at 100 so that Institutions->indexBeforePaginate will be triggered first
		$events['ControllerAction.Model.index.beforePaginate'] = ['callable' => 'indexBeforePaginate', 'priority' => 100];
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
		$AccessControl = $this->_table->AccessControl;
		$allClassesEditPermission = $AccessControl->check(['Institutions', 'AllClasses', 'edit']);
		$myClassesEditPermission = $AccessControl->check(['Institutions', 'Sections', 'edit']);

		if (!$allClassesEditPermission) {
			if ($myClassesEditPermission) {
				$userId = $this->_table->Auth->user('id');
				if ($userId != $entity->security_user_id) {
					$urlParams = $this->_table->ControllerAction->url('view');
					$event->stopPropagation();
					return $this->_table->controller->redirect($urlParams);
				}
			}
		}
	}

	public function findByAccess(Query $query, array $options) {
		if (array_key_exists('accessControl', $options)) {
			$AccessControl = $options['accessControl'];
			$userId = $options['userId'];
			if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'])) {
				$query->where([$this->_table->aliasField('security_user_id') => $userId]);
			}
		}
		return $query;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);
		$AccessControl = $this->_table->AccessControl;
		$allClassesEditPermission = $AccessControl->check(['Institutions', 'AllClasses', 'edit']);
		$myClassesEditPermission = $AccessControl->check(['Institutions', 'Sections', 'edit']);

		// Remove the edit function if the user does not have the right to access that page
		if (!$allClassesEditPermission) {
			if ($myClassesEditPermission) {
				$userId = $this->_table->Auth->user('id');
				if ($userId != $entity->security_user_id) {
					if (isset($buttons['edit'])) {
						unset($buttons['edit']);
						return $buttons;
					}
				}
			}
		}
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->_table->request->data[$this->_table->alias()]['security_user_id'] = $entity->security_user_id;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		switch ($action) {
			case 'view':
				$AccessControl = $this->_table->AccessControl;
				$allClassesEditPermission = $AccessControl->check(['Institutions', 'AllClasses', 'edit']);
				$myClassesEditPermission = $AccessControl->check(['Institutions', 'Sections', 'edit']);

				// If all classes can edit, then skip the removal of the button
				if (!$allClassesEditPermission) {
					// If there is no permission to edit my classes
					if ($myClassesEditPermission) {
						$userId = $this->_table->Auth->user('id');
						$entityUserId = $this->_table->request->data[$this->_table->alias()]['security_user_id'];
						// Remove the edit button from those records who does not belong to the user
						if ($userId != $entityUserId) {
							if (isset($toolbarButtons['edit'])) {
								unset($toolbarButtons['edit']);
							}
						}
					}
				}
				break;
		}
	}
}