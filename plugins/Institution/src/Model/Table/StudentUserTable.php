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
use Cake\Network\Session;
use Student\Model\Table\StudentsTable as UserTable;

class StudentUserTable extends UserTable {

	public function initialize(array $config) {
		parent::initialize($config);
	}
	public function beforeAction(Event $event) {
		$this->ControllerAction->field('username', ['visible' => false]);
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
		$sessionKey = 'Institution.Students.new';
		if ($this->Session->check($sessionKey)) {
			$academicData = $this->Session->read($sessionKey);
			$academicData['student_id'] = $entity->id;
			// $class = $academicData['class'];
			// unset($academicData['class']);
			$StudentStatusesTable = TableRegistry::get('Student.StudentStatuses');
			$pendingAdmissionCode = $StudentStatusesTable->getIdByCode('PENDING_ADMISSION');
			if ($academicData['student_status_id'] != $pendingAdmissionCode) {
				$Student = TableRegistry::get('Institution.Students');
				if (empty($academicData['student_name'])) {
					$academicData['student_name'] = $entity->openemis_no;
				}

				$newStudentEntity = $Student->newEntity($academicData);
				$newStudentEntity->class = $academicData['class']; // add the class value so the student will be added to the selected class
				if (!$Student->save($newStudentEntity)) {
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
					// if ($class > 0) {
					// 	$sectionData = [];
					// 	$sectionData['student_id'] = $entity->id;
					// 	$sectionData['education_grade_id'] = $academicData['education_grade_id'];
					// 	$sectionData['institution_section_id'] = $class;
					// 	$InstitutionSectionStudents = TableRegistry::get('Institution.InstitutionSectionStudents');
					// 	$InstitutionSectionStudents->autoInsertSectionStudent($sectionData);
					// }
				}
				//  else {
				// 	$validationErrors = [];
				// 	foreach ($newStudentEntity->errors() as $nkey => $nvalue) {
				// 		foreach ($nvalue as $ekey => $evalue) {
				// 			$validationErrors[] = $evalue;
				// 		}
				// 	}

				// 	$validationErrors = implode('; ', $validationErrors);
				// 	$this->controller->ControllerAction->Alert->error($validationErrors, ['type' => 'text']);
				// 	$event->stopPropagation();
				// 	$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Students', 'add'];
				// 	return $this->controller->redirect($action);
				// }
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
		if (!$this->AccessControl->isAdmin()) {
			$institutionIds = $this->AccessControl->getInstitutionsByUser();
			$this->Session->write('AccessControl.Institutions.ids', $institutionIds);
		}
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

    private function addTransferButton(ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, Session $session) {
    	$InstitutionStudentsTable = TableRegistry::get('Institution.Students');
		$statuses = $InstitutionStudentsTable->StudentStatuses->findCodeList();
		$id = $session->read('Institution.Students.id');
		$studentStatusId = $InstitutionStudentsTable->get($id)->student_status_id;	
		if ($this->AccessControl->check([$this->controller->name, 'TransferRequests', 'add'])) {
			$TransferRequests = TableRegistry::get('Institution.TransferRequests');
			$StudentPromotion = TableRegistry::get('Institution.StudentPromotion');
			$studentData = $InstitutionStudentsTable->get($id);
			$selectedStudent = $studentData->student_id;
			$selectedPeriod = $studentData->academic_period_id;
			$selectedGrade = $studentData->education_grade_id;
			$session->write($TransferRequests->registryAlias().'.id', $id);

			// Show Transfer button only if the Student Status is Current
			$institutionId = $session->read('Institution.Institutions.id');
			$student = $StudentPromotion
				->find()
				->where([
					$StudentPromotion->aliasField('institution_id') => $institutionId,
					$StudentPromotion->aliasField('student_id') => $selectedStudent,
					$StudentPromotion->aliasField('academic_period_id') => $selectedPeriod,
					$StudentPromotion->aliasField('education_grade_id') => $selectedGrade
				])
				->first();
				
			$checkIfCanTransfer = $InstitutionStudentsTable->checkIfCanTransfer($student, $institutionId);
			// End

			// Transfer button
			$transferButton = $buttons['back'];
			$transferButton['type'] = 'button';
			$transferButton['label'] = '<i class="fa kd-transfer"></i>';
			$transferButton['attr'] = $attr;
			$transferButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
			$transferButton['attr']['title'] = __('Transfer');
			//End

			$transferRequest = $TransferRequests
					->find()
					->where([
						$TransferRequests->aliasField('previous_institution_id') => $institutionId,
						$TransferRequests->aliasField('student_id') => $selectedStudent,
						$TransferRequests->aliasField('status') => 0
					])
					->first();

			if (!empty($transferRequest)) {
				$transferButton['url'] = [
					'plugin' => $buttons['back']['url']['plugin'],
					'controller' => $buttons['back']['url']['controller'],
					'action' => 'TransferRequests',
					'edit',
					$transferRequest->id
				];
				$toolbarButtons['transfer'] = $transferButton;
			} 
			else if ($checkIfCanTransfer) {
				$transferButton['url'] = [
					'plugin' => $buttons['back']['url']['plugin'],
					'controller' => $buttons['back']['url']['controller'],
					'action' => 'TransferRequests',
					'add'
				];
				$toolbarButtons['transfer'] = $transferButton;
			} 
		}
    }

    private function addDropoutButton(ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, Session $session) {
    	$InstitutionStudentsTable = TableRegistry::get('Institution.Students');
		if ($this->AccessControl->check([$this->controller->name, 'DropoutRequests', 'add'])) {
			// Institution student id
			$id = $session->read('Institution.Students.id');
			$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
			$enrolledStatus = $StudentStatuses->find()->where([$StudentStatuses->aliasField('code') => 'CURRENT'])->first()->id;
			$studentData = $InstitutionStudentsTable->get($id);
			// Check if the student is enrolled
			if ($studentData->student_status_id == $enrolledStatus) {

				$DropoutRequests = TableRegistry::get('Institution.DropoutRequests');
				$session->write($DropoutRequests->registryAlias().'.id', $id);
				$NEW = 0;
				
				$selectedStudent = $DropoutRequests->find()
					->select(['institution_student_dropout_id' => 'id'])
					->where([$DropoutRequests->aliasField('student_id') => $studentData->student_id, 
							$DropoutRequests->aliasField('institution_id') => $studentData->institution_id,
							$DropoutRequests->aliasField('education_grade_id') => $studentData->education_grade_id,
							$DropoutRequests->aliasField('status') => $NEW
						])
					->first();

				// Dropout button
				$dropoutButton = $buttons['back'];
				$dropoutButton['type'] = 'button';
				$dropoutButton['label'] = '<i class="fa kd-dropout"></i>';
				$dropoutButton['attr'] = $attr;
				$dropoutButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
				$dropoutButton['attr']['title'] = __('Dropout');

				// If this is a new application
				if (count($selectedStudent) == 0) {
					$dropoutButton['url'] = [
							'plugin' => $buttons['back']['url']['plugin'],
							'controller' => $buttons['back']['url']['controller'],
							'action' => 'DropoutRequests',
							'add'
						];
				} 
				// If the application is not new
				else {
					$dropoutButton['url'] = [
							'plugin' => $buttons['back']['url']['plugin'],
							'controller' => $buttons['back']['url']['controller'],
							'action' => 'DropoutRequests',
							'edit',
							$selectedStudent->institution_student_dropout_id
						];
				}
				$toolbarButtons['dropout'] = $dropoutButton;
			}
		}
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'view') {
			unset($toolbarButtons['back']);
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$id = $this->request->query('id');

			if (empty($id)) {
				// if no url param found... query the database to find the latest one
				// for catering redirections that do not contain institution_student_id url param - POCOR-2511
				$securityUserId = $this->request->pass[1];
				$InstitutionStudentsTable = TableRegistry::get('Institution.Students');
				$institutionStudentRecord = $InstitutionStudentsTable->find()
					->select([$InstitutionStudentsTable->aliasField('id')])
					->where([
						$InstitutionStudentsTable->aliasField('student_id') => $securityUserId,
						$InstitutionStudentsTable->aliasField('institution_id') => $institutionId
					])
					->order($InstitutionStudentsTable->aliasField('end_date').' DESC')
					->first()
					;
				$institutionStudentRecord = (!empty($institutionStudentRecord))? $institutionStudentRecord->toArray(): null;
				$institutionStudentId = (!empty($institutionStudentRecord))? $institutionStudentRecord['id']: null;
				$id = $institutionStudentId;
			}

			if (!empty($id)) {
				$this->Session->write('Institution.Students.id', $id);
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
			
			$session = $this->request->session();
			$this->addTransferButton($buttons, $toolbarButtons, $attr, $session);
			$this->addDropoutButton($buttons, $toolbarButtons, $attr, $session);

		} else if ($action == 'add') {
			$toolbarButtons['back']['url'] = $this->request->referer(true);
			if ($toolbarButtons->offsetExists('export')) {
				unset($toolbarButtons['export']);
			}
		}
	}

}
