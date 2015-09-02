<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Student\Model\Table\StudentsTable as UserTable;

class StudentUserTable extends UserTable {
	public function addAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
		$sessionKey = 'Institution.Students.new';
		if ($this->Session->check($sessionKey)) {
			$academicData = $this->Session->read($sessionKey);
			$academicData['student_id'] = $entity->id;
			$class = $academicData['class'];
			unset($academicData['class']);

			$Student = TableRegistry::get('Institution.Students');
			if ($Student->save($Student->newEntity($academicData))) {
				if ($class > 0) {
					$sectionData = [];
					$sectionData['student_id'] = $entity->id;
					$sectionData['education_grade_id'] = $academicData['education_grade_id'];
					$sectionData['institution_site_section_id'] = $class;
					$InstitutionSiteSectionStudents = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
					$InstitutionSiteSectionStudents->autoInsertSectionStudent($sectionData);
				}
			}
			$this->Session->delete($sessionKey);
		}
		$event->stopPropagation();
		$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Students', 'index'];
		return $this->controller->redirect($action);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	private function setupTabElements($entity) {
		$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];
		
		$tabElements = [
			'Students' => ['text' => __('Academic')],
			'StudentUser' => ['text' => __('General')]
		];

		if ($this->action == 'add') {
			$tabElements['Students']['url'] = array_merge($url, ['action' => 'Students', 'add']);
			$tabElements['StudentUser']['url'] = array_merge($url, ['action' => $this->alias(), 'add']);
		} else {
			$id = $this->request->query['id'];
			$tabElements['Students']['url'] = array_merge($url, ['action' => 'Students', 'view', $id]);
			$tabElements['StudentUser']['url'] = array_merge($url, ['action' => $this->alias(), 'view', $entity->id, 'id' => $id]);

			$tabElements['StudentSurveys'] = [
				'text' => __('Survey'),
				'url' => array_merge($url, ['action' => 'StudentSurveys', 'index', 'id' => $id, 'student_id' => $entity->id])
			];
		}

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view' || $action == 'add') {
			unset($toolbarButtons['back']);
			if ($toolbarButtons->offsetExists('export')) {
				unset($toolbarButtons['export']);
			}
		}
	}

}
