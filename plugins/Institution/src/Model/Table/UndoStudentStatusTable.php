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
	private $dataCount = 0;

	public function initialize(array $config) {
		$this->table('institution_students');
		parent::initialize($config);

		$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);

		// Undo behavior
		$this->Grades = TableRegistry::get('Institution.InstitutionGrades');
		$this->Students = TableRegistry::get('Institution.Students');
		$this->statuses = $this->StudentStatuses->findCodeList();
		$settings = [
			'model' => 'Institution.Students',
			'statuses' => $this->statuses
		];

		$this->addBehavior('Institution.UndoCurrent', $settings);
		$this->addBehavior('Institution.UndoGraduated', $settings);
		$this->addBehavior('Institution.UndoPromoted', $settings);
		$this->addBehavior('Institution.UndoRepeated', $settings);
		// End
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
		$Navigation->addCrumb('Undo');
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		$selectedGrade = !is_null($this->request->query('grade')) ? $this->request->query('grade') : -1;
		$selectedStatus = !is_null($this->request->query('status')) ? $this->request->query('status') : -1;

		$this->request->query['grade'] = $selectedGrade;
		$this->request->query['status'] = $selectedStatus;
	}

	public function addBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// bypass validation
		$options['validate'] = false;
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$process = function ($model, $entity) use ($data) {
			$institutionId = $entity->institution_id;
			$selectedPeriod = $entity->academic_period_id;
			$selectedGrade = $entity->education_grade_id;
			$selectedStatus = $entity->student_status_id;

			if ($selectedPeriod != -1 && $selectedGrade != -1 && $selectedStatus != -1) {
				if (array_key_exists($this->alias(), $data)) {
					if (array_key_exists('student_status_id', $data[$this->alias()])) {
						$selectedStatus = $data[$this->alias()]['student_status_id'];
						$statusCode = array_search($selectedStatus, $this->statuses);
						$undoAction = Inflector::camelize(strtolower($statusCode));

						$event = $this->dispatchEvent('Undo.beforeSave' . $undoAction . 'Students', [$entity, $data], $this);
						if ($event->isStopped()) { return $event->result; }

						// set student_ids and output alert message in addAfterSave()
						$entity->student_ids = $event->result;
					}
				}

				return true;
			} else {
				return false;
			}
		};

		return $process;
	}

	public function addAfterSave(Event $event, Entity $entity, ArrayObject $data) {
		if (empty($entity->student_ids)) {
			$this->Alert->warning('general.notSelected', ['reset' => true]);
		} else {
			$this->Alert->success('UndoStudentStatus.success', ['reset' => true]);
		}

		$event->stopPropagation();
		$url = $this->ControllerAction->url('add');
		return $this->controller->redirect($url);
	}

	public function addAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		// unset buttons if no students found
		if (empty($this->dataCount)) {
			$buttons->exchangeArray([]);
		}
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
		} else if ($action == 'add' || $action == 'edit') {
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$Grades = $this->Grades;

			$periodOptions = $this->AcademicPeriods->getList();
			if (empty($request->query['period'])) {
				$request->query['period'] = $this->AcademicPeriods->getCurrent();
			}
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
				$selectedGrade = $request->query['grade'];
				$gradeOptions = ['-1' => '-- Select Grade --'] + $gradeOptions;

				$Students = $this->Students;
				$this->advancedSelectOptions($gradeOptions, $selectedGrade, [
					'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
					'callable' => function($id) use ($Students, $institutionId, $selectedPeriod) {
						if ($id == -1) {
							return 1;
						} else {
							return $Students
								->find()
								->where([
									'institution_id' => $institutionId,
									'academic_period_id' => $selectedPeriod,
									'education_grade_id' => $id
								])
								->count();
						}
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
			$selectedStatus = $request->query['status'];
			$statusOptions = ['-1' => '-- Select Status --'] + $statusOptions;
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
		if (!is_null($selectedPeriod) && $selectedGrade != -1 && $selectedStatus != -1) {
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

	    	// update students count here and show / hide form buttons in onGetFormButtons()
	    	$this->dataCount = $data->count();

			// onGetCurrentStudents event
			$statusCode = array_search($selectedStatus, $this->statuses);
			$undoAction = Inflector::camelize(strtolower($statusCode));
			$event = $this->dispatchEvent('Undo.get' . $undoAction . 'Students', [$data], $this);
			if ($event->isStopped()) { return $event->result; }
			if (!empty($event->result)) {
				$data = $event->result;
				$this->dataCount = sizeof($data);
			}
			// End event
		}

		if (empty($this->dataCount)) {
	  		$this->Alert->warning($this->aliasField('noData'));
	  	}

    	$attr['type'] = 'element';
		$attr['element'] = 'Institution.UndoStudentStatus/students';
		$attr['data'] = $data;

		return $attr;
	}

	public function addEditOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		$request->query['period'] = -1;
		$request->query['grade'] = -1;
		$request->query['status'] = -1;

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
		$request->query['grade'] = -1;
		$request->query['status'] = -1;

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
		$request->query['status'] = -1;

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
