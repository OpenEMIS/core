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
	public function beforeAction(Event $event) {
		$this->ControllerAction->field('username', ['visible' => false]);
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
		$sessionKey = 'Institution.Students.new';
		if ($this->Session->check($sessionKey)) {
			$academicData = $this->Session->read($sessionKey);
			$academicData['student_id'] = $entity->id;
			$class = $academicData['class'];
			unset($academicData['class']);
			$StudentStatusesTable = TableRegistry::get('Student.StudentStatuses');
			$pendingAdmissionCode = $StudentStatusesTable->getIdByCode('PENDING_ADMISSION');
			if ($academicData['student_status_id'] != $pendingAdmissionCode) {
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
			} else {
				$AdmissionTable = TableRegistry::get('Institution.StudentAdmission');
				$admissionStatus = 1;
				$entityData = [
					'start_date' => $academicData['start_date'],
					'end_date' => $academicData['end_date'],
					'student_id' => $academicData['student_id'],
					'status' => 0,
					'institution_id' => $academicData['institution_id'],
					'academic_period_id' => $academicData['academic_period_id'],
					'education_grade_id' => $academicData['education_grade_id'],
					'previous_institution_id' => 0,
					'student_transfer_reason_id' => 0,
					'type' => $admissionStatus,
				];
				if ($AdmissionTable->save($AdmissionTable->newEntity($entityData))) {
					$this->Alert->success('general.add.success');
				} else {
					$AdmissionTable->log($admissionEntity->errors(), 'debug');
					$this->Alert->error('general.add.failed');
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
		$tabElements = $this->controller->getUserTabElements(['userRole' => 'Student']);

		if ($this->action != 'add') {
			$id = $this->request->query['id'];
			$tabElements['Students']['url'] = array_merge($tabElements['Students']['url'], [$id]);
			foreach ($tabElements as $key => $value) {
				if ($key == 'Students') continue;
				$tabElements[$key]['url'] = array_merge($tabElements[$key]['url'], [$entity->id, 'id' => $id]);
			}
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
		if ($action == 'view') {
			unset($toolbarButtons['back']);
			if ($toolbarButtons->offsetExists('export')) {
				unset($toolbarButtons['export']);
			}
		} else if ($action == 'add') {
			$toolbarButtons['back']['url'] = $this->request->referer(true);
			if ($toolbarButtons->offsetExists('export')) {
				unset($toolbarButtons['export']);
			}
		}
	}

}
