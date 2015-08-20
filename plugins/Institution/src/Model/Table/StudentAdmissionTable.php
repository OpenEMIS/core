<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use Cake\Network\Request;

class StudentAdmissionTable extends AppTable {
	const NEW_REQUEST = 0;
	const APPROVED = 1;
	const REJECTED = 2;

	public function initialize(array $config) {
		$this->table('institution_student_admission');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('StudentTransferReasons', ['className' => 'FieldOption.StudentTransferReasons']);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$statusToshow = [self::NEW_REQUEST, self::REJECTED];
		$query->where([$this->aliasField('type') => 'Admission', $this->aliasField('status').' IN' => $statusToshow]);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		// Set all selected values only
		$this->request->data[$this->alias()]['status'] = $entity->status;
		$this->request->data[$this->alias()]['security_user_id'] = $entity->security_user_id;
		$this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;
		$this->request->data[$this->alias()]['academic_period_id'] = $entity->academic_period_id;
		$this->request->data[$this->alias()]['education_grade_id'] = $entity->education_grade_id;
		$this->request->data[$this->alias()]['start_date'] = $entity->start_date;
		$this->request->data[$this->alias()]['end_date'] = $entity->end_date;
		$this->request->data[$this->alias()]['comment'] = $entity->comment;
	}

	public function afterAction($event) {
    	$this->ControllerAction->field('student_transfer_reason_id', ['visible' => ['edit' => true, 'index' => false, 'view' => false]]);
    	$this->ControllerAction->field('start_date', ['visible' => ['edit' => true, 'index' => false, 'view' => true]]);
    	$this->ControllerAction->field('end_date', ['visible' => ['edit' => true, 'index' => false, 'view' => true]]);
    	$this->ControllerAction->field('previous_institution_id', ['visible' => ['edit' => true, 'index' => false, 'view' => false]]);
    	$this->ControllerAction->field('type', ['visible' => false]);
    	$this->ControllerAction->field('comment', ['visible' => ['index' => false, 'edit' => true, 'view' => true]]);
    	$this->ControllerAction->field('security_user_id');
    	$this->ControllerAction->field('status');
    	$this->ControllerAction->field('institution_id');
    	$this->ControllerAction->field('academic_period_id');
    	$this->ControllerAction->field('education_grade_id');
    	$this->ControllerAction->field('comment');

    	// $this->ControllerAction->field('modified_user_id', ['visible' => false]);
    	// $this->ControllerAction->field('modified', ['visible' => false]);
    	// $this->ControllerAction->field('created_user_id', ['visible' => false]);
    	// $this->ControllerAction->field('created', ['visible' => false]);
    }

    public function editAfterAction($event) {
		$this->ControllerAction->field('student');
  		$this->ControllerAction->setFieldOrder([
			'status', 'student',
			'institution_id', 'academic_period_id', 'education_grade_id',
			'start_date', 'end_date', 'comment'
		]);
    }

   	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		// $events['Workbench.Model.onGetList'] = 'onGetWorkbenchList';
		return $events;
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

	public function onUpdateFieldStatus(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$status = $request->data[$this->alias()]['status'];
			$attr['type'] = 'readonly';
			if ($status == self::NEW_REQUEST) {
				$attr['attr']['value'] = __('New');
			} else if ($status == self::APPROVED) {
				$attr['attr']['value'] = __('Approved');
			} else if ($status == self::REJECTED) {
				$attr['attr']['value'] = __('Rejected');
			}
			return $attr;
		}
	}

	public function onUpdateFieldStudent(Event $event, array $attr, $action, $request) {
		if ($action=='edit') {
			$student = $request->data[$this->alias()]['security_user_id'];
			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->Users->get($student)->name_with_id;
			return $attr;
		}
	}

	public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$institution = $request->data[$this->alias()]['institution_id'];
			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->Institutions->get($institution)->code_name;
			return $attr;
		}
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$academicPeriod = $request->data[$this->alias()]['academic_period_id'];
			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->AcademicPeriods->get($academicPeriod)->name;
			return $attr;	
		}
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$educationGrade = $request->data[$this->alias()]['education_grade_id'];
			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $this->EducationGrades->get($educationGrade)->programme_grade_name;
			return $attr;	
		}
	}

	public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, $request) {
		if ($action=='edit') {
			$student = $request->data[$this->alias()]['security_user_id'];
			$attr['type'] = 'hidden';
			$attr['attr']['value'] = $student;
			return $attr;
		}
	}

	public function onUpdateFieldPreviousInstitutionId(Event $event, array $attr, $action, $request) {
		if ($action=='edit') {
			$attr['type'] = 'hidden';
			$attr['attr']['value'] = 0;
			return $attr;
		}
	}

	public function onUpdateFieldStudentTransferReasonId(Event $event, array $attr, $action, $request) {
		if ($action=='edit') {
			$attr['type'] = 'hidden';
			$attr['attr']['value'] = 0;
			return $attr;
		}
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$endDate = $request->data[$this->alias()]['end_date'];
			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $endDate->format('d-m-Y');
			return $attr;
		}
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			if ($request->data[$this->alias()]['status'] != self::NEW_REQUEST) {
				$startDate = $request->data[$this->alias()]['start_date'];
				$attr['type'] = 'readonly';
				$attr['attr']['value'] = $startDate->format('d-m-Y');
			}
			return $attr;
		}
	}

	public function onUpdateFieldComment(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			if ($request->data[$this->alias()]['status'] != self::NEW_REQUEST) {
				$attr['type'] = 'readonly';
			}
			return $attr;
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		$newItem = [];
		if ($entity->status == 'New') {
			$newItem['view'] = $buttons['view'];
			$newItem['edit'] = $buttons['edit'];
		} else {
			$newItem['view'] = $buttons['view'];
		}
		$buttons = $newItem;

		return $buttons;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'edit') {
			$toolbarButtons['back']['url'][0] = 'index';
			unset($toolbarButtons['back']['url'][1]);
		} else if ($action == 'view') {
			unset($toolbarButtons['edit']);
		}
	}

	public function onGetSecurityUserId(Event $event, Entity $entity){
		if ($entity->status == self::NEW_REQUEST) {
			$urlParams = $this->ControllerAction->url('index');
			return $event->subject()->Html->link($entity->user->name, [
				'plugin' => $urlParams['plugin'],
				'controller' => $urlParams['controller'],
				'action' => $urlParams['action'],
				'0' => 'edit',
				'1' => $entity->id
			]);
		}
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'edit') {
			// If the status is new application then display the approve and reject button, 
			// if not remove the button just in case the user gets to access the edit page
			if ($this->request->data[$this->alias()]['status'] == self::NEW_REQUEST) {
				$buttons[0] = [
					'name' => '<i class="fa fa-check"></i> ' . __('Approve'),
					'attr' => ['class' => 'btn btn-default', 'div' => false, 'name' => 'submit', 'value' => 'approve']
				];

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

	// // Workbench.Model.onGetList
	// public function onGetWorkbenchList(Event $event, $AccessControl, ArrayObject $data) {
	// 	if ($AccessControl->check(['Dashboard', 'StudentAdmission', 'edit'])) {
	// 		$institutionIds = $AccessControl->getInstitutionsByUser();

	// 		pr($data);

	// 		$where = [$this->aliasField('status') => 0, $this->aliasField('type') => 'Admission'];
	// 		if (!$AccessControl->isAdmin()) {
	// 			$where[$this->aliasField('institution_id') . ' IN '] = $institutionIds;
	// 		}

	// 		$resultSet = $this
	// 			->find()
	// 			->contain(['Users', 'Institutions', 'EducationGrades', 'PreviousInstitutions', 'ModifiedUser', 'CreatedUser'])
	// 			->where($where)
	// 			->order([
	// 				$this->aliasField('created')
	// 			])
	// 			->toArray();

	// 		// foreach ($resultSet as $key => $obj) {
	// 		// 	$requestTitle = sprintf('Transfer of student (%s) from %s to %s', $obj->user->name_with_id, $obj->previous_institution->name, $obj->institution->name);
	// 		// 	$url = [
	// 		// 		'plugin' => false,
	// 		// 		'controller' => 'Dashboard',
	// 		// 		'action' => 'TransferApprovals',
	// 		// 		'edit',
	// 		// 		$obj->id
	// 		// 	];

	// 		// 	$receivedDate = $this->formatDate($obj->modified);
	// 		// 	$data[] = [
	// 		// 		'request_title' => ['title' => $requestTitle, 'url' => $url],
	// 		// 		'receive_date' => $receivedDate,
	// 		// 		'due_date' => '<i class="fa fa-minus"></i>',
	// 		// 		'requester' => $obj->created_user->username,
	// 		// 		'type' => __('Student Transfer')
	// 		// 	];
	// 		// }
	// 	}
	// }

	public function editOnApprove(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$entity->comment = $data['StudentAdmission']['comment'];
		$Students = TableRegistry::get('Institution.Students');
		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
		$statuses = $StudentStatuses->findCodeList();

		$newSchoolId = $entity->institution_id;
		$previousSchoolId = $entity->previous_institution_id;
		$studentId = $entity->security_user_id;
		$periodId = $entity->academic_period_id;
		$gradeId = $entity->education_grade_id;

		$conditions = [
			'institution_id' => $newSchoolId,
			'student_id' => $studentId,
			'academic_period_id' => $periodId,
			'education_grade_id' => $gradeId,
			'student_status_id' => $statuses['CURRENT']
		];

		// check if the student is already in the new school
		if (!$Students->exists($conditions)) { // if not exists
			$startDate = $data[$this->alias()]['start_date'];
			$startDate = date('Y-m-d', strtotime($startDate));

			// add the student to the new school
			$newData = $conditions;
			$newData['start_date'] = $startDate;
			$newData['end_date'] = $entity->end_date->format('Y-m-d');
			$newEntity = $Students->newEntity($newData);
			if ($Students->save($newEntity)) {
				$this->Alert->success('StudentAdmission.approve');
				// Update the status of the admission to be approved
				$entity->start_date = $startDate;
				$entity->status = self::APPROVED;
				if (!$this->save($entity)) {
					$this->log($entity->errors(), 'debug');
				}
			} else {
				$this->Alert->error('general.edit.failed');
				$this->log($newEntity->errors(), 'debug');
			}
		} else {
			$this->Alert->error('StudentAdmission.exists');
		}
		$event->stopPropagation();
		return $this->controller->redirect(['plugin' => false, 'plugin'=>'Institution', 'controller' => 'Institutions', 'action' => 'StudentAdmission']);
	}

	public function editOnReject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$entity->comment = $data['StudentAdmission']['comment'];
		// Update status to 2 => reject
		$this->updateAll(
			['status' => self::REJECTED, 'comment' => $entity->comment], 
			['id' => $entity->id]);
		// End

		$this->Alert->success('StudentAdmission.reject');
		$event->stopPropagation();
		return $this->controller->redirect(['plugin' => false, 'plugin'=>'Institution', 'controller' => 'Institutions', 'action' => 'StudentAdmission']);
	}
}
