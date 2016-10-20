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

class StudentUserTable extends UserTable
{
	public function initialize(array $config)
	{
		parent::initialize($config);
	}

	public function beforeAction(Event $event)
	{
		$this->ControllerAction->field('username', ['visible' => false]);
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);
		$validator
			->add('date_of_birth', 'ruleCheckAdmissionAgeWithEducationCycleGrade', [
				'rule' => ['checkAdmissionAgeWithEducationCycleGrade'],
				'on' => 'create'
			])

			->allowEmpty('postal_code')
			->add('postal_code', 'ruleCustomPostalCode', [
        		'rule' => ['validateCustomPattern', 'postal_code'],
        		'provider' => 'table',
        		'last' => true
		    ])
			;
		return $validator;
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
	{
		$sessionKey = 'Institution.Students.new';
		if ($this->Session->check($sessionKey)) {
			$academicData = $this->Session->read($sessionKey);
			$data[$this->alias()]['academic_period_id'] = $academicData['academic_period_id'];
			$data[$this->alias()]['education_grade_id'] = $academicData['education_grade_id'];
		} else {
			$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Students', 'add'];
			return $this->controller->redirect($action);
		}
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data)
	{
		$sessionKey = 'Institution.Students.new';
		if ($this->Session->check($sessionKey)) {
			$academicData = $this->Session->read($sessionKey);
			$academicData['student_id'] = $entity->id;
			$StudentStatusesTable = TableRegistry::get('Student.StudentStatuses');
			$pendingAdmissionCode = $StudentStatusesTable->PENDING_ADMISSION;
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
					// 	$classData = [];
					// 	$classData['student_id'] = $entity->id;
					// 	$classData['education_grade_id'] = $academicData['education_grade_id'];
					// 	$classData['institution_class_id'] = $class;
					// 	$InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
					// 	$InstitutionClassStudents->autoInsertClassStudent($classData);
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
					'institution_class_id' => $academicData['class'],
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

	public function viewAfterAction(Event $event, Entity $entity)
	{
		if (!$this->AccessControl->isAdmin()) {
			$institutionIds = $this->AccessControl->getInstitutionsByUser();
			$this->Session->write('AccessControl.Institutions.ids', $institutionIds);
		}
		$this->Session->write('Student.Students.id', $entity->id);
		$this->Session->write('Student.Students.name', $entity->name);
		$this->setupTabElements($entity);

		// individual promotion: show error if student has pending transfer or dropout requests
		if ($this->Session->check('Institution.IndividualPromotion.pendingRequest')) {
			if ($this->Session->check('Institution.IndividualPromotion.pendingRequest.transfer')) {
				$this->Alert->error('IndividualPromotion.pendingTransfer');
			}
			if ($this->Session->check('Institution.IndividualPromotion.pendingRequest.dropout')) {
				$this->Alert->error('IndividualPromotion.pendingDropout');
			}
			$this->Session->delete('Institution.IndividualPromotion.pendingRequest');
		}
	}

	public function editAfterAction(Event $event, Entity $entity)
	{
		$this->Session->write('Student.Students.id', $entity->id);
		$this->Session->write('Student.Students.name', $entity->name);
		$this->setupTabElements($entity);

		// POCOR-3010
		$userId = $this->Auth->user('id');
		if (!$this->checkClassPermission($entity->id, $userId)) {
			$this->Alert->error('security.noAccess');
			$event->stopPropagation();
			$url = $this->ControllerAction->url('view');
			return $this->controller->redirect($url);
		}
		// End POCOR-3010

		$this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.
	}

	private function setupTabElements($entity)
	{
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

	public function implementedEvents()
	{
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

    private function addTransferButton(ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, Session $session)
    {
    	$InstitutionStudentsTable = TableRegistry::get('Institution.Students');
		$statuses = $InstitutionStudentsTable->StudentStatuses->findCodeList();
		$id = $session->read('Institution.Students.id');
		if ($this->AccessControl->check([$this->controller->name, 'TransferRequests', 'add'])) {
			$TransferRequests = TableRegistry::get('Institution.TransferRequests');
			$studentData = $InstitutionStudentsTable->get($id);
			$selectedStudent = $studentData->student_id;
			$selectedGrade = $studentData->education_grade_id;
			$session->write($TransferRequests->registryAlias().'.id', $id);

			// Show Transfer button only if the Student Status is Current
			$institutionId = $session->read('Institution.Institutions.id');
			$checkIfCanTransfer = $InstitutionStudentsTable->checkIfCanTransfer($studentData, $institutionId);
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

    private function addPromoteButton(ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, Session $session)
    {
		if ($this->AccessControl->check([$this->controller->name, 'Promotion', 'add'])) {

			$InstitutionStudentsTable = TableRegistry::get('Institution.Students');
			$statuses = $InstitutionStudentsTable->StudentStatuses->findCodeList();
			$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$editableAcademicPeriods = $AcademicPeriods->getYearList(['isEditable' => true]);

			$id = $session->read('Institution.Students.id');
			$studentData = $InstitutionStudentsTable->get($id);
			$academicPeriodId = $studentData->academic_period_id;

			// Show Promote button only if the Student Status is Current and academic period is editable
			if ($studentData->student_status_id == $statuses['CURRENT'] && array_key_exists($academicPeriodId, $editableAcademicPeriods)) {

				// Promote button
				$promoteButton = $buttons['back'];
				$promoteButton['type'] = 'button';
				$promoteButton['label'] = '<i class="fa kd-graduate"></i>';
				$promoteButton['attr'] = $attr;
				$promoteButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
				$promoteButton['attr']['title'] = __('Promotion');

				$promoteButton['url'] = [
					'plugin' => $buttons['back']['url']['plugin'],
					'controller' => $buttons['back']['url']['controller'],
					'action' => 'IndividualPromotion',
					'add'
				];

				$toolbarButtons['promote'] = $promoteButton;
				//End

				$session->write('Institution.IndividualPromotion.id', $id);
			}
		}
    }

    private function addDropoutButton(ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, Session $session)
    {
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

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
	{
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

			// Export execute permission.
			if (!$this->AccessControl->check(['Institutions', 'StudentUser', 'excel'])) {
				if (isset($toolbarButtons['export'])) {
					unset($toolbarButtons['export']);
				}
			}

			// POCOR-3010
			$userId = $this->Auth->user('id');
			$studentId = $this->request->pass[1];
			if (!$this->checkClassPermission($studentId, $userId)) {
				if (isset($toolbarButtons['edit'])) {
					unset($toolbarButtons['edit']);
				}
			}
			// End POCOR-3010

			$session = $this->request->session();
			$this->addPromoteButton($buttons, $toolbarButtons, $attr, $session);
			$this->addTransferButton($buttons, $toolbarButtons, $attr, $session);
			$this->addDropoutButton($buttons, $toolbarButtons, $attr, $session);

		} else if ($action == 'add') {
			$backAction = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Students', 'add'];
			$toolbarButtons['back']['url'] = $backAction;
			if ($toolbarButtons->offsetExists('export')) {
				unset($toolbarButtons['export']);
			}
		}
	}

	//to handle identity_number field that is automatically created by mandatory behaviour.
	public function onUpdateFieldIdentityNumber(Event $event, array $attr, $action, Request $request)
	{
		if ($action == 'add') {
			$attr['fieldName'] = $this->alias().'.identities.0.number';
			$attr['attr']['label'] = __('Identity Number');
		}
		return $attr;
	}

	private function checkClassPermission($studentId, $userId)
	{
		$permission = false;
		if (!$this->AccessControl->isAdmin()) {
			$event = $this->controller->dispatchEvent('Controller.SecurityAuthorize.onUpdateRoles', null, $this);
			$roles = [];
            if (is_array($event->result)) {
                $roles = $event->result;
            }
			if (!$this->AccessControl->check(['Institutions', 'AllClasses', $permission], $roles)) {
				$Class = TableRegistry::get('Institution.InstitutionClasses');
				$classStudentRecord = $Class
					->find('ByAccess', [
						'accessControl' => $this->AccessControl,
						'controller' => $this->controller,
						'userId' => $userId,
						'permission' => 'edit'
					])
					->innerJoinWith('ClassStudents')
					->where(['ClassStudents.student_id' => $studentId])
					->toArray();
				if (!empty($classStudentRecord)) {
					$permission = true;
				}
			} else {
				$permission = true;
			}

		} else {
			$permission = true;
		}
		return $permission;
	}

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        foreach ($fields as $key => $field) {
            //get the value from the table, but change the label to become default identity type.
            if ($field['field'] == 'identity_number') {
                $fields[$key] = [
                    'key' => 'StudentUser.identity_number',
                    'field' => 'identity_number',
                    'type' => 'string',
                    'label' => __($identity->name)
                ];
                break;
            }
        }
    }
}
