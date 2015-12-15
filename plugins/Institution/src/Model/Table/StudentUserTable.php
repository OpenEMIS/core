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
				if (empty($academicData['student_name'])) {
					$academicData['student_name'] = $entity->openemis_no;
				}

				$newStudentEntity = $Student->newEntity($academicData);
				if ($Student->save($newStudentEntity)) {
					if ($class > 0) {
						$sectionData = [];
						$sectionData['student_id'] = $entity->id;
						$sectionData['education_grade_id'] = $academicData['education_grade_id'];
						$sectionData['institution_section_id'] = $class;
						$InstitutionSectionStudents = TableRegistry::get('Institution.InstitutionSectionStudents');
						$InstitutionSectionStudents->autoInsertSectionStudent($sectionData);
					}
				} else {
					$validationErrors = [];
					foreach ($newStudentEntity->errors() as $nkey => $nvalue) {
						foreach ($nvalue as $ekey => $evalue) {
							$validationErrors[] = $evalue;
						}
					}

					$validationErrors = implode('; ', $validationErrors);
					$this->controller->ControllerAction->Alert->error($validationErrors, ['type' => 'text']);
					$event->stopPropagation();
					$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Students', 'add'];
					return $this->controller->redirect($action);
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
		$this->Session->write('Student.Students.id', $entity->id);
		$this->Session->write('Student.Students.name', $entity->name);
		$this->setupTabElements($entity);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	private function setupTabElements($entity) {
		$id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;

		$options = [
			'userRole' => 'Student',
			'action' => $this->action,
			'id' => $id,
			'userId' => $entity->id
		];

		$tabElements = $this->controller->getUserTabElements($options);
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
			$institutionId = $this->Session->read('Institution.Institutions.id');
			if (!empty($this->request->query('id'))) {
				$this->Session->write('Institution.Students.id', $this->request->query('id'));
			}
			$id = $this->Session->read('Institution.Students.id');
			$StudentTable = TableRegistry::get('Institution.Students');
			$studentId = $StudentTable->get($id)->student_id;
			// Start PHPOE-1897
			if (! $StudentTable->checkEnrolledInInstitution($studentId, $institutionId)) {
				if (isset($toolbarButtons['edit'])) {
					unset($toolbarButtons['edit']);
				}
			}
			// End PHPOE-1897
		} else if ($action == 'add') {
			$toolbarButtons['back']['url'] = $this->request->referer(true);
			if ($toolbarButtons->offsetExists('export')) {
				unset($toolbarButtons['export']);
			}
		}
	}

}
