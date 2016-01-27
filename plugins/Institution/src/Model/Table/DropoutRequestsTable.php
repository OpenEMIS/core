<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;

class DropoutRequestsTable extends AppTable {
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

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
    	$id = $this->Session->read('Students.Student.id');
    	$action = $this->ControllerAction->url('add');
		$action['action'] = 'StudentUser';
		$action[0] = 'view';
		$action[1] = $id;
    	$event->stopPropagation();
    	$this->Session->delete($this->registryAlias().'.id');
    	return $this->controller->redirect($action);
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $requestData) {
		$StudentAdmissionTable = TableRegistry::get('Institution.StudentAdmission');

		$conditions = [
			'student_id' => $entity->student_id, 
			'status' => self::NEW_REQUEST,
			'type' => 2,
			'education_grade_id' => $entity->education_grade_id,
			'previous_institution_id' => $entity->institution_id
		];

		$count = $StudentAdmissionTable->find()
			->where($conditions)
			->count();

		if ($count > 0) {
			$process = function ($model, $entity) {
				$this->Alert->error('StudentDropout.hasTransferApplication');
			};
			return $process;
		}
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $data) {
		$id = $this->Session->read('Students.Student.id');
    	$action = $this->ControllerAction->url('edit');
		$action['action'] = 'StudentUser';
		$action[0] = 'view';
		$action[1] = $id;
    	$event->stopPropagation();
    	$this->Session->delete($this->registryAlias().'.id');
    	return $this->controller->redirect($action);
	}

	public function addAfterAction(Event $event, Entity $entity) {
		if ($this->Session->check($this->registryAlias().'.id')) {
			$this->ControllerAction->field('application_status');
			$this->ControllerAction->field('status', ['type' => 'hidden', 'attr' => ['value' => self::NEW_REQUEST]]);
			$this->ControllerAction->field('student_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->student_id)->name_with_id]]);
			$this->ControllerAction->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->code_name]]);
			$this->ControllerAction->field('academic_period_id', ['type' => 'hidden', 'attr' => ['value' => $entity->academic_period_id]]);
			$this->ControllerAction->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $this->EducationGrades->get($entity->education_grade_id)->programme_grade_name]]);
			$this->ControllerAction->field('effective_date');
			$this->ControllerAction->field('student_dropout_reason_id', ['type' => 'select']);
			$this->ControllerAction->field('comment');

			$this->ControllerAction->setFieldOrder([
				'application_status','student_id','institution_id', 'academic_period_id', 'education_grade_id',
				'effective_date',
				'student_dropout_reason_id', 'comment',
			]);
		} else {
			$Students = TableRegistry::get('Institution.Students');
			$action = $this->ControllerAction->url('index');
			$action['action'] = $Students->alias();
			$event->stopPropagation();
			return $this->controller->redirect($action);
		}
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('application_status');
		$this->ControllerAction->field('status', ['type' => 'hidden']);
		$this->ControllerAction->field('student_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->student_id)->name_with_id]]);
		$this->ControllerAction->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->code_name]]);
		$this->ControllerAction->field('academic_period_id', ['type' => 'hidden', 'attr' => ['value' => $entity->academic_period_id]]);
		$this->ControllerAction->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $this->EducationGrades->get($entity->education_grade_id)->programme_grade_name]]);
		$this->ControllerAction->field('effective_date');
		$this->ControllerAction->field('student_dropout_reason_id', ['type' => 'select', 'attr' => ['value' => $entity->student_dropout_reason_id]]);
		$this->ControllerAction->field('comment');

		$this->ControllerAction->setFieldOrder([
			'status', 'student_id','institution_id', 'academic_period_id', 'education_grade_id',
			'effective_date',
			'student_dropout_reason_id', 'comment',
		]);
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$id = $this->Session->read($this->registryAlias().'.id');
		$Students = TableRegistry::get('Institution.Students');
		$student = $Students->get($id);
		$entity->student_id = $student->student_id;
		$entity->academic_period_id = $student->academic_period_id;
		$entity->education_grade_id = $student->education_grade_id;
		$entity->institution_id = $student->institution_id;

		$this->request->data[$this->alias()]['student_id'] = $entity->student_id;
		$this->request->data[$this->alias()]['academic_period_id'] = $entity->academic_period_id;
		$this->request->data[$this->alias()]['education_grade_id'] = $entity->education_grade_id;
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['transfer_status'] = $entity->status;
	}

	public function onUpdateFieldApplicationStatus(Event $event, array $attr, $action, $request) {
		switch ($action) {
			case 'add':
				$attr['type'] = 'readonly';
				$attr['attr']['value'] = __('New');
				break;
			case 'edit':
				$transferStatus = $request->data[$this->alias()]['transfer_status'];
				$attr['type'] = 'readonly';

				switch ($transferStatus) {
					case self::NEW_REQUEST:
						$attr['attr']['value'] = __('New');
						break;
					case self::APPROVED:
						$attr['attr']['value'] = __('Approve');
						break;
					case self::REJECTED:
						$attr['attr']['value'] = __('Reject');
						break;
				}
				break;
		}
		return $attr;		
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

   	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'add' || $action == 'edit') {
			$Students = TableRegistry::get('Institution.StudentUser');
			$toolbarButtons['back']['url']['action'] = $Students->alias();
			$toolbarButtons['back']['url'][0] = 'view';
			$toolbarButtons['back']['url'][1] = $this->Session->read('Student.Students.id');
		}
	}
}
