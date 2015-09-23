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
			// pr($this->_table->Session->read('Permissions.Institutions.AllClasses'));
			$query->find('byAccess', ['userId' => $userId, 'accessControl' => $AccessControl]);
		}
	}

	public function editAfterAction(Event $event, Entity $entity) {

	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = $this->_table->onUpdateActionButtons($event, $entity, $buttons);
		$AccessControl = $this->_table->AccessControl;
		$allSubjectsEditPermission = $AccessControl->check(['Institutions', 'AllSubjects', 'edit']);
		$mySubjectsEditPermission = $AccessControl->check(['Institutions', 'Classes', 'edit']);

		// Remove the edit function if the user does not have the right to access that page
		if (!$allSubjectsEditPermission) {
			if ($mySubjectsEditPermission) {
				$userId = $this->_table->Auth->user('id');
				if ($entity->has('teachers')) {
					if (!empty($entity->teachers)) {
						if ($userId != $entity->teachers[0]->id) {
							if (isset($buttons['edit'])) {
								unset($buttons['edit']);
								return $buttons;
							}
						}
					} else {
						if (isset($buttons['edit'])) {
							unset($buttons['edit']);
							return $buttons;
						}
					}
					
				}
			}
		}
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {

	}

	public function viewAfterAction(Event $event, Entity $entity) {

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
