<?php
namespace Security\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

class InstitutionSubjectBehavior extends Behavior {
	public function implementedEvents() {
		$events = parent::implementedEvents();

		// priority has to be set at 100 so that Institutions->indexBeforePaginate will be triggered first
		$events['ControllerAction.Model.index.beforePaginate'] = ['callable' => 'indexBeforePaginate', 'priority' => 100];
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
							FROM institution_class_staff
							WHERE institution_class_staff.institution_class_id = ' . $this->_table->aliasField('id') . '
							AND institution_class_staff.security_user_id = ' . $userId . 
						')',

						// second condition if the current user is the homeroom teacher of the subject class
						'EXISTS (
							SELECT 1
							FROM institution_section_classes
							JOIN institution_sections
							ON institution_sections.id = institution_section_classes.institution_section_id
							AND institution_sections.security_user_id = ' . $userId . '
							WHERE institution_section_classes.institution_class_id = ' . $this->_table->aliasField('id') .
						')'
					]
				]);
			}
		}

		return $query;
	}
}
