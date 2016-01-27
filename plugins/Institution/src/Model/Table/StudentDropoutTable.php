<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\ORM\Query;

class StudentDropoutTable extends AppTable {
	const NEW_REQUEST = 0;
	const APPROVED = 1;
	const REJECTED = 2;

	public function initialize(array $config) {
		$this->table('institution_student_dropout');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('StudentDropoutReasons', ['className' => 'FieldOption.StudentDropoutReasons', 'foreignKey' => 'student_dropout_reason_id']);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$statusToshow = [self::NEW_REQUEST, self::REJECTED];
		$query->where([$this->aliasField('status').' IN' => $statusToshow]);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		$events['Workbench.Model.onGetList'] = 'onGetWorkbenchList';
		return $events;
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['status'] = $entity->status;
		$this->request->data[$this->alias()]['effective_date'] = $entity->start_date;
	}

	public function afterAction($event) {
    	$this->ControllerAction->field('student_dropout_reason_id', ['visible' => ['edit' => true, 'index' => false, 'view' => false]]);
    	$this->ControllerAction->field('effective_date', ['visible' => ['edit' => true, 'index' => false, 'view' => true]]);
    	$this->ControllerAction->field('comment', ['visible' => ['index' => false, 'edit' => true, 'view' => true]]);
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
		$this->ControllerAction->field('student_dropout_reason_id', ['type' => 'hidden']);
		$this->ControllerAction->field('created', ['type' => 'disabled', 'attr' => ['value' => $this->formatDate($entity->created)]]);
  		$this->ControllerAction->setFieldOrder([
			'created', 'status', 'student_id',
			'institution_id', 'academic_period_id', 'education_grade_id',
			'effective_date', 'comment', 
		]);

		$urlParams = $this->ControllerAction->url('edit');
		if ($urlParams['controller'] == 'Dashboard') {
			$this->Navigation->addCrumb('Dropout Approvals', $urlParams);
		}
    }

    public function viewAfterAction($event, Entity $entity) {
    	$this->request->data[$this->alias()]['status'] = $entity->status;
		$this->ControllerAction->setFieldOrder([
			'created', 'status', 'student_id',
			'institution_id', 'academic_period_id', 'education_grade_id',
			'effective_date', 'comment'
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
	
	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'edit') {
			// If the status is new application then display the approve and reject button, 
			// if not remove the button just in case the user gets to access the edit page
			if ($this->request->data[$this->alias()]['status'] == self::NEW_REQUEST && ($this->AccessControl->check(['Institutions', $this->alias(), 'edit']))) {
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

	public function onGetStudentId(Event $event, Entity $entity){
		$urlParams = $this->ControllerAction->url('index');
		if ($entity->status == self::NEW_REQUEST) {
			if ($this->AccessControl->check(['Institutions', $this->alias(), 'edit'])) {
				return $event->subject()->Html->link($entity->user->name, [
					'plugin' => $urlParams['plugin'],
					'controller' => $urlParams['controller'],
					'action' => $urlParams['action'],
					'0' => 'edit',
					'1' => $entity->id
				]);
			}
		}
	}

	// Workbench.Model.onGetList
	public function onGetWorkbenchList(Event $event, $AccessControl, ArrayObject $data) {
		if ($AccessControl->check(['Institutions', $this->alias(), 'edit'])) {
			$institutionIds = $AccessControl->getInstitutionsByUser();

			$where = [$this->aliasField('status') => 0];
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
				$requestTitle = sprintf('Dropout request from (%s) in %s', $obj->user->name_with_id, $obj->institution->name);
				$url = [
					'plugin' => false,
					'controller' => 'Dashboard',
					'action' => $this->alias(),
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
					'type' => __('Dropout')
				];
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

	public function onUpdateFieldComment(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			if ($request->data[$this->alias()]['status'] != self::NEW_REQUEST || !($this->AccessControl->check(['Institutions', $this->alias(), 'edit']))) {
				$attr['type'] = 'readonly';
			}
			return $attr;
		}
	}

	public function onUpdateFieldEffectiveDate(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {
			if ($request->data[$this->alias()]['status'] != self::NEW_REQUEST || !($this->AccessControl->check(['Institutions', $this->alias(), 'edit']))) {
				$attr['type'] = 'readonly';
			}
			return $attr;
		}
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

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		$newItem = [];
		$status = $this->get($entity->id)->status;
		if ($status == self::NEW_REQUEST) {
			if (isset($buttons['view'])) {
				$newItem['view'] = $buttons['view'];
			}
			if ($this->AccessControl->check(['Institutions', $this->alias(), 'edit'])) {
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

	public function editOnApprove(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$entity->comment = $data[$this->alias()]['comment'];
		$effectiveDate = strtotime($data[$this->alias()]['effective_date']);
		$Students = TableRegistry::get('Institution.Students');
		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
		$statuses = $StudentStatuses->findCodeList();

		$institutionId = $entity->institution_id;
		$studentId = $entity->student_id;
		$periodId = $entity->academic_period_id;
		$gradeId = $entity->education_grade_id;

		$conditions = [
			'institution_id' => $institutionId,
			'student_id' => $studentId,
			'academic_period_id' => $periodId,
			'education_grade_id' => $gradeId,
			'student_status_id' => $statuses['DROPOUT']
		];

		$newData = $conditions;

		// If the student is not already drop out
		if (!$Students->exists($conditions)) {
			// Change the status of the student in the school
			// Update only enrolled statuses student
			$Students->updateAll(
				['student_status_id' => $statuses['DROPOUT'], 'end_date' => $effectiveDate],
				[
					'institution_id' => $institutionId,
					'student_id' => $studentId,
					'academic_period_id' => $periodId,
					'education_grade_id' => $gradeId,
					'student_status_id' => $statuses['CURRENT']
				]
			);

			$this->Alert->success('StudentDropout.approve');

			$entity->status = self::APPROVED;
			$entity->effective_date = date('Y-m-d', $effectiveDate);
			if (!$this->save($entity)) {
				$this->log($entity->errors(), 'debug');
			}
		} else {
			$this->Alert->error('StudentDropout.exists');
		}

		// To redirect back to the student admission if it is not access from the workbench
		$urlParams = $this->ControllerAction->url('index');
		$plugin = false;
		$controller = 'Dashboard';
		$action = 'index';
		if ($urlParams['controller'] == 'Institutions') {
			$plugin = 'Institution';
			$controller = 'Institutions';
			$action = 'StudentDropout';
		}

		$event->stopPropagation();
		return $this->controller->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => $action]);
	}

	public function editOnReject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$this->updateAll(
			['status' => self::REJECTED, 'comment' => $data[$this->alias()]['comment'], 'effective_date' => strtotime($data[$this->alias()]['effective_date'])], 
			['id' => $entity->id]);

		$this->Alert->success('StudentDropout.reject');

		// To redirect back to the student admission if it is not access from the workbench
		$urlParams = $this->ControllerAction->url('index');
		$plugin = false;
		$controller = 'Dashboard';
		$action = 'index';
		if ($urlParams['controller'] == 'Institutions') {
			$plugin = 'Institution';
			$controller = 'Institutions';
			$action = $this->alias();
		}

		$event->stopPropagation();
		return $this->controller->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => $action]);
	}
}
