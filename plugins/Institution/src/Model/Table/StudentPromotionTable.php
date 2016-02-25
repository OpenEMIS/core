<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Utility\Inflector;
use Cake\Controller\Component;
use Cake\Validation\Validator;

class StudentPromotionTable extends AppTable {
	private $InstitutionGrades = null;
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
		$this->addBehavior('Institution.UpdateStudentStatus');
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		$events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
		return $events;
	}

	public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona=false) {
		$url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students'];
		$Navigation->substituteCrumb('Promotion', 'Students', $url);
		$Navigation->addCrumb('Promotion');
	}

	public function beforeAction(Event $event) {
		$this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
		$this->institutionId = $this->Session->read('Institution.Institutions.id');
		$selectedPeriod = $this->AcademicPeriods->getCurrent();
		$this->currentPeriod = $this->AcademicPeriods->get($selectedPeriod);
		$this->statuses = $this->StudentStatuses->findCodeList();
	}

	public function addAfterAction() {
		$this->fields = [];
		$this->ControllerAction->field('from_academic_period_id', ['attr' => ['label' => $this->getMessage($this->aliasField('fromAcademicPeriod'))]]);
		$this->ControllerAction->field('next_academic_period_id', ['attr' => ['label' => $this->getMessage($this->aliasField('toAcademicPeriod'))]]);
		$this->ControllerAction->field('grade_to_promote', ['attr' => ['label' => $this->getMessage($this->aliasField('fromGrade'))]]);
		$this->ControllerAction->field('student_status_id', ['attr' => ['label' => $this->getMessage($this->aliasField('status'))]]);
		$this->ControllerAction->field('education_grade_id', ['attr' => ['label' => $this->getMessage($this->aliasField('toGrade'))]]);
		$this->ControllerAction->field('students');
		
		$this->ControllerAction->setFieldOrder(['from_academic_period_id', 'next_academic_period_id', 'grade_to_promote', 'student_status_id', 'education_grade_id','students']);
	}

	public function onUpdateFieldFromAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		switch ($action) {
			case 'reconfirm':
				$sessionKey = $this->registryAlias() . '.confirm';
				if ($this->Session->check($sessionKey)) {
					$currentData = $this->Session->read($sessionKey);
				}
				$selectedAcademicPeriodId = $currentData['from_academic_period_id'];
				$attr['type'] = 'readonly';
				$attr['attr']['value'] = $this->AcademicPeriods->get($selectedAcademicPeriodId)->name;
				break;

			default:
				if (empty($request->data[$this->alias()]['from_academic_period_id'])) {
					$request->data[$this->alias()]['from_academic_period_id'] = $this->currentPeriod->id;
				}
				$condition = [$this->AcademicPeriods->aliasField('order').' >= ' => $this->currentPeriod->order];
				$academicPeriodList = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
				$selectedPeriodId = $request->data[$this->alias()]['from_academic_period_id'];
				$AcademicPeriodsTable = $this->AcademicPeriods;
				$this->advancedSelectOptions($academicPeriodList, $selectedPeriodId, [
					'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noAvailableAcademicPeriod')),
					'callable' => function($id) use ($AcademicPeriodsTable) {
						return $AcademicPeriodsTable
							->find()
							->find('editable', ['isEditable' => true])
							->where([$AcademicPeriodsTable->aliasField('id') => $id])
							->count();
					}
				]);

				$attr['type'] = 'select';
				$attr['options'] = $academicPeriodList;
				$attr['onChangeReload'] = true;
				break;
		}
		return $attr;
	}

	public function onUpdateFieldNextAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		switch ($action) {
			case 'reconfirm':
				$sessionKey = $this->registryAlias() . '.confirm';
				if ($this->Session->check($sessionKey)) {
					$currentData = $this->Session->read($sessionKey);
				}
				if ($currentData->has('next_academic_period_id')) {
					$academicPeriodData = $this->AcademicPeriods
						->find()
						->where([$this->AcademicPeriods->aliasField($this->AcademicPeriods->primaryKey()) => $currentData->next_academic_period_id])
						->select([$this->AcademicPeriods->aliasField('name')])
						->first();
					$academicPeriodName = (!empty($academicPeriodData))? $academicPeriodData['name']: '';
				}

				$attr['type'] = 'readonly';
				$attr['attr']['value'] = (!empty($academicPeriodName))? $academicPeriodName: $this->getMessage($this->aliasField('noAvailableAcademicPeriod'));
				break;

			default:
				$selectedPeriodId = $request->data[$this->alias()]['from_academic_period_id'];
				$selectedPeriod = $this->AcademicPeriods->get($selectedPeriodId);
				$condition = [$this->AcademicPeriods->aliasField('order').' < ' => $selectedPeriod->order, $this->AcademicPeriods->aliasField('id').' <> ' => $selectedPeriodId];
				$periodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
				$attr['type'] = 'select';
				if (empty($periodOptions)) {
					$periodOptions = [0 => $this->getMessage($this->aliasField('noAvailableAcademicPeriod'))];
				}
				$selectedNextPeriodId = null;
				$AcademicPeriodsTable = $this->AcademicPeriods;
				$this->advancedSelectOptions($periodOptions, $selectedNextPeriodId, [
					'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noAvailableAcademicPeriod')),
					'callable' => function($id) use ($AcademicPeriodsTable) {
						return $AcademicPeriodsTable
							->find()
							->find('editable', ['isEditable' => true])
							->where([$AcademicPeriodsTable->aliasField('id') => $id])
							->count();
					}
				]);

				$attr['options'] = $periodOptions;
				$attr['onChangeReload'] = true;
				if (empty($request->data[$this->alias()]['next_academic_period_id'])) {
					$request->data[$this->alias()]['next_academic_period_id'] = key($periodOptions);
				}
				break;
		}		

		return $attr;
	}

	public function onUpdateFieldNextGrade(Event $event, array $attr, $action, Request $request) {
		// used for reconfirm
		$sessionKey = $this->registryAlias() . '.confirm';
		if ($this->Session->check($sessionKey)) {
			$currentData = $this->Session->read($sessionKey);
		}

		if ($currentData->has('education_grade_id')) {
			$gradeData = $this->EducationGrades
				->find()
				->where([$this->EducationGrades->aliasField($this->EducationGrades->primaryKey()) => $currentData->education_grade_id])
				->select([$this->EducationGrades->aliasField('education_programme_id'), $this->EducationGrades->aliasField('name')])
				->first();
			$gradeName = (!empty($gradeData))? $gradeData->programme_grade_name: $this->getMessage($this->aliasField('noAvailableGrades'));
		}

		$attr['type'] = 'readonly';
		$attr['attr']['value'] = (!empty($gradeName))? $gradeName: '';

		return $attr;
	}

	public function onUpdateFieldGradeToPromote(Event $event, array $attr, $action, Request $request) {
		switch ($action) {
			case 'reconfirm':
				$sessionKey = $this->registryAlias() . '.confirm';
				if ($this->Session->check($sessionKey)) {
					$currentData = $this->Session->read($sessionKey);
				}

				if ($currentData->has('grade_to_promote')) {
					$gradeData = $this->EducationGrades
						->find()
						->where([$this->EducationGrades->aliasField($this->EducationGrades->primaryKey()) => $currentData->grade_to_promote])
						->select([$this->EducationGrades->aliasField('education_programme_id'), $this->EducationGrades->aliasField('name')])
						->first();
					$gradeName = (!empty($gradeData))? $gradeData->programme_grade_name: $this->getMessage($this->aliasField('noAvailableGrades'));
				}

				$attr['type'] = 'readonly';
				$attr['attr']['value'] = (!empty($gradeName))? $gradeName: '';
				break;
			
			default:
				$InstitutionTable = $this->Institutions;
				$InstitutionGradesTable = $this->InstitutionGrades;
				$selectedPeriod = $request->data[$this->alias()]['from_academic_period_id'];
				$institutionId = $this->institutionId;
				$statuses = $this->statuses;
				$gradeOptions = $InstitutionGradesTable
					->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
					->contain(['EducationGrades.EducationProgrammes'])
					->where([$InstitutionGradesTable->aliasField('institution_id') => $institutionId])
					->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
					->order(['EducationProgrammes.order', 'EducationGrades.order'])
					->toArray();

				$attr['type'] = 'select';
				if (empty($request->data[$this->alias()]['grade_to_promote'])) {
					$selectedGrade = null;
				} else {
					$selectedGrade = $request->data[$this->alias()]['grade_to_promote'];
				}
				
				$GradeStudents = $this;
				$this->advancedSelectOptions($gradeOptions, $selectedGrade, [
					'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
					'callable' => function($id) use ($GradeStudents, $institutionId, $selectedPeriod, $statuses) {
						return $GradeStudents
							->find()
							->where([
								$GradeStudents->aliasField('institution_id') => $institutionId,
								$GradeStudents->aliasField('academic_period_id') => $selectedPeriod,
								$GradeStudents->aliasField('education_grade_id') => $id,
								$GradeStudents->aliasField('student_status_id') => $statuses['CURRENT']
							])
							->count();
					}
				]);
				$request->data[$this->alias()]['grade_to_promote'] = $selectedGrade;

				$attr['onChangeReload'] = true;
				$attr['options'] = $gradeOptions;
				if (empty($request->data[$this->alias()]['grade_to_promote'])) {
					$request->data[$this->alias()]['grade_to_promote'] = $selectedGrade;
				}
				break;
		}

		
		return $attr;
	}

	public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$studentStatusesList = $this->StudentStatuses->find('list')->toArray();
			$statusesCode = $this->statuses;
			$educationGradeId = $request->data[$this->alias()]['grade_to_promote'];
			$nextGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId, false);

			// If there is no more next grade in the same education programme then the student may be graduated
			if (count($nextGrades) == 0) {
				$options[$statusesCode['GRADUATED']] = $studentStatusesList[$statusesCode['GRADUATED']];
			} else {
				$options[$statusesCode['PROMOTED']] = $studentStatusesList[$statusesCode['PROMOTED']];
			}
			$options[$statusesCode['REPEATED']] = $studentStatusesList[$statusesCode['REPEATED']];
			$attr['options'] = $options;
			$attr['onChangeReload'] = true;
			if (empty($request->data[$this->alias()]['student_status_id']) || !array_key_exists($request->data[$this->alias()]['student_status_id'], $options)) {
				reset($options);
				$request->data[$this->alias()]['student_status_id'] = key($options);
			}
			return $attr;
		}
	}

	public function onUpdateFieldStudentStatus(Event $event, array $attr, $action, Request $request) {
		// used for reconfirm
		$sessionKey = $this->registryAlias() . '.confirm';
		if ($this->Session->check($sessionKey)) {
			$currentData = $this->Session->read($sessionKey);
		}

		if ($currentData->has('student_status_id')) {
			$statusData = $this->StudentStatuses
				->find()
				->where([$this->StudentStatuses->aliasField($this->StudentStatuses->primaryKey()) => $currentData->student_status_id])
				->select([$this->StudentStatuses->aliasField('name')])
				->first();
			$statusName = (!empty($statusData))? $statusData->name: '';
		}

		$attr['type'] = 'readonly';
		$attr['attr']['value'] = (!empty($statusName))? $statusName: '';

		return $attr;
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
		$studentStatusId = $request->data[$this->alias()]['student_status_id'];
		$statuses = $this->statuses;

		if (!in_array($studentStatusId, [$statuses['REPEATED']])) {
			$educationGradeId = $request->data[$this->alias()]['grade_to_promote'];
			$institutionId = $this->institutionId;
			
			// list of grades available to promote to
			$listOfGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId);

			// list of grades available in the institution
			$listOfInstitutionGrades = $this->InstitutionGrades
				->find('list', [
					'keyField' => 'education_grade_id', 
					'valueField' => 'education_grade.programme_grade_name'])
				->contain(['EducationGrades.EducationProgrammes'])
				->where([$this->InstitutionGrades->aliasField('institution_id') => $institutionId])
				->order(['EducationProgrammes.order', 'EducationGrades.order'])
				->toArray();

			// Only display the options that are available in the institution and also linked to the current programme
			$options = array_intersect_key($listOfInstitutionGrades, $listOfGrades);

			if (count($options) == 0) {
				$options = [0 => $this->getMessage($this->aliasField('noAvailableGrades'))];
			}
			$attr['type'] = 'select';
			$attr['options'] = $options;
		} else {
			$attr['type'] = 'hidden';
		}
		
		return $attr;
	}

	public function onUpdateFieldStudents(Event $event, array $attr, $action, Request $request) {
		$institutionId = $this->institutionId;	

		$currentData = null;
		switch ($action) {
			case 'reconfirm':
				$sessionKey = $this->registryAlias() . '.confirm';
				if ($this->Session->check($sessionKey)) {
					$currentData = $this->Session->read($sessionKey);
				}
				$attr['selectedStudents'] = ($currentData->has('students'))? $currentData->students: [];
				$selectedPeriod = $currentData['from_academic_period_id'];
				break;
			
			default:
				$currentData = $request->data[$this->alias()];
				$selectedPeriod = $request->data[$this->alias()]['from_academic_period_id'];
				break;
		}

		if (!is_null($currentData)) {
			$selectedGrade = $currentData['grade_to_promote'];
			$students = [];
			if (!is_null($selectedGrade)) {
				$studentStatuses = $this->statuses;
				$students = $this->find()
					->matching('Users')
					->matching('EducationGrades')
					->where([
						$this->aliasField('institution_id') => $institutionId,
						$this->aliasField('academic_period_id') => $selectedPeriod,
						$this->aliasField('student_status_id') => $studentStatuses['CURRENT'],
						$this->aliasField('education_grade_id') => $selectedGrade
					])
					->toArray();

				if (!empty($students)) {
					// have to see if these students have pending requests of any kind
					$StudentAdmissionTable = TableRegistry::get('Institution.StudentAdmission');
					foreach ($students as $key => $value) {
						// at this point of time it is getting all requests - (admission requests)
						$conditions = [
							'student_id' => $value->student_id, 
							'status' => $StudentAdmissionTable::NEW_REQUEST,
							'education_grade_id' => $value->education_grade_id,
							'institution_id' => $value->institution_id,
							'type' => $StudentAdmissionTable::ADMISSION
						];

						$admissionCount = $StudentAdmissionTable->find()
							->where($conditions)
							->count();

						// at this point of time it is getting all requests - (transfer requests)
						$conditions = [
							'student_id' => $value->student_id, 
							'status' => $StudentAdmissionTable::NEW_REQUEST,
							'education_grade_id' => $value->education_grade_id,
							'previous_institution_id' => $value->institution_id,
							'type' => $StudentAdmissionTable::TRANSFER,
						];

						$transferCount = $StudentAdmissionTable->find()
							->where($conditions)
							->count();

						$students[$key]->admissionRequestCount = $admissionCount + $transferCount;
					}

					$StudentDropoutTable = TableRegistry::get('Institution.StudentDropout');
					foreach ($students as $key => $value) {
						$conditions = [
							'student_id' => $value->student_id, 
							'status' => $StudentDropoutTable::NEW_REQUEST,
							'education_grade_id' => $value->education_grade_id,
							'institution_id' => $value->institution_id,
							'academic_period_id' => $value->academic_period_id,
						];

						$count = $StudentDropoutTable->find()
							->where($conditions)
							->count();

						$students[$key]->dropoutRequestCount = $count;
					}
				}
			}
			if (empty($students)) {
				$this->Alert->warning($this->aliasField('noData'));
			}
		}
		
		$attr['type'] = 'element';
		$attr['element'] = 'Institution.StudentPromotion/students';
		$attr['data'] = $students;

		return $attr;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		switch ($action) {
			case 'add':
				$toolbarButtons['back'] = $buttons['back'];
				$toolbarButtons['back']['type'] = 'button';
				$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
				$toolbarButtons['back']['attr'] = $attr;
				$toolbarButtons['back']['attr']['title'] = __('Back');
				$toolbarButtons['back']['url']['action'] = 'Students';
				break;

			case 'reconfirm':
				unset($toolbarButtons['back']);
				break;
			
			default:
				# code...
				break;
		}
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		if (array_key_exists($this->alias(), $data)) {
			$selectedStudent = false;
			if (array_key_exists('students', $data[$this->alias()])) {
				foreach ($data[$this->alias()]['students'] as $key => $value) {
					if ($value['selected'] != 0) {
						$selectedStudent = true;
						break;
					}
				}
			}
			$nextAcademicPeriodId = isset($data[$this->alias()]['next_academic_period_id']) ? $data[$this->alias()]['next_academic_period_id'] : 0;
			$educationGradeId = isset($data[$this->alias()]['education_grade_id']) ? $data[$this->alias()]['education_grade_id'] : 0;
			
			if ($nextAcademicPeriodId == 0 && $educationGradeId != 0) {
				$this->Alert->warning($this->alias().'.noNextAcademicPeriod');
				$url = $this->ControllerAction->url('add');
				$event->stopPropagation();
				return $this->controller->redirect($url);
			} else {
				if ($selectedStudent) {
					// redirects to confirmation page
					$url = $this->ControllerAction->url('reconfirm');
					$this->currentEntity = $entity;
					$session = $this->Session;
					$session->write($this->registryAlias().'.confirm', $entity);
					$session->write($this->registryAlias().'.confirmData', $data);
					$this->currentEvent = $event;
					$event->stopPropagation();
					return $this->controller->redirect($url);
				} else {
					$this->Alert->warning($this->alias().'.noStudentSelected');
					$url = $this->ControllerAction->url('add');
					$event->stopPropagation();
					return $this->controller->redirect($url);
				}
			}
		}
	}

	public function savePromotion(Entity $entity, ArrayObject $data) {
		$url = $this->ControllerAction->url('index');
		$url['action'] = 'Students';

		$nextAcademicPeriodId = null;
		$nextEducationGradeId = null;
		$fromAcademicPeriod = null;
		$currentGrade = null;
		$statusToUpdate = null;
		$studentStatuses = $this->statuses;
		$institutionId = $this->institutionId;
		if (array_key_exists('from_academic_period_id', $data[$this->alias()])) {
			$fromAcademicPeriod = $data[$this->alias()]['from_academic_period_id'];
		}
		if (array_key_exists('grade_to_promote', $data[$this->alias()])) {
			$currentGrade = $data[$this->alias()]['grade_to_promote'];
		}

		if (array_key_exists('next_academic_period_id', $data[$this->alias()])) {
			$nextAcademicPeriodId = $data[$this->alias()]['next_academic_period_id'];
		}
		if (array_key_exists('education_grade_id', $data[$this->alias()])) {
			$nextEducationGradeId = $data[$this->alias()]['education_grade_id'];
		}
		if (array_key_exists('student_status_id', $data[$this->alias()])) {
			$statusToUpdate = $data[$this->alias()]['student_status_id'];
		}
		if ($statusToUpdate == $studentStatuses['REPEATED']) {
			$nextEducationGradeId = $currentGrade;
		}
		if ($statusToUpdate == $studentStatuses['PROMOTED']) {
			$successMessage = $this->aliasField('success');
		} else if ($statusToUpdate == $studentStatuses['GRADUATED']) {
			$successMessage = $this->aliasField('successGraduated');
		} else {
			$successMessage = $this->aliasField('successOthers');
		}
		if (!empty($fromAcademicPeriod) && !empty($currentGrade)) {
			if (array_key_exists('students', $data[$this->alias()])) {
				foreach ($data[$this->alias()]['students'] as $key => $studentObj) {
					if ($studentObj['selected']) {
						unset($studentObj['selected']);
						if ($nextAcademicPeriodId != 0) {
							$studentObj['academic_period_id'] = $nextAcademicPeriodId;
							$studentObj['education_grade_id'] = $nextEducationGradeId;
							$studentObj['institution_id'] = $institutionId;
							$studentObj['student_status_id'] = $studentStatuses['CURRENT'];
							$nextPeriod = $this->AcademicPeriods->get($nextAcademicPeriodId);
							$studentObj['start_date'] = $nextPeriod->start_date->format('Y-m-d');
							$studentObj['end_date'] = $nextPeriod->end_date->format('Y-m-d');
						}
						$entity = $this->newEntity($studentObj);
						$existingStudentEntity = $this->find()->where([
								$this->aliasField('institution_id') => $institutionId,
								$this->aliasField('student_id') => $studentObj['student_id'],
								$this->aliasField('academic_period_id') => $fromAcademicPeriod,
								$this->aliasField('education_grade_id') => $currentGrade,
								$this->aliasField('student_status_id') => $studentStatuses['CURRENT']
							])->first();
						$existingStudentEntity->student_status_id = $statusToUpdate;
						
						if ($this->save($existingStudentEntity)) {
							if ($nextEducationGradeId != 0 && $nextAcademicPeriodId != 0) {
								if ($this->save($entity)) {
									$this->Alert->success($successMessage, ['reset' => true]);
								} else {
									$this->log($entity->errors(), 'debug');
								}
							} else {
								$this->Alert->success($successMessage, ['reset' => true]);
							}
						} else {
							$message = 'failed to update student status';
							$this->Alert->error($this->aliasField('savingPromotionError'), ['reset' => true]);
							$this->log($message, 'debug');
							$url['action'] = 'Promotion';
							$url[0] = 'add';
						}
					}
				}
			} else {
				$message = 'students does not exists in data';
				$this->Alert->error($this->aliasField('noStudentSelected'), ['reset' => true]);
				$this->log($message, 'debug');
				$url['action'] = 'Promotion';
				$url[0] = 'add';
			}
		} else {
			$message = 'nextAcademicPeriodId && fromAcademicPeriod && currentGrade are empty';
			$this->Alert->error($this->aliasField('noNextGradeOrNextPeriod'), ['reset' => true]);
			$this->log($message, 'debug');
			$url['action'] = 'Promotion';
			$url[0] = 'add';
		}

		return $this->controller->redirect($url);
	}

	public function reconfirm() {
		$this->Alert->info($this->aliasField('reconfirm', ['reset' => true]));

		$sessionKey = $this->registryAlias() . '.confirm';
		if ($this->Session->check($sessionKey)) {
			$currentEntity = $this->Session->read($sessionKey);
			$currentData = $this->Session->read($sessionKey.'Data');
		} else {
			$this->Alert->warning('general.notExists');
			return $this->controller->redirect($this->ControllerAction->url('add'));
		}
		$academicPeriodData = $this->AcademicPeriods
			->find()
			->where([$this->AcademicPeriods->aliasField($this->AcademicPeriods->primaryKey()) => $currentEntity->from_academic_period_id])
			->select([$this->AcademicPeriods->aliasField('name')])
			->first();
		$academicPeriodName = (!empty($academicPeriodData))? $academicPeriodData['name']: '';
		// preset all fields as invisble 
		foreach ($this->fields as $key => $value) {
			$this->fields[$key]['visible'] = false;
		}

		$this->ControllerAction->field('from_academic_period_id', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('fromAcademicPeriod'))]]);
		$this->ControllerAction->field('grade_to_promote', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('fromGrade'))]]);
		$this->ControllerAction->field('next_academic_period_id', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('toAcademicPeriod'))]]);
		$this->ControllerAction->field('student_status', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('status'))]]);
		$statuses = $this->statuses;
		$this->ControllerAction->field('students', ['type' => 'readonly']);
		if (!in_array($currentData[$this->alias()]['student_status_id'], [$statuses['REPEATED']])) {
			$this->ControllerAction->field('next_grade', ['type' => 'readonly', 'attr' => ['label' => $this->getMessage($this->aliasField('toGrade'))]]);
			$this->ControllerAction->setFieldOrder(['from_academic_period_id', 'next_academic_period_id', 'grade_to_promote', 'student_status', 'next_grade',  'students']);
		} else {
			$this->ControllerAction->setFieldOrder(['from_academic_period_id', 'next_academic_period_id', 'grade_to_promote', 'student_status',  'students']);
		}

		if ($currentEntity && !empty($currentEntity)) {
			if ($this->request->is(['post', 'put'])) {
				if ($currentData instanceOf ArrayObject) {
					$currentData = $currentData->getArrayCopy();
				}
				$currentEntity = $this->patchEntity($currentEntity, $currentData, []);
				return $this->savePromotion($currentEntity, new ArrayObject($currentData));
			}
			$this->controller->set('data', $currentEntity);
		} else {
			$this->Alert->warning('general.notExists');
			return $this->controller->redirect($this->ControllerAction->url('add'));
		}

		$this->ControllerAction->renderView('/ControllerAction/edit');
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		switch ($this->action) {
			case 'add':
				$buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Next');
				break;

			case 'reconfirm':
				$buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Confirm');
				$buttons[1]['url'] = $this->ControllerAction->url('add');
				break;
			
			default:
				# code...
				break;
		}
	}
}
