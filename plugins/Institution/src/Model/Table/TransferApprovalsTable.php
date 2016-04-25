<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\I18n\Time;
use Cake\I18n\Date;

class TransferApprovalsTable extends AppTable {
	const NEW_REQUEST = 0;
	const APPROVED = 1;
	const REJECTED = 2;
	
	// Type status for admission
	const TRANSFER = 2;
	const ADMISSION = 1;

	public function initialize(array $config) {
		$this->table('institution_student_admission');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('StudentTransferReasons', ['className' => 'FieldOption.StudentTransferReasons']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		$validator->notEmpty('institution_class');
		return $validator;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		$events['Workbench.Model.onGetList'] = 'onGetWorkbenchList';
		return $events;
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		//set current date to start date
		$entity->start_date = new Date();

		// Set all selected values only
		$this->request->data[$this->alias()]['transfer_status'] = $entity->status;
		$this->request->data[$this->alias()]['student_id'] = $entity->student_id;
		$this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;
		$this->request->data[$this->alias()]['academic_period_id'] = $entity->academic_period_id;
		$this->request->data[$this->alias()]['education_grade_id'] = $entity->education_grade_id;
		$this->request->data[$this->alias()]['start_date'] = $entity->start_date;
		$this->request->data[$this->alias()]['end_date'] = $entity->end_date;
		$this->request->data[$this->alias()]['student_transfer_reason_id'] = $entity->student_transfer_reason_id;
		$this->request->data[$this->alias()]['status'] = $entity->status;
	}

	public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$errors = $entity->errors();

		if (empty($errors)) {
			$Students = TableRegistry::get('Institution.Students');
			$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
			$EducationGradesTable = TableRegistry::get('Education.EducationGrades');

			$statuses = $StudentStatuses->findCodeList();

			$newSchoolId = $entity->institution_id;
			$previousSchoolId = $entity->previous_institution_id;
			$studentId = $entity->student_id;
            $periodId = $entity->academic_period_id;
			$gradeId = $entity->education_grade_id;
			$newSystemId = TableRegistry::get('Education.EducationGrades')->getEducationSystemId($gradeId);

			$validateEnrolledInAnyInstitutionResult = $Students->validateEnrolledInAnyInstitution($studentId, $newSystemId, ['targetInstitutionId' => $newSchoolId]);
			if (!empty($validateEnrolledInAnyInstitutionResult)) {
				$this->Alert->error($validateEnrolledInAnyInstitutionResult, ['type' => 'message']);
			} else if ($Students->completedGrade($gradeId, $studentId)) {
				$this->Alert->error('Institution.Students.student_name.ruleStudentNotCompletedGrade');
			} else { // if not exists
				$startDate = $data[$this->alias()]['start_date'];
				$startDate = date('Y-m-d', strtotime($startDate));

				// add the student to the new school
				$newData = [
                    'institution_id' => $newSchoolId,
                    'student_id' => $studentId,
                    'academic_period_id' => $periodId,
                    'education_grade_id' => $gradeId,
                    'student_status_id' => $statuses['CURRENT']
                ];
				$newData['start_date'] = $startDate;
				$newData['end_date'] = $entity->end_date->format('Y-m-d');
				$newEntity = $Students->newEntity($newData);
				if ($Students->save($newEntity)) {
					$classId = $data[$this->alias()]['institution_class'];
					$InstitutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
					$institutionClassStudentObj = [
						'student_id' => $newEntity->student_id,
						'institution_class_id' => $classId,
						'education_grade_id' => $newEntity->education_grade_id,
						'student_status_id' => $newEntity->student_status_id,
						'institution_id' => $newEntity->institution_id,
						'academic_period_id' => $newEntity->academic_period_id
					];
					$InstitutionClassStudentsTable->autoInsertClassStudent($institutionClassStudentObj);

					$this->Alert->success('TransferApprovals.approve');
					$existingStudentEntity = $Students->find()->where([
							$Students->aliasField('institution_id') => $previousSchoolId,
							$Students->aliasField('student_id') => $studentId,
							$Students->aliasField('academic_period_id') => $periodId,
							$Students->aliasField('education_grade_id') => $gradeId,
							$Students->aliasField('student_status_id') => $statuses['CURRENT']
						])
						->first();

                    // if cannot be found (perhaps is a promoted/graduated transfer record). then dont change the record
                    if (!empty($existingStudentEntity)) {
                        $existingStudentEntity->student_status_id = $statuses['TRANSFERRED'];
                        $existingStudentEntity->end_date = $startDate;
                        $Students->save($existingStudentEntity);
                    }
					
					$EducationGradesTable = TableRegistry::get('Education.EducationGrades');

					$educationSystemId = $EducationGradesTable->getEducationSystemId($gradeId);
					$educationGradesToUpdate = $EducationGradesTable->getEducationGradesBySystem($educationSystemId);

					$conditions = [
						'student_id' => $studentId,
						'status' => self::NEW_REQUEST,
						'education_grade_id IN' => $educationGradesToUpdate
					];

					// Reject all other new pending admission / transfer application entry of the 
					// same student for the same academic period
					$this->updateAll(
						['status' => self::REJECTED],
						[$conditions]
					);

					// finally update the transfer request to become approved
					$entity->start_date = $startDate;
					$entity->status = self::APPROVED;
					if (!$this->save($entity)) {
						$this->Alert->error('general.edit.failed');
						$this->log($entity->errors(), 'debug');
					}
				} else {
					$this->Alert->error('general.edit.failed');
					$this->log($newEntity->errors(), 'debug');
				}
			}

			// To redirect back to the student admission if it is not access from the workbench
			$urlParams = $this->ControllerAction->url('index');
			$plugin = false;
			$controller = 'Dashboard';
			$action = 'index';
			if ($urlParams['controller'] == 'Institutions') {
				$plugin = 'Institution';
				$controller = 'Institutions';
				$action = 'StudentAdmission';
			}

			$event->stopPropagation();
			return $this->controller->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => $action]);
		} else {
			// required for validation to work
			$process = function($model, $entity) {
				return false;
			};

			return $process;
		}
	}

	public function editAfterAction(Event $event, Entity $entity) {
		if ($entity->type != self::TRANSFER) {
			$event->stopPropagation();
			return $this->controller->redirect(['controller' => 'Institutions', 'action' => 'Students', 'plugin'=>'Institution']);
		}
		$this->ControllerAction->field('transfer_status');
		$this->ControllerAction->field('student');
		$this->ControllerAction->field('student_id');
		$this->ControllerAction->field('institution_id');
		$this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
		$this->ControllerAction->field('education_grade_id');
		$this->ControllerAction->field('status', ['type' => 'hidden']);
		$this->ControllerAction->field('start_date');
		$this->ControllerAction->field('end_date');
		$this->ControllerAction->field('student_transfer_reason_id', ['type' => 'select']);
		$this->ControllerAction->field('comment');
		$this->ControllerAction->field('previous_institution_id', ['type' => 'hidden']);
		$this->ControllerAction->field('type', ['type' => 'hidden', 'value' => self::TRANSFER]);
		$this->ControllerAction->field('created', ['type' => 'disabled', 'attr' => ['value' => $this->formatDate($entity->created)]]);
		$this->ControllerAction->field('institution_class', ['type' => 'select']);

		$this->ControllerAction->setFieldOrder([
			'created', 'transfer_status', 'student',
			'institution_id', 'academic_period_id', 'education_grade_id', 'institution_class',
			'status', 'start_date', 'end_date',
			'student_transfer_reason_id', 'comment',
			'previous_institution_id'
		]);
		$urlParams = $this->ControllerAction->url('edit');
		if ($urlParams['controller'] == 'Dashboard') {
			$this->Navigation->addCrumb('Transfer Approvals', $urlParams);
		}
	}

	public function onUpdateFieldInstitutionClass(Event $event, array $attr, $action, $request) {
		$academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
		$institutionId = $this->request->data[$this->alias()]['institution_id'];
		$educationGradeId = $this->request->data[$this->alias()]['education_grade_id'];
		$listOfClasses = $this->Institutions->find('list', [
				'keyField' => 'class_id',
				'valueField' => 'class_name'
			])
			->matching('InstitutionClasses.ClassGrades')
			->where([
				'ClassGrades.education_grade_id' => $educationGradeId,
				'InstitutionClasses.academic_period_id' => $academicPeriodId,
				$this->Institutions->aliasField('id') => $institutionId
			])
			->select(['class_id' => 'InstitutionClasses.id', 'class_name' => 'InstitutionClasses.name'])
			->hydrate(false)
			->toArray()
			;
		if (count($listOfClasses) == 0) {
			$options = ['' => __('No Available Classes')];
		} else {
			$options = $listOfClasses;
		}
		$attr['options'] = $options;
		return $attr;
	}

	public function viewAfterAction($event, Entity $entity) {
		$this->request->data[$this->alias()]['status'] = $entity->status;
		$this->ControllerAction->field('type', ['visible'=>	false]);
		$this->ControllerAction->setFieldOrder([
			'created', 'status', 'student_id',
			'institution_id', 'academic_period_id', 'education_grade_id',
			'start_date', 'end_date', 'comment'
		]);
    }

    public function onGetStatus(Event $event, Entity $entity) {
		$statusName = "";
		switch ($entity->status) {
			case self::NEW_REQUEST:
				$statusName = "New";
				break;
			case self::APPROVED:
				$statusName = "Approved";
				break;
			case self::REJECTED:
				$statusName = "Rejected";
				break;
			default:
				$statusName = $entity->status;
				break;
		}
		return __($statusName);
	}



	public function onUpdateFieldTransferStatus(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$transferStatus = $request->data[$this->alias()]['transfer_status'];

			$attr['type'] = 'readonly';
			$attr['value'] = $transferStatus;
			if ($transferStatus == 0) {
				$attr['attr']['value'] = __('New');
			} else if ($transferStatus == 1) {
				$attr['attr']['value'] = __('Approve');
			} else if ($transferStatus == 2) {
				$attr['attr']['value'] = __('Reject');
			}
		}

		return $attr;
	}

	public function onUpdateFieldStudent(Event $event, array $attr, $action, $request) {
		$selectedStudent = $request->data[$this->alias()]['student_id'];

		$attr['type'] = 'readonly';
		$attr['attr']['value'] = $this->Users->get($selectedStudent)->name_with_id;

		return $attr;
	}

	public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request) {
		$selectedStudent = $request->data[$this->alias()]['student_id'];

		$attr['type'] = 'hidden';
		$attr['attr']['value'] = $selectedStudent;

		return $attr;
	}

	public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$selectedInstitution = $request->data[$this->alias()]['institution_id'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->Institutions->get($selectedInstitution)->code_name;
		}

		return $attr;
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$selectedGrade = $request->data[$this->alias()]['education_grade_id'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->EducationGrades->get($selectedGrade)->programme_grade_name;
		}

		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			// If it is not a new request, disable this field
			if ($this->request->data[$this->alias()]['status'] != self::NEW_REQUEST || (!$this->AccessControl->check(['Institutions', 'TransferApprovals', 'edit']))) {
				$startDate = $request->data[$this->alias()]['start_date'];
				$attr['type'] = 'readonly';
				$attr['attr']['value'] = $startDate->format('d-m-Y');
				return $attr;
			}
			
			$selectedPeriod = $request->data[$this->alias()]['academic_period_id'];
			$startDate = $request->data[$this->alias()]['start_date'];
			if (!is_object($startDate)) {
				$startDate = new Date(date('Y-m-d', strtotime($startDate)));
				$request->data[$this->alias()]['start_date'] = $startDate;
			}
			$endDate = $request->data[$this->alias()]['end_date'];
			if (!is_object($endDate)) {
				$endDate = new Date(date('Y-m-d', strtotime($endDate)));
				$request->data[$this->alias()]['end_date'] = $endDate;
			}

			$periodStartDate = $this->AcademicPeriods->get($selectedPeriod)->start_date;
			$periodEndDate = $this->AcademicPeriods->get($selectedPeriod)->end_date;
			if (!is_null($endDate)) {
				$periodEndDate = $endDate->copy()->subDay();
			}

			$attr['attr']['value'] = date('d-m-Y', strtotime($startDate));
			$attr['date_options'] = ['startDate' => $periodStartDate->format('d-m-Y'), 'endDate' => $periodEndDate->format('d-m-Y')];
		}

		return $attr;
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$endDate = $request->data[$this->alias()]['end_date'];
			$attr['type'] = 'readonly';
			$attr['value'] = $endDate->format('d-m-Y');
			$attr['attr']['value'] = $endDate->format('d-m-Y');
		}

		return $attr;
	}

	public function onUpdateFieldStudentTransferReasonId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$selectedReason = $request->data[$this->alias()]['student_transfer_reason_id'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->StudentTransferReasons->get($selectedReason)->name;
		}

		return $attr;
	}

	public function onUpdateFieldComment(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$attr['attr']['disabled'] = 'disabled';
		}

		return $attr;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'edit' || $action == 'view') {
			if ($toolbarButtons['back']['url']['controller']=='Dashboard') {
				$toolbarButtons['back']['url']['action']= 'index';
				unset($toolbarButtons['back']['url'][0]);
				unset($toolbarButtons['back']['url'][1]);
			} else if ($toolbarButtons['back']['url']['controller']=='Institutions') {
				$toolbarButtons['back']['url']['action']= 'StudentAdmission';
				unset($toolbarButtons['back']['url'][0]);
				unset($toolbarButtons['back']['url'][1]);
			}
		}

		if ($action == 'view') {
			if ($this->request->data[$this->alias()]['status'] != self::NEW_REQUEST || (!$this->AccessControl->check(['Institutions', 'TransferApprovals', 'edit']))) {
				unset($toolbarButtons['edit']);
			}
		}
	}

	// Workbench.Model.onGetList
	public function onGetWorkbenchList(Event $event, $AccessControl, ArrayObject $data) {
		if ($AccessControl->check(['Institutions', 'TransferApprovals', 'edit'])) {
			// $institutionIds = $AccessControl->getInstitutionsByUser(null, ['Dashboard', 'TransferApprovals', 'edit']);
			$institutionIds = $AccessControl->getInstitutionsByUser();

			$where = [$this->aliasField('status') => 0, $this->aliasField('type') => self::TRANSFER];
			if (!$AccessControl->isAdmin()) {
				if (!empty($institutionIds)) {
					$where[$this->aliasField('institution_id') . ' IN '] = $institutionIds;
				} else {
					$where[$this->aliasField('institution_id')] = '-1';
				}
			}

			$resultSet = $this
				->find()
				->contain(['Users', 'Institutions', 'EducationGrades', 'PreviousInstitutions', 'ModifiedUser', 'CreatedUser'])
				->where($where)
				->order([
					$this->aliasField('modified') => 'DESC'
				])
				->toArray();

			foreach ($resultSet as $key => $obj) {
				$requestTitle = sprintf('Transfer of student (%s) from %s to %s', $obj->user->name_with_id, $obj->previous_institution->name, $obj->institution->name);
				$url = [
					'plugin' => false,
					'controller' => 'Dashboard',
					'action' => 'TransferApprovals',
					'edit',
					$obj->id
				];

				if (is_null($obj->modified)) {
					$receivedDate = $this->formatDate($obj->created);
				} else {
					$receivedDate = $this->formatDate($obj->modified);
				}
				
				$data[] = [
					'request_title' => ['title' => $requestTitle, 'url' => $url],
					'receive_date' => $receivedDate,
					'due_date' => '<i class="fa fa-minus"></i>',
					'requester' => $obj->created_user->username,
					'type' => __('Transfer')
				];
			}
		}
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'edit') {
			// If the status is new application then display the approve and reject button, 
			// if not remove the button just in case the user gets to access the edit page
			if ($this->request->data[$this->alias()]['status'] == self::NEW_REQUEST || !($this->AccessControl->check(['Institutions', 'TransferApprovals', 'edit']))) {
				$buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Approve');

				$buttons[1] = [
					'name' => '<i class="fa fa-close"></i> ' . __('Reject'),
					'attr' => ['class' => 'btn btn-outline btn-cancel', 'div' => false, 'name' => 'submit', 'value' => 'reject']
				];
			} else {
				unset($buttons[0]);
				unset($buttons[1]);
			}
		}
	}

	public function editOnReject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// Update status to Current in previous school
		$institutionId = $entity->previous_institution_id;
		$selectedStudent = $entity->student_id;
		$selectedPeriod = $entity->academic_period_id;
		$selectedGrade = $entity->education_grade_id;

		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
		$currentStatus = $StudentStatuses->getIdByCode('CURRENT');

		$Students = TableRegistry::get('Institution.Students');
		$Students->updateAll(
			['student_status_id' => $currentStatus],
			[
				'institution_id' => $institutionId,
				'student_id' => $selectedStudent,
				'academic_period_id' => $selectedPeriod,
				'education_grade_id' => $selectedGrade
			]
		);
		// End

		// Update status to 2 => reject
		$this->updateAll(['status' => self::REJECTED], ['id' => $entity->id]);
		// End

		$this->Alert->success('TransferApprovals.reject');
		
		// To redirect back to the student admission if it is not access from the workbench
		$urlParams = $this->ControllerAction->url('index');
		$plugin = false;
		$controller = 'Dashboard';
		$action = 'index';
		if ($urlParams['controller'] == 'Institutions') {
			$plugin = 'Institution';
			$controller = 'Institutions';
			$action = 'StudentAdmission';
		}

		$event->stopPropagation();
		return $this->controller->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => $action]);
	}
}
