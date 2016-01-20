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

		$this->addBehavior('User.AdvancedNameSearch');
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$options['auto_search'] = false;

		$statusToshow = [self::NEW_REQUEST, self::REJECTED];
		$typeToShow = [];

		if ($this->AccessControl->check(['Institutions', 'TransferApprovals', 'view'])) {
			$typeToShow[] = self::TRANSFER;
		}

		if ($this->AccessControl->check(['Institutions', 'StudentAdmission', 'view'])) {
			$typeToShow[] = self::ADMISSION;
		}

		$query->where([$this->aliasField('type').' IN' => $typeToShow, $this->aliasField('status').' IN' => $statusToshow]);



		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
		}
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['status'] = $entity->status;
		$this->request->data[$this->alias()]['start_date'] = $entity->start_date;
		$this->request->data[$this->alias()]['end_date'] = $entity->end_date;
		$this->request->data[$this->alias()]['type'] = $entity->type;
	}

	public function afterAction($event) {
    	$this->ControllerAction->field('student_transfer_reason_id', ['visible' => ['edit' => true, 'index' => false, 'view' => false]]);
    	$this->ControllerAction->field('start_date', ['visible' => ['edit' => true, 'index' => false, 'view' => true]]);
    	$this->ControllerAction->field('end_date', ['visible' => ['edit' => true, 'index' => false, 'view' => true]]);
    	$this->ControllerAction->field('previous_institution_id', ['visible' => ['edit' => true, 'index' => false, 'view' => false]]);
    	$this->ControllerAction->field('type');
    	$this->ControllerAction->field('comment', ['visible' => ['index' => false, 'edit' => true, 'view' => true]]);
    	if ($this->action == 'index') {
    		$this->ControllerAction->field('openemis_no');
    	}
    	$this->ControllerAction->field('student_id');
    	$this->ControllerAction->field('status');
    	$this->ControllerAction->field('institution_id', ['visible' => ['index' => false, 'edit' => true, 'view' => 'true']]);
    	$this->ControllerAction->field('academic_period_id', ['type' => 'readonly']);
    	$this->ControllerAction->field('education_grade_id');
    	$this->ControllerAction->field('comment');
    	$this->ControllerAction->field('created', ['visible' => ['index' => false, 'edit' => true, 'view' => true]]);
    }

    public function editAfterAction($event, Entity $entity) {
		$this->ControllerAction->field('student_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->student_id)->name_with_id]]);
		$this->ControllerAction->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->code_name]]);
		$this->ControllerAction->field('academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $this->AcademicPeriods->get($entity->academic_period_id)->name]]);
		$this->ControllerAction->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $this->EducationGrades->get($entity->education_grade_id)->programme_grade_name]]);
		$this->ControllerAction->field('student_transfer_reason_id', ['type' => 'hidden']);
		$this->ControllerAction->field('previous_institution_id', ['type' => 'hidden']);
		$this->ControllerAction->field('created', ['type' => 'disabled', 'attr' => ['value' => $this->formatDate($entity->created)]]);
  		$this->ControllerAction->setFieldOrder([
			'created', 'status', 'type', 'student_id',
			'institution_id', 'academic_period_id', 'education_grade_id',
			'start_date', 'end_date', 'comment', 
		]);

		$urlParams = $this->ControllerAction->url('edit');
		if ($urlParams['controller'] == 'Dashboard') {
			$this->Navigation->addCrumb('Student Admission', $urlParams);
		}
    }

    public function viewAfterAction($event, Entity $entity) {
    	$this->request->data[$this->alias()]['status'] = $entity->status;
		$this->ControllerAction->setFieldOrder([
			'created', 'status', 'type', 'student_id',
			'institution_id', 'academic_period_id', 'education_grade_id',
			'start_date', 'end_date', 'comment'
		]);
    }

   	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		$events['Workbench.Model.onGetList'] = 'onGetWorkbenchList';
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

	public function onGetType(Event $event, Entity $entity) {
		$typeName = "";
		switch ($entity->type) {
			case self::ADMISSION:
				$typeName = "Admission";
				break;
			case self::TRANSFER:
				$typeName = "Transfer";
				break;
			default:
				$typeName = $entity->status;
				break;
		}
		return __($typeName);
	}
	public function onGetOpenemisNo(Event $event, Entity $entity){
		return $entity->user->openemis_no;
	}

	public function onGetStudentId(Event $event, Entity $entity){
		$urlParams = $this->ControllerAction->url('index');
		$action = $urlParams['action'];
		if ($entity->type == self::TRANSFER) {
			$action = 'TransferApprovals';
		}
		if ($entity->status == self::NEW_REQUEST) {
			if ($this->AccessControl->check(['Institutions', 'StudentAdmission', 'edit'])) {
				return $event->subject()->Html->link($entity->user->name, [
					'plugin' => $urlParams['plugin'],
					'controller' => $urlParams['controller'],
					'action' => $action,
					'0' => 'edit',
					'1' => $entity->id
				]);
			}
		}
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

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'edit') {
			// If the status is new application then display the approve and reject button, 
			// if not remove the button just in case the user gets to access the edit page
			if ($this->request->data[$this->alias()]['status'] == self::NEW_REQUEST && ($this->AccessControl->check(['Institutions', 'StudentAdmission', 'edit']))) {
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

	// Workbench.Model.onGetList
	public function onGetWorkbenchList(Event $event, $AccessControl, ArrayObject $data) {
		if ($AccessControl->check(['Institutions', 'StudentAdmission', 'edit'])) {
			$institutionIds = $AccessControl->getInstitutionsByUser();

			$where = [$this->aliasField('status') => 0, $this->aliasField('type') => self::ADMISSION];
			if (!$AccessControl->isAdmin()) {
				$where[$this->aliasField('institution_id') . ' IN '] = $institutionIds;
			}

			$resultSet = $this
				->find()
				->contain(['Users', 'Institutions', 'EducationGrades', 'ModifiedUser', 'CreatedUser'])
				->where($where)
				->order([
					$this->aliasField('created')
				])
				->toArray();

			foreach ($resultSet as $key => $obj) {
				$requestTitle = sprintf('Admission of student (%s) to %s', $obj->user->name_with_id, $obj->institution->name);
				$url = [
					'plugin' => false,
					'controller' => 'Dashboard',
					'action' => 'StudentAdmission',
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
					'type' => __('Admission')
				];
			}
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
			if ($request->data[$this->alias()]['status'] != self::NEW_REQUEST || !($this->AccessControl->check(['Institutions', 'StudentAdmission', 'edit']))) {
				$startDate = $request->data[$this->alias()]['start_date'];
				$attr['type'] = 'readonly';
				$attr['attr']['value'] = $startDate->format('d-m-Y');
			}
			return $attr;
		}
	}

	public function onUpdateFieldComment(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			if ($request->data[$this->alias()]['status'] != self::NEW_REQUEST || !($this->AccessControl->check(['Institutions', 'StudentAdmission', 'edit']))) {
				$attr['type'] = 'readonly';
			}
			return $attr;
		}
	}

	public function onUpdateFieldType(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			$type = $request->data[$this->alias()]['type'];
			$attr['type'] = 'readonly';
			$typeName = "";
			switch ($type) {
				case self::ADMISSION:
					$typeName = "Admission";
					break;
				case self::TRANSFER:
					$typeName = "Transfer";
					break;
			}
			$attr['attr']['value'] = $typeName;
			return $attr;
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		$newItem = [];

		$status = $this->get($entity->id)->status;
		if ($status == self::NEW_REQUEST) {
			if (isset($buttons['view'])) {
				$newItem['view'] = $buttons['view'];
			}
			if ($this->AccessControl->check(['Institutions', 'StudentAdmission', 'edit'])) {
				if (isset($buttons['edit'])) {
					$newItem['edit'] = $buttons['edit'];
				}
			}
		} else {
			if (isset($buttons['view'])) {
				$newItem['view'] = $buttons['view'];
			}
		}
		$buttons = $newItem;

		return $buttons;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'index') {
			$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
			$toolbarButtons['back']['attr']['title'] = __('Back');
			$toolbarButtons['back']['url']['plugin'] = 'Institution';
			$toolbarButtons['back']['url']['controller'] = 'Institutions';
			$toolbarButtons['back']['url']['action'] = 'Students';
			$toolbarButtons['back']['url'][0] = 'index';
			$toolbarButtons['back']['attr'] = $attr;
		}

		if ($action == 'edit') {
			$toolbarButtons['back']['url'][0] = 'index';
			if ($toolbarButtons['back']['url']['controller']=='Dashboard') {
				$toolbarButtons['back']['url']['action']= 'index';
				unset($toolbarButtons['back']['url'][0]);
			}
			unset($toolbarButtons['back']['url'][1]);
		} else if ($action == 'view') {
			if ($this->request->data[$this->alias()]['status'] != self::NEW_REQUEST) {
				unset($toolbarButtons['edit']);
			}
		}
	}

	public function editOnApprove(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$entity->comment = $data['StudentAdmission']['comment'];
		$Students = TableRegistry::get('Institution.Students');
		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
		$statuses = $StudentStatuses->findCodeList();

		$newSchoolId = $entity->institution_id;
		$previousSchoolId = $entity->previous_institution_id;
		$studentId = $entity->student_id;
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

				$EducationGradesTable = TableRegistry::get('Education.EducationGrades');

				$educationSystemId = $EducationGradesTable->getEducationSystemId($entity->education_grade_id);
				$educationGradesToUpdate = $EducationGradesTable->getEducationGradesBySystem($educationSystemId);

				$conditions = [
					'student_id' => $entity->student_id, 
					'status' => self::NEW_REQUEST,
					'education_grade_id IN' => $educationGradesToUpdate
				];

				// Reject all other new pending admission / transfer application entry of the 
				// same student for the same academic period
				$this->updateAll(
					['status' => self::REJECTED],
					[$conditions]
				);

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

	public function editOnReject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {

		$entity->comment = $data['StudentAdmission']['comment'];
		$this->updateAll(
			['status' => self::REJECTED, 'comment' => $entity->comment], 
			['id' => $entity->id]);

		$this->Alert->success('StudentAdmission.reject');

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
