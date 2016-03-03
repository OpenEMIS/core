<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Collection\Collection;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class InstitutionClassesTable extends ControllerActionTable {
	use MessagesTrait;

	public $institutionId = 0;
	public $numberOfClasses = 1;
	public $selectedGradeType = 'single';
	public $selectedAcademicPeriodId = -1;
	public $selectedEducationGradeId = 0;

	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', 		['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Staff', 					['className' => 'User.Users', 						'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionShifts',		['className' => 'Institution.InstitutionShifts', 	'foreignKey' => 'institution_shift_id']);
		$this->belongsTo('Institutions', 			['className' => 'Institution.Institutions', 		'foreignKey' => 'institution_id']);

		$this->hasMany('InstitutionClassGrades', 	['className' => 'Institution.InstitutionClassGrades', 'dependent' => true]);
		$this->hasMany('InstitutionClassStudents', 	['className' => 'Institution.InstitutionClassStudents', 'dependent' => true]);

		$this->belongsToMany('InstitutionSubjects', [
			'className' => 'Institution.InstitutionSubjects',
			'joinTable' => 'institution_class_subjects',
			'foreignKey' => 'institution_class_id',
			'targetForeignKey' => 'institution_class_id'
		]);

		/**
		 * Shortcuts
		 */
		$this->InstitutionGrades = $this->Institutions->InstitutionGrades;

		// this behavior restricts current user to see All Classes or My Classes
		$this->addBehavior('Security.InstitutionClass');
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
	}

	public function validationDefault(Validator $validator) {
		$validator
			->requirePresence('name')
			->add('name', 'ruleUniqueNamePerAcademicPeriod', [
	        		'rule' => 'uniqueNamePerAcademicPeriod',
	        		'provider' => 'table',
			    ])
			;
		return $validator;
	}

	public static function uniqueNamePerAcademicPeriod($field, array $globalData) {
		$data = $globalData['data'];
		$model = $globalData['providers']['table'];
		$exists = $model->find('all')
			->select(['id'])
			->where([
				$model->aliasField('academic_period_id') => $globalData['data']['academic_period_id'],
				$model->aliasField('institution_id') => $globalData['data']['institution_id'],
				$model->aliasField('name') => $field,
			])
			->toArray();
		if (!empty($exists)) {
			foreach ($exists as $key => $value) {
				if (array_key_exists('id', $data) && $value->id == $data['id']) {
					// if editing an existing value
					return true;
					break;
				}
			}
			return false;
		} else {
			return true;
		}
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.delete.afterAction'] = ['callable' => 'deleteAfterAction', 'priority' => 10];
		return $events;
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$query = $this->request->query;
		$this->institutionId = $this->Session->read('Institution.Institutions.id');
		$academicPeriodOptions = $this->getAcademicPeriodOptions();
		if ($this->action == 'index') {
			if (empty($query['academic_period_id'])) {
				$query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
			}
		} else if ($this->action == 'add') {
	    	if (array_key_exists('grade_type', $query)) {
	    		$this->selectedGradeType = $query['grade_type'];
	    	}
			$gradeBehaviors = ['Institution.SingleGrade', 'Institution.MultiGrade'];
			foreach ($gradeBehaviors as $key => $behavior) {
				if ($this->hasBehavior($behavior)) {
					$this->removeBehavior($behavior);
				}
			}
			if ($this->selectedGradeType == 'single') {
				$this->addBehavior('Institution.SingleGrade');
	    	} else {
				$this->addBehavior('Institution.MultiGrade');
	    	}
		}
		if (array_key_exists($this->alias(), $this->request->data)) {
			$this->selectedAcademicPeriodId = $this->postString('academic_period_id', $academicPeriodOptions);
		} else if ($this->action == 'edit' && isset($this->request->pass[1])) {
			$id = $this->request->pass[1];
			if ($this->exists($id)) {
				$this->selectedAcademicPeriodId = $this->get($id)->academic_period_id;
			}
		}

		$this->field('class_number', ['visible' => false]);
		$this->field('modified_user_id', ['visible' => false]);
		$this->field('modified', ['visible' => false]);
		$this->field('created_user_id', ['visible' => false]);
		$this->field('created', ['visible' => false]);

		$this->field('academic_period_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true]]);
		$this->field('institution_shift_id', ['type' => 'select', 'visible' => ['view'=>true, 'edit'=>true]]);

		$this->field('staff_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);

		$this->field('male_students', ['type' => 'integer', 'visible' => ['index'=>true]]);
		$this->field('female_students', ['type' => 'integer', 'visible' => ['index'=>true]]);
		$this->field('subjects', ['override' => true, 'type' => 'integer', 'visible' => ['index'=>true]]);

		$this->field('students', [
			'label' => '',
			'override' => true,
			'type' => 'element',
			'element' => 'Institution.Classes/students',
			'data' => [	
				'students'=>[],
				'studentOptions'=>[]
			],
			'visible' => ['view'=>true, 'edit'=>true]
		]);
		$this->field('education_grades', [
			'type' => 'element',
			'element' => 'Institution.Classes/multi_grade',
			'data' => [	
				'grades'=>[]
			],
			'visible' => ['view'=>true]
		]);

		$this->setFieldOrder([
			'name', 'staff_id', 'male_students', 'female_students', 'subjects',
		]);

	}


/******************************************************************************************************************
**
** delete action methods
**
******************************************************************************************************************/
	public function deleteAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$errorMessage = $this->aliasField('stopDeleteWhenStudentExists');
		if (isset($extra['errorMessage']) && $extra['errorMessage']==$errorMessage) {
			$this->Alert->warning($errorMessage, ['reset'=>true]);
		}
	}

	public function onBeforeDelete(Event $event, Entity $entity, ArrayObject $extra) {
		$Students = $this->InstitutionClassStudents;
		$conditions = [$Students->aliasField($Students->foreignKey()) => $entity->id];
		if ($Students->exists($conditions)) {
			$extra['errorMessage'] = $this->aliasField('stopDeleteWhenStudentExists');
			$event->stopPropagation();
			return $this->controller->redirect($this->url('index'));
		}
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$query = $this->request->query;
    	if (array_key_exists('grade_type', $query)) {
			$action = $this->url('index');
			unset($action['grade_type']);
			$this->controller->redirect($action);
    	}

		$Classes = $this;

		$academicPeriodOptions = $this->AcademicPeriods->getList();

		$institutionId = $this->institutionId;
		$this->selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
		$this->advancedSelectOptions($academicPeriodOptions, $this->selectedAcademicPeriodId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
			'callable' => function($id) use ($Classes, $institutionId) {
				return $Classes->findByInstitutionIdAndAcademicPeriodId($institutionId, $id)->count();
			}
		]);
		$gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptionsForIndex($this->institutionId, $this->selectedAcademicPeriodId);
		$selectedAcademicPeriodId = $this->selectedAcademicPeriodId;
		if (!empty($gradeOptions)) {
			/**
			 * Added on PHPOE-1762 for PHPOE-1766
			 * "All Grades" option is inserted here instead of inside InstitutionGrades->getInstitutionGradeOptions() 
			 * so as to avoid unadherence of User's Requirements.
			 */
			$gradeOptions[-1] = 'All Grades';
			// sort options by key
			ksort($gradeOptions);
			/**/
		}
		
		$this->selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
		$this->advancedSelectOptions($gradeOptions, $this->selectedEducationGradeId, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
			'callable' => function($id) use ($Classes, $institutionId, $selectedAcademicPeriodId) {
				/**
				 * If statement added on PHPOE-1762 for PHPOE-1766
				 * If $id is -1, get all classes under the selected academic period
				 */
				if ($id==-1) {
					$query = $Classes->find()
						->join([
							[
								'table' => 'institution_class_grades',
								'alias' => 'InstitutionClassGrades',
								'conditions' => [
									'InstitutionClassGrades.institution_class_id = InstitutionClasses.id'
								]
							]
						])
						->where([
							$Classes->aliasField('institution_id') => $institutionId,
							$Classes->aliasField('academic_period_id') => $selectedAcademicPeriodId,
						]);
					return $query->count();
				} else {
					$query = $Classes->find()
						->join([
							[
								'table' => 'institution_class_grades',
								'alias' => 'InstitutionClassGrades',
								'conditions' => [
									'InstitutionClassGrades.institution_class_id = InstitutionClasses.id',
									'InstitutionClassGrades.education_grade_id' => $id
								]
							]
						])
						->where([
							$Classes->aliasField('institution_id') => $institutionId,
							$Classes->aliasField('academic_period_id') => $selectedAcademicPeriodId,
						]);
					return $query->count();
				}
			}
		]);

		$extra['elements']['control'] = [
			'name' => 'Institution.Classes/controls', 
			'data' => [
            	'academicPeriodOptions'=>$academicPeriodOptions, 
            	'selectedAcademicPeriod'=>$this->selectedAcademicPeriodId, 
            	'gradeOptions'=>$gradeOptions, 
            	'selectedGrade'=>$this->selectedEducationGradeId, 
			],
			'options' => [],
			'order' => 3
		];
    }

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		/**
		 * Added on PHPOE-1762 (extra feature)
		 */
		$sort = $this->queryString('sort', ['name'=>'name']);
		$direction = $this->queryString('direction', ['asc'=>'asc', 'desc'=>'desc']);
		/**/

		$query
		->find('byGrades')
		->where([$this->aliasField('academic_period_id') => $this->selectedAcademicPeriodId])
		/**
		 * Added on PHPOE-1762 (extra feature)
		 */
		->order([$this->aliasField('name')=>$direction])
		/**/
		;
	}

    public function findByGrades(Query $query, array $options) {
    	if ($this->selectedEducationGradeId != -1) {
	    	return $query
				->join([
					[
						'table' => 'institution_class_grades',
						'alias' => 'InstitutionClassGrades',
						'conditions' => [
							'InstitutionClassGrades.institution_class_id = InstitutionClasses.id',
							'InstitutionClassGrades.education_grade_id' => $this->selectedEducationGradeId
						]
					]
				]);
		} else {
	    	return $query
				->join([
					[
						'table' => 'institution_class_grades',
						'alias' => 'InstitutionClassGrades',
						'conditions' => [
							'InstitutionClassGrades.institution_class_id = InstitutionClasses.id'
						]
					]
				])
				->group(['InstitutionClassGrades.institution_class_id'])
				;
		}
    }


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
    public function viewBeforeAction(Event $event, ArrayObject $extra) {
		if ($this->selectedAcademicPeriodId == -1) {
			return $this->controller->redirect([
				'plugin' => $this->controller->plugin, 
				'controller' => $this->controller->name, 
				'action' => 'Classes'
			]);
		}

		$query = $this->request->query;
    	if (array_key_exists('academic_period_id', $query) || array_key_exists('education_grade_id', $query)) {
    		$action = $this->url('view');
    		if (array_key_exists('academic_period_id', $query)) {
	    		unset($action['academic_period_id']);
    		}
    		if (array_key_exists('education_grade_id', $query)) {
	    		unset($action['education_grade_id']);
    		}
			$this->controller->redirect($action);
    	}

		$this->setFieldOrder([
			'academic_period_id', 'name', 'institution_shift_id', 'education_grades', 'staff_id', 'students'
		]);

	}

	public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query->contain([
			'InstitutionClassStudents.StudentStatuses',
			'AcademicPeriods',
			'InstitutionShifts',
			'Staff',
			'InstitutionClassGrades.EducationGrades',
			'InstitutionClassStudents.Users.Genders',
			'InstitutionClassStudents.EducationGrades'
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->fields['students']['data']['students'] = $entity->institution_class_students;
		$this->fields['education_grades']['data']['grades'] = $entity->institution_class_grades;

		$academicPeriodOptions = $this->getAcademicPeriodOptions();
	}


/******************************************************************************************************************
**
** add action methods
**
******************************************************************************************************************/
	// selected grade_type behavior's addBeforeAction will be called later
    public function addBeforeAction(Event $event, ArrayObject $extra) {
		$query = $this->request->query;
    	if (array_key_exists('academic_period_id', $query) || array_key_exists('education_grade_id', $query)) {
    		$action = $this->url('add');
    		if (array_key_exists('academic_period_id', $query)) {
	    		unset($action['academic_period_id']);
    		}
    		if (array_key_exists('education_grade_id', $query)) {
	    		unset($action['education_grade_id']);
    		}
			$this->controller->redirect($action);
    	}
		if (array_key_exists($this->alias(), $this->request->data)) {
			$academicPeriodOptions = $this->getAcademicPeriodOptions();
			$this->selectedAcademicPeriodId = $this->postString('academic_period_id', $academicPeriodOptions);
		}
		if ($this->selectedAcademicPeriodId == -1) {
			return $this->controller->redirect([
				'plugin' => $this->controller->plugin, 
				'controller' => $this->controller->name, 
				'action' => 'Classes'
			]);
		}

		$this->Navigation->substituteCrumb(ucwords(strtolower($this->action)), ucwords(strtolower($this->action)).' '.ucwords(strtolower($this->selectedGradeType)).' Grade');

		$tabElements = [
			'single' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Classes', 'add', 'grade_type'=>'single'],
				'text' => $this->getMessage($this->aliasField('singleGrade'))
			],
			'multi' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Classes', 'add', 'grade_type'=>'multi'],
				'text' => $this->getMessage($this->aliasField('multiGrade'))
			],
		];
        $this->controller->set('tabElements', $tabElements);
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
        $errors = $entity->errors();
        if (empty($errors)) {
			/**
			 * using the entity->id (class id), find the list of grades from (institution_class_grades) linked to this class
			 */
			$grades = [];
			foreach ($entity->institution_class_grades as $grade) {
				$grades[] = $grade->education_grade_id;
			}
			$EducationGrades = TableRegistry::get('Education.EducationGrades');
			/**
			 * from the list of grades, find the list of subjects group by grades in (education_grades_subjects) where visible = 1
			 */
			$educationGradeSubjects = $EducationGrades
					->find()
					->contain(['EducationSubjects' => function($query) use ($grades) {
						return $query
							->join([
								[
									'table' => 'education_grades_subjects',
									'alias' => 'EducationGradesSubjects',
									'conditions' => [
										'EducationGradesSubjects.education_grade_id IN' => $grades,
										'EducationGradesSubjects.education_subject_id = EducationSubjects.id',
										'EducationGradesSubjects.visible' => 1
									]
								]
							]);
					}])
					->where([
						'EducationGrades.id IN' => $grades,
						'EducationGrades.visible' => 1
					])
					->toArray();
			unset($EducationGrades);
			unset($grades);

			$educationSubjects = [];
			if (count($educationGradeSubjects)>0) {
				foreach ($educationGradeSubjects as $gradeSubject) {
					foreach ($gradeSubject->education_subjects as $subject) {
						if (!isset($educationSubjects[$subject->id])) {
							$educationSubjects[$subject->id] = [
								'id' => $subject->id,
								'name' => $subject->name
							];
						}
					}
					unset($subject);
				}
				unset($gradeSubject);
			}
			unset($educationGradeSubjects);	

			/**
			 * for each education subjects, find the primary key of institution_classes using (entity->academic_period_id and institution_id and education_subject_id)
			 */
			$InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
			$institutionSubjects = $InstitutionSubjects->find('list', [
				    'keyField' => 'id',
				    'valueField' => 'education_subject_id'
				])
				->where([
					$InstitutionSubjects->aliasField('academic_period_id') => $entity->academic_period_id,
					$InstitutionSubjects->aliasField('institution_id') => $entity->institution_id,
					$InstitutionSubjects->aliasField('education_subject_id').' IN' => array_keys($educationSubjects)
				])
				->toArray();
			$institutionSubjectsIds = [];
			foreach ($institutionSubjects as $key => $value) {
				$institutionSubjectsIds[$value][] = $key;
			}
			unset($institutionSubjects);

			/**
			 * using the list of primary keys, search institution_class_subjects (InstitutionClassSubjects) to check for existing records
			 * if found, don't insert, 
			 * else create a record in institution_subjects (InstitutionSubjects)
			 * and link to the subject in institution_class_subjects (InstitutionClassSubjects) with status 1
			 */
			$InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
			$newSchoolSubjects = [];

			foreach ($educationSubjects as $key=>$educationSubject) {
				$existingSchoolSubjects = false;
				if (array_key_exists($key, $institutionSubjectsIds)) {
					$existingSchoolSubjects = $InstitutionClassSubjects->find()
						->where([
							$InstitutionClassSubjects->aliasField('institution_class_id') => $entity->id,
							$InstitutionClassSubjects->aliasField('institution_class_id').' IN' => $institutionSubjectsIds[$key],
						])
						->select(['id'])
						->first();
				}
				if (!$existingSchoolSubjects) {
					$newSchoolSubjects[$key] = [
						'name' => $educationSubject['name'],
						'institution_id' => $entity->institution_id,
						'education_subject_id' => $educationSubject['id'],
						'academic_period_id' => $entity->academic_period_id,
						'institution_class_subjects' => [
							[
								'status' => 1,
								'institution_class_id' => $entity->id
							]
						]
					];
				}
			}

			if (!empty($newSchoolSubjects)) {
				$newSchoolSubjects = $InstitutionSubjects->newEntities($newSchoolSubjects);
				foreach ($newSchoolSubjects as $subject) {
				    $InstitutionSubjects->save($subject);
				}
				unset($subject);
			}
			unset($newSchoolSubjects);
			unset($InstitutionSubjects);
			unset($InstitutionClassSubjects);
        }
	}

	public function addAfterAction(Event $event, Entity $entity) {
        $this->controller->set('selectedAction', $this->selectedGradeType);
	}


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
	public function editBeforeAction(Event $event, ArrayObject $extra) {
		if ($this->selectedAcademicPeriodId == -1) {
			return $this->controller->redirect([
				'plugin' => $this->controller->plugin, 
				'controller' => $this->controller->name, 
				'action' => 'Classes'
			]);
		}

		$this->setFieldOrder([
			'academic_period_id', 'name', 'institution_shift_id', 'staff_id', 'students',
		]);
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
		/**
		 * System is unable to cope if there are too many students to be added.
		 * Temporarily extend the server's max_execution_time to 60 seconds.
		 * @todo  Change the way to save huge hasMany records.
		 */
		ini_set('max_execution_time', 60);

		/**
		 * In students.ctp, we set the student_id as the array keys for easy search and compare.
		 * Assign back original record's id to the new list so as to preserve id numbers.
		 */
		foreach($entity->institution_class_students as $key => $record) {
			$k = $record->student_id;
			if (array_key_exists('institution_class_students', $requestData[$this->alias()])) {
				if (!array_key_exists($k, $requestData[$this->alias()]['institution_class_students'])) {			
					// PHPOE-2338 - status no longer used, record will be deleted instead
				} else {
					$requestData[$this->alias()]['institution_class_students'][$k]['id'] = $record->id;
				}
			} else {
				$requestData[$this->alias()]['institution_class_students'] = [];
				// PHPOE-2338 - status no longer used, record will be deleted instead
			}
		}
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		/**
		 * @todo  add this max limit to config
		 * This limit value is being used in ValidationBehavior->checkInstitutionClassMaxLimit() and ImportStudents as well
		 */
		$maxNumberOfStudents = 100;

		$students = $entity->institution_class_students;
		$studentOptions = $this->getStudentsOptions($entity);
		/**
		 * Check if the request is a page reload
		 */
		if (count($this->request->data)>0 && $this->request->data['submit']=='add') {
			// clear institution_class_students list grab from db
			$existingStudents = $students;
			$students = [];
			/**
			 * Populate records in the UI table & unset the record from studentOptions
			 */
			if (array_key_exists('institution_class_students', $this->request->data[$this->alias()])) {
				foreach ($this->request->data[$this->alias()]['institution_class_students'] as $row) {
					if (array_key_exists($row['student_id'], $studentOptions)) {
						$id = $row['student_id'];
						if ($id != 0) {
							$students[] = $this->createVirtualStudentEntity($id, $entity);
						}
						unset($studentOptions[$id]);
					}
				}
			}
			if (count($students)<$maxNumberOfStudents) {
				/**
				 * Insert the newly added record into the UI table & unset the record from studentOptions
				 */
				if (array_key_exists('student_id', $this->request->data)) {
					if ($this->request->data['student_id']>0) {
						$id = $this->request->data['student_id'];
						if ($id != 0) {
							$students[] = $this->createVirtualStudentEntity($id, $entity);
						}
						unset($studentOptions[$id]);
					} else if ($this->request->data['student_id'] == -1) {
						foreach ($studentOptions as $id => $name) {
							if (count($students)==$maxNumberOfStudents) {
								$this->Alert->warning($this->aliasField('maximumStudentsReached'));
								break;
							}
							if ($id > 0) {
								$students[] = $this->createVirtualStudentEntity($id, $entity);
								unset($studentOptions[$id]);
							}
						}
					}
				}
			} else {
				$this->Alert->warning($this->aliasField('maximumStudentsReached'));
			}
		} else {
			/**
			 * Just unset the record from studentOptions on first page load
			 */
			foreach ($students as $key => $student) {
				if (array_key_exists($student->student_id, $studentOptions)) {
					unset($studentOptions[$student->student_id]);
				}
				// POCOR-1694 - when there are new students added but the form submit fails validation,
				// the new students entity will not have security_users data and will produce notices.
				// Attach user data if it does not exists in the student entity
				if (!$student->has('user')) {
					$students[$key] = $this->createVirtualStudentEntity($student->student_id, $entity);
				}
			}
		}
		if (count($studentOptions) < 3) {
			$studentOptions = [$this->getMessage('Users.select_student_empty')];
		}
		$this->fields['students']['data']['students'] = $students;
		$this->fields['students']['data']['studentOptions'] = $studentOptions;
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra) {
		$currentInstitutionClassStudents = $entity->institution_class_students;
		$currentStudentIds = [];
		foreach ($currentInstitutionClassStudents as $key => $value) {
			$currentStudentIds[] = $value->student_id;
		}

		$originalInstitutionClassStudents = $entity->getOriginal('institution_class_students');
		$originalStudentIds = [];
		foreach ($originalInstitutionClassStudents as $key => $value) {
			$originalStudentIds[] = $value->student_id;
		}
		$removedStudentIds = array_diff($originalStudentIds, $currentStudentIds);

		if (!empty($removedStudentIds)) {
			// 'deleteAll will not trigger beforeDelete/afterDelete events. If you need those first load a collection of records and delete them.'
			foreach ($removedStudentIds as $key => $value) {
				$deleteClassStudent = $this->InstitutionClassStudents->find()
					->where([
						$this->InstitutionClassStudents->aliasField('institution_class_id') => $entity->id,
						$this->InstitutionClassStudents->aliasField('student_id').' IN ' => $removedStudentIds
					])
					->toArray()
					;
				foreach ($deleteClassStudent as $key => $value) {
					$this->InstitutionClassStudents->delete($value);
				}
			}
		}
	}


/******************************************************************************************************************
**
** field specific methods
**
******************************************************************************************************************/
	/**
	 * academic_period_id field setup
	 */
	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request) {
		$academicPeriodOptions = $this->AcademicPeriods->getlist(['isEditable'=>true]);
		if ($action == 'edit') {
		
			$attr['type'] = 'readonly';
			if ($this->selectedAcademicPeriodId > -1) {
				$attr['attr']['value'] = $this->AcademicPeriods->get($this->selectedAcademicPeriodId)->name;
			}

		} elseif ($action == 'add') {

			$attr['options'] = $academicPeriodOptions;
			$attr['onChangeReload'] = true;
			$attr['default'] = $this->AcademicPeriods->getCurrent();
		
		}

		return $attr;
	}

	/**
	 * institution_shift_id field setup
	 */
	public function onUpdateFieldInstitutionShiftId(Event $event, array $attr, $action, $request) {
		
		if ($action == 'edit' || $action == 'add') {

			if ($this->selectedAcademicPeriodId > -1) {
				$this->InstitutionShifts->createInstitutionDefaultShift($this->institutionId, $this->selectedAcademicPeriodId);
				$shiftOptions = $this->InstitutionShifts->getShiftOptions($this->institutionId, $this->selectedAcademicPeriodId);
			} else {
				$shiftOptions = [];
			}
			$attr['options'] = $shiftOptions;

		}

		return $attr;
	}

	/**
	 * staff_id field setup
	 */
	public function onUpdateFieldStaffId(Event $event, array $attr, $action, $request) {
		if ($action == 'edit') {

			if ($this->selectedAcademicPeriodId > -1) {
				$attr['options'] = $this->getStaffOptions('edit');
			}

		} elseif (in_array($action, ['view', 'index'])) {

			if ($this->selectedAcademicPeriodId > -1) {
				$attr['options'] = $this->getStaffOptions('view');
			}
			
		}

		return $attr;
	}

	public function onGetStaffId(Event $event, Entity $entity) {
		if ($this->action == 'view') {
			if ($entity->has('staff')) {
				return $event->subject()->Html->link($entity->staff->name_with_id , [
					'plugin' => 'Institution',
					'controller' => 'Institutions',
					'action' => 'StaffUser',
					'view',
					$entity->staff->id
				]);
			} else {
				return $this->getMessage($this->aliasField('noTeacherAssigned'));
			}
		} else {
			if ($entity->has('staff')) {
				return $entity->staff->name_with_id;
			} else {
				return $this->getMessage($this->aliasField('noTeacherAssigned'));
			}			
		}		
	}


/******************************************************************************************************************
**
** essential functions
**
******************************************************************************************************************/
	public function getClassGradeOptions($entity) {
		$Grade = $this->InstitutionClassGrades;
		$gradeOptions = $Grade->find()
							->contain('EducationGrades')
							->where([
								$Grade->aliasField('institution_class_id') => $entity->id,
								$Grade->aliasField('status') => 1
							])
							->toArray();
		$options = [];
		foreach ($gradeOptions as $key => $value) {
			$options[$value->education_grade->id] = $value->education_grade->name;
		}
		return $options;
	}

	/**
	 * [getStudentsOptions description]
	 * @param  [type] $classEntity [description]
	 * @return [type]                [description]
	 */
	public function getStudentsOptions($classEntity) {
		
		$academicPeriodObj = $this->AcademicPeriods->get($this->selectedAcademicPeriodId);
		$classGradeObjects = $classEntity->institution_class_grades;
		$classGrades = [];
		foreach ($classGradeObjects as $key=>$value) {
			$classGrades[] = $value->education_grade_id;
		}

		/**
		 * Modified this query in PHPOE-1780. Use PeriodBehavior which is loaded InstitutionStudents, by adding ->find('AcademicPeriod', ['academic_period_id'=> $this->selectedAcademicPeriodId])
		 * This is inline with how InstitutionClassesTable populate getStudentOptions.
		 */
		$students = $this->Institutions->Students;
		$query = $students
			->find('all')
			->find('AcademicPeriod', ['academic_period_id' => $this->selectedAcademicPeriodId])
			->contain(['Users'])
			->where([
				$students->aliasField('institution_id') => $this->institutionId
			])
			->toArray();
		$studentOptions = [$this->getMessage('Users.select_student')];
		if (!empty($query)) {
			$studentOptions[-1] = $this->getMessage('Users.add_all_student');
		}
		foreach ($query as $skey => $obj) {
			/**
			 * Modified this filter in PHPOE-1799.
			 * Use institution_students table through $this->Institutions->Students where Students being the table alias.
			 */
			if (in_array($obj->education_grade_id, $classGrades)) {
				if (isset($obj->user)) {
					$studentOptions[$obj->user->id] = $obj->user->name_with_id;
				} else {
					$this->log('Data corrupted with no security user for student: '. $obj->id, 'debug');
				}
			}
		}
		$studentOptions = $this->attachClassInfo($classEntity->id, $studentOptions);
		return $studentOptions;
	}

	public function attachClassInfo($id, $studentOptions) {
		$query = $this->InstitutionClassStudents->find()
					->contain(['InstitutionClasses'])
					->where([
						$this->aliasField('institution_id') => $this->institutionId,
						$this->aliasField('academic_period_id') => $this->selectedAcademicPeriodId,
					])
					->where([
							$this->InstitutionClassStudents->aliasField('student_id').' IN' => array_keys($studentOptions)
						]);
		$classesWithStudents = $query->toArray();

		foreach($classesWithStudents as $student) {
			if($student->institution_class_id != $id) {
				if (!isset($studentOptions[$student->institution_class->name])) {
					$studentOptions[$student->institution_class->name] = ['text' => 'Class '.$student->institution_class->name, 'options' => [], 'disabled' => true];
				}
				$studentOptions[$student->institution_class->name]['options'][] = ['value' => $student->student_id, 'text' => $studentOptions[$student->student_id]];
				unset($studentOptions[$student->student_id]);
			}
		}
		return $studentOptions;
	}

	public function getStaffOptions($action='edit') {
		if (in_array($action, ['edit', 'add'])) {
			$options = [0 => '-- ' . $this->getMessage($this->aliasField('selectTeacherOrLeaveBlank')) . ' --'];
		} else {
			$options = [0 => $this->getMessage($this->aliasField('noTeacherAssigned'))];
		}

		if (!empty($this->selectedAcademicPeriodId)) {

			$academicPeriodObj = $this->AcademicPeriods->get($this->selectedAcademicPeriodId);
			$startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
	        $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);

	        $Staff = $this->Institutions->Staff;
			$query = $Staff->find('all')
							->find('withBelongsTo')
							->find('byPositions', ['Institutions.id' => $this->institutionId, 'type' => 1]) // refer to OptionsTrait for type options
							->find('byInstitution', ['Institutions.id'=>$this->institutionId])
							->find('AcademicPeriod', ['academic_period_id'=>$academicPeriodObj->id])
							;

			foreach ($query->toArray() as $key => $value) {
				if ($value->has('user')) {
					$options[$value->user->id] = $value->user->name_with_id;
				}
			}
		}

		return $options;
	}

	public function numberOfClassesOptions() {
		$total = 10;
		$options = [];
		for($i=1; $i<=$total; $i++){
			$options[$i] = $i;
		}
		
		return $options;
	}
	
	public function getExistedClasses() {
		$classesByGrade = $this->InstitutionClassGrades
			->find('list', [
				'keyField'=>'id',
				'valueField'=>'institution_class_id'
			])
			->where([$this->InstitutionClassGrades->aliasField('education_grade_id') => $this->selectedEducationGradeId])
			->toArray();

		$data = $this->find('list', [
				'keyField' => 'id',
			    'valueField' => 'name'
			])
			->where([
				/**
				 * If class_number is null, it is considered as a multi-grade class
				 */
				$this->aliasField('class_number').' IS NOT NULL',
				$this->aliasField('institution_id') => $this->institutionId,
				$this->aliasField('academic_period_id') => $this->selectedAcademicPeriodId,
				$this->aliasField('id').' IN' => $classesByGrade,
			])
			->toArray()
			;
		return $data;
	}

	public function createVirtualStudentEntity($id, $entity) {
		$InstitutionStudentsTable = $this->Institutions->Students;
		$userData = $InstitutionStudentsTable->find()
			->contain(['Users' => ['Genders'], 'StudentStatuses', 'EducationGrades'])
			->where([
				$InstitutionStudentsTable->aliasField('student_id') => $id,
				$InstitutionStudentsTable->aliasField('academic_period_id') => $entity->academic_period_id,
				$InstitutionStudentsTable->aliasField('institution_id') => $entity->institution_id
			])
			->first();

		$data = [
			'id' => $this->getExistingRecordId($id, $entity),
			'student_id' => $id,
			'institution_class_id' => $entity->id,
			'education_grade_id'=>  $userData->education_grade_id,
			'student_status_id' => $userData->student_status_id,
			'education_grade' => [],
			'student_status' => [],
			'user' => []
		];
		$student = $this->InstitutionClassStudents->newEntity();
		$student = $this->InstitutionClassStudents->patchEntity($student, $data);
		$student->user = $userData->user;
		$student->student_status = $userData->student_status;
		$student->education_grade = $userData->education_grade;
		return $student;
	}

	public function getExistingRecordId($securityId, $entity) {
		$id = Text::uuid();
		foreach ($entity->institution_class_students as $student) {
			if ($student->student_id == $securityId) {
				$id = $student->id;
			}
		}
		return $id;
	}

	public function getAcademicPeriodOptions() {
		$InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
		$conditions = array(
			'InstitutionGrades.institution_id' => $this->institutionId
		);
		$list = $InstitutionGrades->getAcademicPeriodOptions($this->Alert, $conditions);
		if (!empty($list)) {
			if ($this->selectedAcademicPeriodId != 0) {
				if (!array_key_exists($this->selectedAcademicPeriodId, $list)) {
					$this->selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();
				}
			} else {
				$this->selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();
			}
		}
		return $list;
	}
	
	/**
	 * Used by Institution/UserBehavior && Institution/InstitutionStudentsTable
	 * @param  [integer]  $academicPeriodId [description]
	 * @param  [integer]  $institutionId    [description]
	 * @param  boolean $gradeId          [description]
	 * @return [type]                    [description]
	 */
	public function getClassOptions($academicPeriodId, $institutionId, $gradeId=false) {
		$multiGradeOptions = array(
			'fields' => array('InstitutionClasses.id', 'InstitutionClasses.name'),
			'conditions' => array(
				'InstitutionClasses.academic_period_id' => $academicPeriodId,
				'InstitutionClasses.institution_id' => $institutionId
			),
			'order' => array('InstitutionClasses.name')
		);

		if($gradeId != false) {
			$multiGradeOptions['join'] = array(
				array(
					'table' => 'institution_class_grades',
					'alias' => 'InstitutionClassGrades',
					'conditions' => array(
						'InstitutionClassGrades.institution_class_id = InstitutionClasses.id',
						'InstitutionClassGrades.education_grade_id = ' . $gradeId,
						'InstitutionClassGrades.status = 1'
					)
				)
			);
			$multiGradeOptions['group'] = array('InstitutionClasses.id');
		}

		$multiGradeData = $this->find('list', $multiGradeOptions);
		return $multiGradeData->toArray();
	}

	
}
