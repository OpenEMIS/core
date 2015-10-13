<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

class StudentTransferTable extends AppTable {
	// Status of Transfer Request
	const NEW_REQUEST = 0;
	const APPROVED = 1;
	const REJECTED = 2;

	// Type status for admission
	const TRANSFER = 2;
	const ADMISSION = 1;

	private $Grades = null;
	private $GradeStudents = null;
	private $StudentAdmission = null;
	private $Students = null;

	private $institutionId = null;
	private $currentPeriod = null;
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
	}

	public function validationDefault(Validator $validator) {
		return $validator
			->requirePresence('education_grade_id')
			->notEmpty('education_grade_id', 'This field is required.')
			->requirePresence('next_academic_period_id')
			->notEmpty('next_academic_period_id', 'This field is required.')
			->requirePresence('next_education_grade_id')
			->notEmpty('next_education_grade_id', 'This field is required.')
			->requirePresence('next_institution_id')
			->notEmpty('next_institution_id', 'This field is required.')
			->requirePresence('student_transfer_reason_id')
			->notEmpty('student_transfer_reason_id', 'This field is required.')
			;
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function beforeAction(Event $event) {
		$this->Grades = TableRegistry::get('Institution.InstitutionGrades');
		$this->GradeStudents = TableRegistry::get('Institution.StudentTransfer');
		$this->StudentAdmission = TableRegistry::get('Institution.StudentAdmission');
	    $this->Students = TableRegistry::get('Institution.Students');

		$this->institutionId = $this->Session->read('Institution.Institutions.id');
		$selectedPeriod = $this->AcademicPeriods->getCurrent();
    	$this->currentPeriod = $this->AcademicPeriods->get($selectedPeriod);
    	$this->statuses = $this->StudentStatuses->findCodeList();
    }

    public function indexBeforeAction(Event $event) {
    	$this->_redirect();
    }

    public function addAfterAction(Event $event, Entity $entity) {
    	$this->ControllerAction->field('student_status_id', ['visible' => false]);
    	$this->ControllerAction->field('student_id', ['visible' => false]);
		$this->ControllerAction->field('start_date', ['visible' => false]);
		$this->ControllerAction->field('end_date', ['visible' => false]);

		$this->ControllerAction->field('academic_period_id');
		$this->ControllerAction->field('current_academic_period_id');
		$this->ControllerAction->field('education_grade_id');
		$this->ControllerAction->field('next_academic_period_id');
		$this->ControllerAction->field('next_education_grade_id');
		$this->ControllerAction->field('next_institution_id');
		$this->ControllerAction->field('student_transfer_reason_id');
		$this->ControllerAction->field('students');

		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'current_academic_period_id', 'education_grade_id',
			'next_academic_period_id', 'next_education_grade_id', 'next_institution_id', 'student_transfer_reason_id'
		]);
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
    	if (array_key_exists($this->alias(), $data)) {
			$nextAcademicPeriodId = null;
			$nextEducationGradeId = null;
			$nextInstitutionId = null;
			$studentTransferReasonId = null;

			if (array_key_exists('next_academic_period_id', $data[$this->alias()])) {
				$nextAcademicPeriodId = $data[$this->alias()]['next_academic_period_id'];
			}
			if (array_key_exists('next_education_grade_id', $data[$this->alias()])) {
				$nextEducationGradeId = $data[$this->alias()]['next_education_grade_id'];
			}
			if (array_key_exists('next_institution_id', $data[$this->alias()])) {
				$nextInstitutionId = $data[$this->alias()]['next_institution_id'];
			}
			if (array_key_exists('student_transfer_reason_id', $data[$this->alias()])) {
				$studentTransferReasonId = $data[$this->alias()]['student_transfer_reason_id'];
			}

			if (!empty($nextAcademicPeriodId) && !empty($nextEducationGradeId) && !empty($nextInstitutionId) && !empty($studentTransferReasonId)) {
				if (array_key_exists('students', $data[$this->alias()])) {
					$TransferRequests = TableRegistry::get('Institution.TransferRequests');
					$institutionId = $data[$this->alias()]['institution_id'];

					$tranferCount = 0;
					foreach ($data[$this->alias()]['students'] as $key => $studentObj) {
						if ($studentObj['selected']) {
							unset($studentObj['selected']);
							$studentObj['academic_period_id'] = $nextAcademicPeriodId;
							$studentObj['education_grade_id'] = $nextEducationGradeId;
							$studentObj['institution_id'] = $nextInstitutionId;
							$studentObj['student_transfer_reason_id'] = $studentTransferReasonId;
							$studentObj['previous_institution_id'] = $institutionId;

							$nextPeriod = $this->AcademicPeriods->get($nextAcademicPeriodId);
							$studentObj['start_date'] = $nextPeriod->start_date->format('Y-m-d');
							$studentObj['end_date'] = $nextPeriod->end_date->format('Y-m-d');

							$entity = $TransferRequests->newEntity($studentObj);
							if ($TransferRequests->save($entity)) {
								$tranferCount++;
								$this->Alert->success($this->aliasField('success'));
							} else {
								$this->log($entity->errors(), 'debug');
								$this->Alert->error('general.add.failed');
							}
						}
					}

					if ($tranferCount == 0) {
						$this->Alert->error('general.notSelected');
					}

					$url = $this->ControllerAction->url('add');

					$event->stopPropagation();
					return $this->controller->redirect($url);
				}
			}			
		}
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
    	$attr['type'] = 'hidden';
    	$attr['attr']['value'] = $this->currentPeriod->id;

    	return $attr;
    }

    public function onUpdateFieldCurrentAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
    	$attr['type'] = 'readonly';
    	$attr['attr']['value'] = $this->currentPeriod->name;

    	return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
		$gradeOptions = [];

		if (!is_null($this->currentPeriod)) {
			$Grades = $this->Grades;
			$GradeStudents = $this->GradeStudents;
			$StudentAdmission = $this->StudentAdmission;
			$Students = $this->Students;

	    	$institutionId = $this->institutionId;
	    	$selectedPeriod = $this->currentPeriod->id;
			$statuses = $this->statuses;

			$gradeOptions = $Grades
				->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
				->contain(['EducationGrades'])
				->where([$Grades->aliasField('institution_site_id') => $institutionId])
				->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
				->toArray();

			$selectedGrade = $this->queryString('education_grade_id', $gradeOptions);
			$this->advancedSelectOptions($gradeOptions, $selectedGrade, [
				'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
				'callable' => function($id) use ($GradeStudents, $StudentAdmission, $Students, $institutionId, $selectedPeriod, $statuses) {
					return $GradeStudents
						->find()
						->leftJoin(
							[$StudentAdmission->alias() => $StudentAdmission->table()],
							[
								$StudentAdmission->aliasField('student_id = ') . $GradeStudents->aliasField('student_id'),
								$StudentAdmission->aliasField('status') => self::NEW_REQUEST
							]
						)
						->leftJoin(
							[$Students->alias() => $Students->table()],
							[
								$Students->aliasField('student_id = ') . $GradeStudents->aliasField('student_id'),
								$Students->aliasField('student_status_id') => $statuses['CURRENT']
							]
						)
						->where([
							$this->aliasField('institution_id') => $institutionId,
							$this->aliasField('academic_period_id') => $selectedPeriod,
							$this->aliasField('education_grade_id') => $id,
							$this->aliasField('student_status_id IN') => [$statuses['PROMOTED'], $statuses['GRADUATED']],
							$StudentAdmission->aliasField('student_id IS') => NULL,
							$Students->aliasField('student_id IS') => NULL
						])
						->count();
				}
			]);
			$this->request->query['education_grade_id'] = $selectedGrade;
		}

    	$attr['attr']['label'] = __('Current Education Grade');
    	$attr['options'] = $gradeOptions;
    	$attr['onChangeReload'] = 'changeGrade';

    	return $attr;
    }

    public function onUpdateFieldNextAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
    	$nextPeriodOptions = [];

    	if (!is_null($this->currentPeriod)) {
			$Grades = $this->Grades;
			$institutionId = $this->institutionId;
			$selectedPeriod = $this->currentPeriod->id;
			$periodLevelId = $this->currentPeriod->academic_period_level_id;
			$startDate = $this->currentPeriod->start_date->format('Y-m-d');

			$where = [
				$this->AcademicPeriods->aliasField('id <>') => $selectedPeriod,
				$this->AcademicPeriods->aliasField('academic_period_level_id') => $periodLevelId,
				$this->AcademicPeriods->aliasField('start_date >=') => $startDate
			];

			$nextPeriodOptions = $this->AcademicPeriods
				->find('list')
				->find('visible')
				->find('order')
				->where($where)
				->toArray();

			// Get the next academic period id
			if (is_null($request->query('next_academic_period_id'))) {
				$nextPeriod = $this->AcademicPeriods
					->find()
					->find('visible')
					->where($where)
					->order([$this->AcademicPeriods->aliasField('start_date asc')])
					->first();

				if (!empty($nextPeriod)) {
					$request->query['next_academic_period_id'] = $nextPeriod->id;
				}
			}
			// End

			$nextPeriodId = $this->queryString('next_academic_period_id', $nextPeriodOptions);
			$this->advancedSelectOptions($nextPeriodOptions, $nextPeriodId, [
				'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
				'callable' => function($id) use ($Grades, $institutionId) {
					return $Grades
						->find()
						->where([$Grades->aliasField('institution_site_id') => $institutionId])
						->find('academicPeriod', ['academic_period_id' => $id])
						->count();
				}
			]);
			$this->request->query['next_academic_period_id'] = $nextPeriodId;
		}

		$attr['options'] = $nextPeriodOptions;
    	$attr['onChangeReload'] = 'changeNextPeriod';

    	return $attr;
    }

    public function onUpdateFieldNextEducationGradeId(Event $event, array $attr, $action, Request $request) {
		$selectedGrade = $request->query('education_grade_id');
		$nextPeriodId = $request->query('next_academic_period_id');
    	$nextGradeOptions = [];

    	if (!is_null($selectedGrade)) {
	   		$currentGrade = $this->EducationGrades->get($selectedGrade);

			$nextGradeOptions = $this->EducationGrades
				->find('list', ['keyField' => 'id', 'valueField' => 'programme_grade_name'])
				->find('visible')
				->where([
					$this->EducationGrades->aliasField('education_programme_id') => $currentGrade->education_programme_id,
					$this->EducationGrades->aliasField('order >') => $currentGrade->order
				])
				->toArray();

			$NextProgrammes = TableRegistry::get('Education.EducationProgrammesNextProgrammes');
			$gradeOptions = $NextProgrammes->getNextGradeList($currentGrade->education_programme_id);

			$nextGradeOptions = $nextGradeOptions + $gradeOptions;

			$nextGradeId = $this->queryString('next_education_grade_id', $nextGradeOptions);

			if (is_null($nextPeriodId)) {
				$this->advancedSelectOptions($nextGradeOptions, $nextGradeId);
			} else {
				$Institutions = $this->Institutions;
				$Grades = $this->Grades;
				$institutionId = $this->institutionId;

				$nextPeriodData = $this->AcademicPeriods->get($nextPeriodId);
				if ($nextPeriodData->start_date instanceof Time) {
					$nextPeriodStartDate = $nextPeriodData->start_date->format('Y-m-d');
				} else {
					$nextPeriodStartDate = date('Y-m-d', strtotime($nextPeriodData->start_date));
				}

				$this->advancedSelectOptions($nextGradeOptions, $nextGradeId, [
					'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noInstitutions')),
					'callable' => function($id) use ($Institutions, $Grades, $institutionId, $nextPeriodStartDate) {
						return $Institutions
							->find()
							->join([
								'table' => $Grades->table(),
								'alias' => $Grades->alias(),
								'conditions' => [
									$Grades->aliasField('institution_site_id = ') . $this->Institutions->aliasField('id'),
									$Grades->aliasField('education_grade_id') => $id,
									$Grades->aliasField('start_date <=') => $nextPeriodStartDate,
									'OR' => [
										$Grades->aliasField('end_date IS NULL'),
										$Grades->aliasField('end_date >=') => $nextPeriodStartDate
									]
								]
							])
							->where([$this->Institutions->aliasField('id <>') => $institutionId])
							->count();
					}
				]);
			}
			$this->request->query['next_education_grade_id'] = $nextGradeId;
    	}

    	$attr['options'] = $nextGradeOptions;
    	$attr['onChangeReload'] = 'changeNextGrade';

    	return $attr;
    }

    public function onUpdateFieldNextInstitutionId(Event $event, array $attr, $action, Request $request) {
		$nextPeriodId = $request->query('next_academic_period_id');
		$nextGradeId = $request->query('next_education_grade_id');
    	$institutionOptions = [];

    	if (!is_null($nextPeriodId) && !is_null($nextGradeId)) {
    		$Grades = $this->Grades;
    		$institutionId = $this->institutionId;

    		$nextPeriodData = $this->AcademicPeriods->get($nextPeriodId);
			if ($nextPeriodData->start_date instanceof Time) {
				$nextPeriodStartDate = $nextPeriodData->start_date->format('Y-m-d');
			} else {
				$nextPeriodStartDate = date('Y-m-d', strtotime($nextPeriodData->start_date));
			}

			$institutionOptions = $this->Institutions
				->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
				->join([
					'table' => $Grades->table(),
					'alias' => $Grades->alias(),
					'conditions' => [
						$Grades->aliasField('institution_site_id = ') . $this->Institutions->aliasField('id'),
						$Grades->aliasField('education_grade_id') => $nextGradeId,
						$Grades->aliasField('start_date <=') => $nextPeriodStartDate,
						'OR' => [
							$Grades->aliasField('end_date IS NULL'),
							$Grades->aliasField('end_date >=') => $nextPeriodStartDate
						]
					]
				])
				->where([$this->Institutions->aliasField('id <>') => $institutionId])
				->toArray();
    	}

    	$attr['attr']['label'] = __('Institution');
    	$attr['options'] = $institutionOptions;

    	return $attr;
    }

    public function onUpdateFieldStudentTransferReasonId(Event $event, array $attr, $action, Request $request) {
    	$StudentTransferReasons = TableRegistry::get('FieldOption.StudentTransferReasons');
		$attr['options'] = $StudentTransferReasons->getList();

    	return $attr;
    }

    public function onUpdateFieldStudents(Event $event, array $attr, $action, Request $request) {
    	$institutionId = $this->institutionId;
    	$selectedPeriod = $this->currentPeriod->id;
    	$selectedGrade = $request->query('education_grade_id');

    	$students = [];
    	if (!is_null($selectedGrade)) {
	    	$GradeStudents = $this->GradeStudents;
	    	$StudentAdmission = $this->StudentAdmission;
			$Students = $this->Students;
	    	$statuses = $this->statuses;

	    	$studentQuery = $this
	    		->find()
	    		->matching('Users')
				->leftJoin(
					[$StudentAdmission->alias() => $StudentAdmission->table()],
					[
						$StudentAdmission->aliasField('student_id = ') . $this->aliasField('student_id'),
						$StudentAdmission->aliasField('status') => self::NEW_REQUEST
					]
				)
				->leftJoin(
					[$Students->alias() => $Students->table()],
					[
						$Students->aliasField('student_id = ') . $this->aliasField('student_id'),
						$Students->aliasField('student_status_id') => $statuses['CURRENT']
					]
				)
				->where([
					$this->aliasField('institution_id') => $institutionId,
					$this->aliasField('academic_period_id') => $selectedPeriod,
					$this->aliasField('education_grade_id') => $selectedGrade,
					$this->aliasField('student_status_id IN') => [$statuses['PROMOTED'], $statuses['GRADUATED']],
					$StudentAdmission->aliasField('student_id IS') => NULL,
					$Students->aliasField('student_id IS') => NULL
				])
				->group($this->aliasField('student_id'));

	  		$students = $studentQuery->toArray();
	  	}

	  	if (empty($students)) {
	  		$this->Alert->warning($this->aliasField('noData'));
	  	}

		$statusOptions = $this->StudentStatuses->find('list')->toArray();
    	$attr['type'] = 'element';
		$attr['element'] = 'Institution.StudentTransfer/students';
		$attr['attr']['status'] = self::NEW_REQUEST;
		$attr['attr']['type'] = self::TRANSFER;
		$attr['attr']['statusOptions'] = $statusOptions;
		$attr['data'] = $students;

		return $attr;
    }

    public function addOnChangeGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		unset($this->request->query['education_grade_id']);
		unset($this->request->query['next_academic_period_id']);
		unset($this->request->query['next_education_grade_id']);

		if ($this->request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $data)) {
				if (array_key_exists('education_grade_id', $data[$this->alias()])) {
					$this->request->query['education_grade_id'] = $data[$this->alias()]['education_grade_id'];
				}
			}
		}
    }

    public function addOnChangeNextPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		unset($this->request->query['next_academic_period_id']);
		unset($this->request->query['next_education_grade_id']);

		if ($this->request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $data)) {
				if (array_key_exists('next_academic_period_id', $data[$this->alias()])) {
					$this->request->query['next_academic_period_id'] = $data[$this->alias()]['next_academic_period_id'];
				}
			}
		}
    }

    public function addOnChangeNextGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		unset($this->request->query['next_education_grade_id']);

		if ($this->request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $data)) {
				if (array_key_exists('next_education_grade_id', $data[$this->alias()])) {
					$this->request->query['next_education_grade_id'] = $data[$this->alias()]['next_education_grade_id'];
				}
			}
		}
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		$toolbarButtons['back'] = $buttons['back'];
		$toolbarButtons['back']['type'] = 'button';
		$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
		$toolbarButtons['back']['attr'] = $attr;
		$toolbarButtons['back']['attr']['title'] = __('Back');
		$toolbarButtons['back']['url']['action'] = 'Students';
	}

	private function _redirect() {
		$url = $this->ControllerAction->url('index');
		$url['action'] = 'Students';

		return $this->controller->redirect($url);		
	}
}
