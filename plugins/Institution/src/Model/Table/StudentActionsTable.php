<?php
namespace Institution\Model\Table;

use ArrayObject;
// use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Utility\Inflector;
use Cake\Controller\Component;

class StudentActionsTable extends AppTable {
	private $undoActions = [];
	private $Grades = null;
	private $Students = null;
	// private $InstitutionGrades = null;
	// private $institutionId = null;
	// private $currentPeriod = null;
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
		$Navigation->addCrumb('Undo');
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
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

			$attr['options'] = $statusOptions;
			$attr['onChangeReload'] = 'changeStatus';
		}

		return $attr;
	}

	public function onUpdateFieldStudents(Event $event, array $attr, $action, Request $request) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$selectedPeriod = $request->query('period');
		$selectedGrade = $request->query('grade');
		$selectedStatus = $request->query('status');

		$settings = new ArrayObject([
			'institution_id' => $institutionId,
			'academic_period_id' => $selectedPeriod,
			'education_grade_id' => $selectedGrade,
			'student_status_id' => $selectedStatus
		]);
		$studentOptions = new ArrayObject([]);
		if (!is_null($selectedPeriod) && !is_null($selectedGrade) && !is_null($selectedStatus)) {
			$params = [$settings, $studentOptions];

			$statusCode = array_search($selectedStatus, $this->statuses);
			$undoAction = Inflector::camelize(strtolower($statusCode));
			$event = $this->dispatchEvent('Undo.get' . $undoAction . 'Students', $params, $this);
			if ($event->isStopped()) { return $event->result; }
		}
		$students = $studentOptions->getArrayCopy();

		if (empty($students)) {
	  		$this->Alert->warning($this->aliasField('noData'));
	  	}

    	$attr['type'] = 'element';
		$attr['element'] = 'Institution.StudentActions/students';
		$attr['data'] = $students;

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
		$this->ControllerAction->field('institution_id', ['visible' => false]);
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

 //    public function beforeAction(Event $event) {
	// 	$this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
	// 	$this->institutionId = $this->Session->read('Institution.Institutions.id');
	// 	$selectedPeriod = $this->AcademicPeriods->getCurrent();
 //    	$this->currentPeriod = $this->AcademicPeriods->get($selectedPeriod);
 //    	$this->statuses = $this->StudentStatuses->findCodeList();
 //    }

 //    public function addAfterAction() {
 //    	$this->fields = [];
 //    	$this->ControllerAction->field('current_academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $this->currentPeriod->name], 'value' => $this->currentPeriod->id]);
 //    	$this->ControllerAction->field('next_academic_period_id');
 //    	$this->ControllerAction->field('grade_to_promote');
 //    	$this->ControllerAction->field('student_status_id');
 //    	$this->ControllerAction->field('education_grade_id');
	// 	$this->ControllerAction->field('students');
		
	// 	$this->ControllerAction->setFieldOrder(['current_academic_period_id', 'grade_to_promote', 'next_academic_period_id', 'student_status_id', 'education_grade_id', 'students']);
	// }

	// public function onUpdateFieldNextAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
	// 	$currentPeriod = $this->currentPeriod;
	// 	$selectedPeriod = $currentPeriod->id;
	// 	$startDate = $currentPeriod->start_date->format('Y-m-d');
	// 	$where = [
	// 		$this->AcademicPeriods->aliasField('id <>') => $selectedPeriod,
	// 		$this->AcademicPeriods->aliasField('academic_period_level_id') => $currentPeriod->academic_period_level_id,
	// 		$this->AcademicPeriods->aliasField('start_date >=') => $startDate
	// 	];
	// 	$periodOptions = $this->AcademicPeriods
	// 			->find('list')
	// 			->find('visible')
	// 			->find('order')
	// 			->where($where)
	// 			->toArray();
	// 	$attr['type'] = 'select';
	// 	$attr['options'] = $periodOptions;
	// 	$attr['onChangeReload'] = true;
	// 	if (empty($request->data[$this->alias()]['next_academic_period_id'])) {
	// 		$request->data[$this->alias()]['next_academic_period_id'] = key($periodOptions);
	// 	}
	// 	return $attr;
	// }

	// public function onUpdateFieldGradeToPromote(Event $event, array $attr, $action, Request $request) {
	// 	$InstitutionTable = $this->Institutions;
	// 	$InstitutionGradesTable = $this->InstitutionGrades;
	// 	$selectedPeriod = $this->currentPeriod->id;
	// 	$institutionId = $this->institutionId;
	// 	$statuses = $this->statuses;
	// 	$gradeOptions = $InstitutionGradesTable
	// 		->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
	// 		->contain(['EducationGrades'])
	// 		->where([$InstitutionGradesTable->aliasField('institution_id') => $institutionId])
	// 		->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
	// 		->toArray();
	// 	$attr['type'] = 'select';
	// 	$selectedGrade = $request->query('grade_to_promote');
	// 	$GradeStudents = $this;
	// 	$this->advancedSelectOptions($gradeOptions, $selectedGrade, [
	// 		'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
	// 		'callable' => function($id) use ($GradeStudents, $institutionId, $selectedPeriod, $statuses) {
	// 			return $GradeStudents
	// 				->find()
	// 				->where([
	// 					$GradeStudents->aliasField('institution_id') => $institutionId,
	// 					$GradeStudents->aliasField('academic_period_id') => $selectedPeriod,
	// 					$GradeStudents->aliasField('education_grade_id') => $id,
	// 					$GradeStudents->aliasField('student_status_id') => $statuses['CURRENT']
	// 				])
	// 				->count();
	// 		}
	// 	]);
	// 	$attr['onChangeReload'] = true;
	// 	$attr['options'] = $gradeOptions;
	// 	if (empty($request->data[$this->alias()]['grade_to_promote'])) {
	// 		$request->data[$this->alias()]['grade_to_promote'] = $selectedGrade;
	// 	}
	// 	return $attr;
	// }

	// public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, Request $request) {
	// 	if ($action == 'add') {
	// 		$studentStatusesList = $this->StudentStatuses->find('list')->toArray();
	// 		$statusesCode = $this->statuses;
	// 		$educationGradeId = $request->data[$this->alias()]['grade_to_promote'];
	// 		$nextGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId, false);

	// 		// If there is no more next grade in the same education programme then the student may be graduated
	// 		if (count($nextGrades) == 0) {
	// 			$options[$statusesCode['GRADUATED']] = $studentStatusesList[$statusesCode['GRADUATED']];
	// 		} else {
	// 			$options[$statusesCode['PROMOTED']] = $studentStatusesList[$statusesCode['PROMOTED']];
	// 		}
			
	// 		$options[$statusesCode['REPEATED']] = $studentStatusesList[$statusesCode['REPEATED']];
	// 		$attr['options'] = $options;
	// 		$attr['onChangeReload'] = true;
	// 		if (empty($request->data[$this->alias()]['student_status_id'])) {
	// 			reset($options);
	// 			$request->data[$this->alias()]['student_status_id'] = key($options);
	// 		}
	// 		return $attr;
	// 	}
	// }

	// public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
	// 	$studentStatusId = $request->data[$this->alias()]['student_status_id'];
	// 	$statuses = $this->statuses;
	// 	if ($studentStatusId != $statuses['REPEATED']) {
	// 		$educationGradeId = $request->data[$this->alias()]['grade_to_promote'];
	// 		$institutionId = $this->institutionId;
			
	// 		// list of grades available to promote to
	// 		$listOfGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId);

	// 		// list of grades available in the institution
	// 		$listOfInstitutionGrades = $this->InstitutionGrades
	// 			->find('list', [
	// 				'keyField' => 'education_grade_id', 
	// 				'valueField' => 'education_grade.programme_grade_name'])
	// 			->contain(['EducationGrades'])
	// 			->where([$this->InstitutionGrades->aliasField('institution_id') => $institutionId])
	// 			->toArray();

	// 		// Only display the options that are available in the institution and also linked to the current programme
	// 		$options = array_intersect_key($listOfInstitutionGrades, $listOfGrades);

	// 		if (count($options) == 0) {
	// 			$options = [0 => __('No Available Grades in this Institution')];
	// 		}
	// 		$attr['type'] = 'select';
	// 		$attr['options'] = $options;
	// 	} else {
	// 		$attr['type'] = 'hidden';
	// 	}
		
	// 	return $attr;
	// }

	// public function onUpdateFieldStudents(Event $event, array $attr, $action, Request $request) {
 //    	$institutionId = $this->institutionId;
 //    	$selectedPeriod = $this->currentPeriod->id;
 //    	$selectedGrade = $request->data[$this->alias()]['grade_to_promote'];
 //    	$students = [];
 //    	if (!is_null($selectedGrade)) {
 //    		$studentStatuses = $this->statuses;
 //    		$students = $this->find()
	//     		->matching('Users')
	//     		->matching('EducationGrades')
	//     		->where([
	//     			$this->aliasField('institution_id') => $institutionId,
	//     			$this->aliasField('academic_period_id') => $selectedPeriod,
	//     			$this->aliasField('student_status_id') => $studentStatuses['CURRENT'],
	//     			$this->aliasField('education_grade_id') => $selectedGrade
	//     		])
	//     		->toArray();
 //    	}
	//   	if (empty($students)) {
	//   		$this->Alert->warning($this->aliasField('noData'));
	//   	}
 //    	$attr['type'] = 'element';
	// 	$attr['element'] = 'Institution.StudentPromotion/students';
	// 	$attr['data'] = $students;

	// 	return $attr;
 //    }

	// public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
	// 	if ($action == 'add') {
	// 		$toolbarButtons['back'] = $buttons['back'];
	// 		$toolbarButtons['back']['type'] = 'button';
	// 		$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
	// 		$toolbarButtons['back']['attr'] = $attr;
	// 		$toolbarButtons['back']['attr']['title'] = __('Back');
	// 		$toolbarButtons['back']['url']['action'] = 'Students';
	// 	}
	// }

	// public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
 //    	if (array_key_exists($this->alias(), $data)) {
	// 		$nextAcademicPeriodId = null;
	// 		$nextEducationGradeId = null;
	// 		$currentAcademicPeriod = null;
	// 		$currentGrade = null;
	// 		$statusToUpdate = null;
	// 		$studentStatuses = $this->statuses;
	// 		$institutionId = $this->institutionId;
	// 		if (array_key_exists('current_academic_period_id', $data[$this->alias()])) {
	// 			$currentAcademicPeriod = $data[$this->alias()]['current_academic_period_id'];
	// 		}
	// 		if (array_key_exists('grade_to_promote', $data[$this->alias()])) {
	// 			$currentGrade = $data[$this->alias()]['grade_to_promote'];
	// 		}

	// 		if (array_key_exists('next_academic_period_id', $data[$this->alias()])) {
	// 			$nextAcademicPeriodId = $data[$this->alias()]['next_academic_period_id'];
	// 		}
	// 		if (array_key_exists('education_grade_id', $data[$this->alias()])) {
	// 			$nextEducationGradeId = $data[$this->alias()]['education_grade_id'];
	// 		}
	// 		if (array_key_exists('student_status_id', $data[$this->alias()])) {
	// 			$statusToUpdate = $data[$this->alias()]['student_status_id'];
	// 		}
	// 		if ($statusToUpdate == $studentStatuses['REPEATED']) {
	// 			$nextEducationGradeId = $currentGrade;
	// 		}
	// 		if (!empty($nextAcademicPeriodId) && !empty($currentAcademicPeriod) && !empty($currentGrade)) {
	// 			if (array_key_exists('students', $data[$this->alias()])) {
	// 				$nextPeriod = $this->AcademicPeriods->get($nextAcademicPeriodId);
	// 				$tranferCount = 0;
	// 				foreach ($data[$this->alias()]['students'] as $key => $studentObj) {
	// 					if ($studentObj['selected']) {
	// 						unset($studentObj['selected']);
	// 						$studentObj['academic_period_id'] = $nextAcademicPeriodId;
	// 						$studentObj['education_grade_id'] = $nextEducationGradeId;
	// 						$studentObj['institution_id'] = $institutionId;
	// 						$studentObj['student_status_id'] = $studentStatuses['CURRENT'];
	// 						$studentObj['start_date'] = $nextPeriod->start_date->format('Y-m-d');
	// 						$studentObj['end_date'] = $nextPeriod->end_date->format('Y-m-d');
	// 						$entity = $this->newEntity($studentObj);
	// 						$update = $this->updateAll(
	// 								['student_status_id' => $statusToUpdate],
	// 								[
	// 									'student_id' => $studentObj['student_id'], 
	// 									'education_grade_id' => $currentGrade,
	// 									'academic_period_id' => $currentAcademicPeriod,
	// 									'institution_id' => $institutionId,
	// 									'student_status_id' => $studentStatuses['CURRENT']
	// 								]
	// 							);
	// 						// If the update count is more than 0	
	// 						if ($update) {
	// 							if ($nextEducationGradeId != 0) {
	// 								if ($this->save($entity)) {
	// 									$this->Alert->success($this->aliasField('success'), ['reset' => true]);
	// 								} else {
	// 									$this->log($entity->errors(), 'debug');
	// 								}
	// 							} else {
	// 								$this->Alert->success($this->aliasField('success'), ['reset' => true]);
	// 							}
	// 						}
	// 					}
	// 				}
	// 				$url = $this->ControllerAction->url('add');
	// 				$event->stopPropagation();
	// 				return $this->controller->redirect($url);
	// 			}
	// 		}			
	// 	}
 //    }
}
