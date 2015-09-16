<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Utility\Inflector;

class StudentTransferTable extends AppTable {
	// Status of Transfer Request
	const NEW_REQUEST = 0;
	const APPROVED = 1;
	const REJECTED = 2;

	// Type status for admission
	const TRANSFER = 2;
	const ADMISSION = 1;

	private $selectedPeriod = null;

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

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
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
    	$event->stopPropagation();

    	if (array_key_exists($this->alias(), $data)) {
			if (array_key_exists('students', $data[$this->alias()])) {
				$TransferRequests = TableRegistry::get('Institution.TransferRequests');
				$institutionId = $data[$this->alias()]['institution_id'];
				$nextAcademicPeriodId = null;
				$nextEducationGradeId = null;
				$nextInstitutionId = null;
				$studentTransferReasonId = null;

				if (array_key_exists($this->alias(), $data)) {
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
				}

				foreach ($data[$this->alias()]['students'] as $key => $studentObj) {
					if ($studentObj['selected']) {
						unset($studentObj['selected']);
						$studentObj['academic_period_id'] = $nextAcademicPeriodId;
						$studentObj['education_grade_id'] = $nextEducationGradeId;
						$studentObj['institution_id'] = $nextInstitutionId;
						$studentObj['student_transfer_reason_id'] = $studentTransferReasonId;
						$studentObj['previous_institution_id'] = $institutionId;

						$entity = $TransferRequests->newEntity($studentObj);
						if ($TransferRequests->save($entity)) {
							$this->Alert->success($this->aliasField('success'));
						} else {
							$this->log($entity->errors(), 'debug');
							$this->Alert->error('general.add.failed');
						}
					}
				}

				$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Students'];
				$url = array_merge($url, $this->request->query, $this->request->pass);
				$url[0] = 'index';

				return $this->controller->redirect($url);
			}
		}

    	return $this->_redirect();
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
    	$this->selectedPeriod = $this->AcademicPeriods->getCurrent();
    	$attr['type'] = 'hidden';
    	$attr['attr']['value'] = $this->selectedPeriod;

    	return $attr;
    }

    public function onUpdateFieldCurrentAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
    	$attr['type'] = 'readonly';
    	$attr['attr']['value'] = $this->AcademicPeriods->get($this->selectedPeriod)->name;

    	return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
    	$institutionId = $this->Session->read('Institution.Institutions.id');
		$Grades = TableRegistry::get('Institution.InstitutionSiteGrades');
		$GradeStudents = TableRegistry::get('Institution.StudentTransfer');
		$StudentStatuses = $this->StudentStatuses;
		$selectedPeriod = $this->selectedPeriod;

		$gradeOptions = $Grades
			->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
			->contain(['EducationGrades'])
			->where([$Grades->aliasField('institution_site_id') => $institutionId])
			->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
			->toArray();
		$selectedGrade = $this->queryString('education_grade_id', $gradeOptions);
		$this->advancedSelectOptions($gradeOptions, $selectedGrade, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
			'callable' => function($id) use ($GradeStudents, $StudentStatuses, $institutionId, $selectedPeriod) {
				return $GradeStudents
					->find()
					->innerJoin(
						[$StudentStatuses->alias() => $StudentStatuses->table()],
						[
							$StudentStatuses->aliasField('id = ') . $GradeStudents->aliasField('student_status_id'),
							$StudentStatuses->aliasField('code IN') => ['GRADUATED', 'PROMOTED'],
							$StudentStatuses->aliasField('code <>') => ['CURRENT']
						]
					)
					->where([
						$GradeStudents->aliasField('institution_id') => $institutionId,
						$GradeStudents->aliasField('academic_period_id') => $selectedPeriod,
						$GradeStudents->aliasField('education_grade_id') => $id
					])
					->count();
			}
		]);
		$this->request->query['education_grade_id'] = $selectedGrade;

    	$attr['attr']['label'] = __('Current Education Grade');
    	$attr['options'] = $gradeOptions;
    	$attr['onChangeReload'] = 'changeGrade';

    	return $attr;
    }

    public function onUpdateFieldNextAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
    	$nextPeriodOptions = [];

    	if (!is_null($this->selectedPeriod)) {
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$Grades = TableRegistry::get('Institution.InstitutionSiteGrades');
			$selectedPeriod = $this->selectedPeriod;

			$currentPeriod = $this->AcademicPeriods->get($selectedPeriod);
			$startDate = $currentPeriod->start_date->format('Y-m-d');

			$where = [
				$this->AcademicPeriods->aliasField('id <>') => $selectedPeriod,
				$this->AcademicPeriods->aliasField('academic_period_level_id') => $currentPeriod->academic_period_level_id,
				$this->AcademicPeriods->aliasField('start_date >=') => $startDate
			];

			$nextPeriodOptions = $this->AcademicPeriods
				->find('list')
				->find('visible')
				->find('order')
				->where($where)
				->toArray();

			// Get the next academic period id
			if (is_null($this->request->query('next_academic_period_id'))) {
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
		}

		$attr['options'] = $nextPeriodOptions;
    	$attr['onChangeReload'] = 'changeNextPeriod';

    	return $attr;
    }

    public function onUpdateFieldNextEducationGradeId(Event $event, array $attr, $action, Request $request) {
    	$nextPeriodId = $request->query('next_academic_period_id');
    	$nextGradeOptions = [];

    	if (!is_null($nextPeriodId)) {
			$institutionId = $this->Session->read('Institution.Institutions.id');
			$Grades = TableRegistry::get('Institution.InstitutionSiteGrades');
			$GradeStudents = TableRegistry::get('Institution.StudentTransfer');

    		$nextGradeOptions = $Grades
				->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
				->contain(['EducationGrades'])
				->where([$Grades->aliasField('institution_site_id') => $institutionId])
				->find('academicPeriod', ['academic_period_id' => $nextPeriodId])
				->toArray();
			$nextGradeId = $this->queryString('next_education_grade_id', $nextGradeOptions);
			$this->advancedSelectOptions($nextGradeOptions, $nextGradeId, [
				'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
				'callable' => function($id) use ($GradeStudents, $institutionId, $nextPeriodId) {
					return $GradeStudents
						->find()
						->where([
							$GradeStudents->aliasField('institution_id') => $institutionId,
							$GradeStudents->aliasField('academic_period_id') => $nextPeriodId,
							$GradeStudents->aliasField('education_grade_id') => $id
						])
						->count();
				}
			]);
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
    		$institutionId = $this->Session->read('Institution.Institutions.id');
			$Grades = TableRegistry::get('Institution.InstitutionSiteGrades');

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
						$Grades->aliasField('institution_site_id =') . $this->Institutions->aliasField('id'),
						$Grades->aliasField('education_grade_id') => $nextGradeId,
						$Grades->aliasField('start_date').' <=' => $nextPeriodStartDate,
						'OR' => [
							$Grades->aliasField('end_date').' IS NULL',
							$Grades->aliasField('end_date').' >=' => $nextPeriodStartDate
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
    	$institutionId = $this->Session->read('Institution.Institutions.id');
    	$selectedPeriod = $this->selectedPeriod;
    	$selectedGrade = $request->query('education_grade_id');
    	$GradeStudents = TableRegistry::get('Institution.StudentTransfer');

    	$students = $this
    		->find()
    		->contain(['Users'])
			->innerJoin(
				[$this->StudentStatuses->alias() => $this->StudentStatuses->table()],
				[
					$this->StudentStatuses->aliasField('id = ') . $this->aliasField('student_status_id'),
					$this->StudentStatuses->aliasField('code IN') => ['GRADUATED', 'PROMOTED'],
					$this->StudentStatuses->aliasField('code <>') => ['CURRENT']
				]
			)
			->where([
				$this->aliasField('institution_id') => $institutionId,
				$this->aliasField('academic_period_id') => $selectedPeriod,
				$this->aliasField('education_grade_id') => $selectedGrade
			])
			->toArray();

    	$attr['type'] = 'element';
		$attr['element'] = 'Institution.StudentTransfer/students';
		$attr['attr']['status'] = self::NEW_REQUEST;
		$attr['attr']['type'] = self::TRANSFER;
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
		$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Students'];
		$url = array_merge($url, $this->request->query, $this->request->pass);
		$url[0] = 'index';

		return $this->controller->redirect($url);		
	}
}
