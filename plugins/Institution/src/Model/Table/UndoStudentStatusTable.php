<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Utility\Inflector;
use Cake\Controller\Component;

class UndoStudentStatusTable extends AppTable {
	private $undoActions = [];
	private $Grades = null;
	private $Students = null;
	private $statuses = [];	// Student Status

	public function initialize(array $config) {
		$this->table('institution_students');
		parent::initialize($config);
		$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		
		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
		$this->addBehavior('Institution.UndoCurrent');
		$this->addBehavior('Institution.UndoGraduated');
		$this->addBehavior('Institution.UndoPromoted');
		$this->addBehavior('Institution.UndoRepeated');

		$this->Grades = TableRegistry::get('Institution.InstitutionGrades');
		$this->Students = TableRegistry::get('Institution.Students');
		$this->statuses = $this->StudentStatuses->findCodeList();
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	$events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
    	return $events;
    }

	public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona=false) {
		$url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students'];
		$Navigation->substituteCrumb('Undo', 'Students', $url);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$options['validate'] = false;

		if (array_key_exists($this->alias(), $data)) {
			if (array_key_exists('student_status_id', $data[$this->alias()])) {
				$selectedStatus = $data[$this->alias()]['student_status_id'];
				$statusCode = array_search($selectedStatus, $this->statuses);
				$undoAction = Inflector::camelize(strtolower($statusCode));
				$data[$this->alias()]['undo_action'] = $undoAction;
			}
		}
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$undoAction = $entity->undo_action;
		$newEvent = $this->dispatchEvent('Undo.beforeSave' . $undoAction . 'Students', [$entity, $data], $this);
		if ($newEvent->isStopped()) { return $newEvent->result; }

		$event->stopPropagation();
		$url = $this->ControllerAction->url('add');
		return $this->controller->redirect($url);
	}

	public function addAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
		} else if ($action == 'add' || $action == 'edit') {
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$Grades = $this->Grades;

			$periodOptions = $this->AcademicPeriods->getList();
			$selectedPeriod = $this->queryString('period', $periodOptions);
			$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
				'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
				'callable' => function($id) use ($Grades, $institutionId) {
					return $Grades
						->find()
						->where([$Grades->aliasField('institution_id') => $institutionId])
						->find('academicPeriod', ['academic_period_id' => $id])
						->count();
				}
			]);

			$attr['options'] = $periodOptions;
			$attr['onChangeReload'] = 'changePeriod';
		}

		return $attr;
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
		} else if ($action == 'add' || $action == 'edit') {
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$selectedPeriod = $request->query('period');

			$gradeOptions = [];
			if (!is_null($selectedPeriod)) {
				$gradeOptions = $this->Grades
					->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
					->contain(['EducationGrades'])
					->where([$this->Grades->aliasField('institution_id') => $institutionId])
					->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
					->toArray();

				$Students = $this->Students;
				$selectedGrade = $this->queryString('grade', $gradeOptions);
				$this->advancedSelectOptions($gradeOptions, $selectedGrade, [
					'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
					'callable' => function($id) use ($Students, $institutionId, $selectedPeriod) {
						return $Students
							->find()
							->where([
								'institution_id' => $institutionId,
								'academic_period_id' => $selectedPeriod,
								'education_grade_id' => $id
							])
							->count();
					}
				]);
			}

			$attr['options'] = $gradeOptions;
			$attr['onChangeReload'] = 'changeGrade';
		}

		return $attr;
	}

	public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
		} else if ($action == 'add' || $action == 'edit') {
			$statusOptions = [];

			$codes = [];
			$codes[$this->statuses['CURRENT']] = $this->statuses['CURRENT'];
			$codes[$this->statuses['GRADUATED']] = $this->statuses['GRADUATED'];
			$codes[$this->statuses['PROMOTED']] = $this->statuses['PROMOTED'];
			$codes[$this->statuses['REPEATED']] = $this->statuses['REPEATED'];

			$statusOptions = $this->StudentStatuses
				->find('list')
				->where([
					$this->StudentStatuses->aliasField('id IN') => $codes
				])
				->toArray();
			$selectedStatus = $this->queryString('status', $statusOptions);
			$this->advancedSelectOptions($statusOptions, $selectedStatus);

			$attr['options'] = $statusOptions;
			$attr['onChangeReload'] = 'changeStatus';
		}

		return $attr;
	}

	public function onUpdateFieldStudents(Event $event, array $attr, $action, Request $request) {
		$model = $this->Students;

		$institutionId = $this->Session->read('Institution.Institutions.id');
		$selectedPeriod = $request->query('period');
		$selectedGrade = $request->query('grade');
		$selectedStatus = $request->query('status');

		$data = [];
		if (!is_null($selectedPeriod) && !is_null($selectedGrade) && !is_null($selectedStatus)) {
			$data = $model
				->find()
	    		->matching('Users')
	    		->matching('EducationGrades')
	    		->where([
	    			$model->aliasField('institution_id') => $institutionId,
	    			$model->aliasField('academic_period_id') =>  $selectedPeriod,
	    			$model->aliasField('education_grade_id') => $selectedGrade,
	    			$model->aliasField('student_status_id') => $selectedStatus
	    		])
	    		->all();
	    	// pr($institutionId);
	    	// pr($selectedPeriod);
	    	// pr($selectedGrade);
	    	// pr($selectedStatus);
	    	// pr($data->count());

			// onGetCurrentStudents event
			$statusCode = array_search($selectedStatus, $this->statuses);
			$undoAction = Inflector::camelize(strtolower($statusCode));
			$event = $this->dispatchEvent('Undo.get' . $undoAction . 'Students', [$data], $this);
			if ($event->isStopped()) { return $event->result; }
			if (!empty($event->result)) {
				$data = $event->result;
			}
			// End event
		}

		if (empty($data)) {
	  		$this->Alert->warning($this->aliasField('noData'));
	  	}

    	$attr['type'] = 'element';
		$attr['element'] = 'Institution.UndoStudentStatus/students';
		$attr['data'] = $data;

		return $attr;
	}

	public function addEditOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['period']);
		unset($request->query['grade']);
		unset($request->query['status']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
					$request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
				}
				if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
					$request->query['grade'] = $request->data[$this->alias()]['education_grade_id'];
				}
				if (array_key_exists('student_status_id', $request->data[$this->alias()])) {
					$request->query['status'] = $request->data[$this->alias()]['student_status_id'];
				}
			}
		}
	}

	public function addEditOnChangeGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['grade']);
		unset($request->query['status']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
					$request->query['grade'] = $request->data[$this->alias()]['education_grade_id'];
				}
				if (array_key_exists('student_status_id', $request->data[$this->alias()])) {
					$request->query['status'] = $request->data[$this->alias()]['student_status_id'];
				}
			}
		}
	}

	public function addEditOnChangeStatus(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['status']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('student_status_id', $request->data[$this->alias()])) {
					$request->query['status'] = $request->data[$this->alias()]['student_status_id'];
				}
			}
		}
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'add') {
			$toolbarButtons['back'] = $buttons['back'];
			$toolbarButtons['back']['type'] = 'button';
			$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
			$toolbarButtons['back']['attr'] = $attr;
			$toolbarButtons['back']['attr']['title'] = __('Back');
			$toolbarButtons['back']['url']['action'] = 'Students';
		}
	}

	public function addUndoActions($type) {
		$this->undoActions[$type] = $type;
	}

	private function setupFields(Entity $entity) {
		$this->ControllerAction->field('student_id', ['visible' => false]);
		$this->ControllerAction->field('institution_id', ['type' => 'hidden']);
		$this->ControllerAction->field('start_date', ['visible' => false]);
		$this->ControllerAction->field('start_year', ['visible' => false]);
		$this->ControllerAction->field('end_date', ['visible' => false]);
		$this->ControllerAction->field('end_year', ['visible' => false]);

		$this->ControllerAction->field('academic_period_id', ['type' => 'select']);
		$this->ControllerAction->field('education_grade_id', ['type' => 'select']);
		$this->ControllerAction->field('student_status_id', ['type' => 'select']);
		$this->ControllerAction->field('students');

		$this->ControllerAction->setFieldOrder(['academic_period_id', 'education_grade_id', 'student_status_id', 'students']);
	}

	public function getStudentModel() {
		return $this->Students;
	}

	public function getStudentStatuses() {
		return $this->statuses;
	}
}
